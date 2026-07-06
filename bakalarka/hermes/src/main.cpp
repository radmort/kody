// main.cpp  –  Hermes Billing Reminder (cron job)
// Queries MariaDB for unpaid invoices, generates PDFs via pdf_single_invoice (same as --pdf= / download),
// attaches them to emails sent via SMTP (libcurl), and logs results.

#include <iostream>
#include <fstream>
#include <sstream>
#include <string>
#include <vector>
#include <cstdio>
#include <cstdlib>
#include <cstring>
#include <ctime>
#include <chrono>
#include <iomanip>
#include <filesystem>
#include <stdexcept>
#include <cctype>
#include <algorithm>

// ── Hermes project headers ────────────────────────────────────────────────────
#include "database.h"
#include "client.h"
#include "item.h"
#include "invoice.h"
#include "printing.h"    // Party struct

// ── Third-party ───────────────────────────────────────────────────────────────
#include <curl/curl.h>
#include <nlohmann/json.hpp>
#include <spdlog/spdlog.h>
#include <spdlog/sinks/rotating_file_sink.h>
#include <spdlog/sinks/stdout_color_sinks.h>

namespace fs = std::filesystem;
using json   = nlohmann::json;

// ─────────────────────────────────────────────────────────────────────────────
// Config
// ─────────────────────────────────────────────────────────────────────────────

struct SmtpConfig {
    std::string host;
    std::string port        { "465" };
    std::string user;
    std::string password;
    std::string from_name;
    std::string from_email;
    bool        use_tls     { true };
};

struct SellerConfig {
    std::string name;
    std::string address;
    std::string ico;
    std::string dic;
    std::string icdph;
    std::string iban;
    std::string bank;
    std::string email;
    std::string phone;
};

struct AppConfig {
    SmtpConfig   smtp;
    SellerConfig seller;
    std::string  log_dir           { "/var/log/hermes" };
    std::string  invoice_tmp_dir   { "/tmp/hermes_invoices" };
    int          due_days_threshold{ 30 }; // spät. kompatibilita v config.json
    /** Počet kalendárnych dní po vystavení (issue_date), než môže ísť upomienka (ak ešte nie je po splatnosti). */
    int          reminder_days_after_issue{ 7 };
    /** Min. počet dní medzi dvoma úspešnými upomienkami (reminder_log) pre tú istú faktúru. */
    int          reminder_min_days_between{ 2 };
};

/** Non-empty getenv wrapper (same idea as merge-runtime-config.php). */
static std::string env_nonempty(const char* key) {
    const char* v = std::getenv(key);
    return (v && v[0] != '\0') ? std::string(v) : std::string{};
}

/** Aligns with PHP filter_var(..., FILTER_VALIDATE_BOOLEAN) for typical strings. */
static bool env_bool_smtp_tls(const char* s) {
    if (!s || !*s) {
        return false;
    }
    std::string t(s);
    for (auto& c : t) {
        c = static_cast<char>(::tolower(static_cast<unsigned char>(c)));
    }
    if (t == "1" || t == "true" || t == "on" || t == "yes") {
        return true;
    }
    if (t == "0" || t == "false" || t == "off" || t == "no") {
        return false;
    }
    return false;
}

/**
 * Rovnaká sémantika ako scripts/merge-runtime-config.php: neprázdne SMTP_* z prostredia
 * prepíšu hodnoty z JSON (jeden zdroj pravdy s PHP / Docker .env).
 */
static void apply_smtp_env_overrides(AppConfig& cfg) {
    if (auto v = env_nonempty("SMTP_HOST"); !v.empty()) {
        cfg.smtp.host = std::move(v);
    }
    if (auto v = env_nonempty("SMTP_PORT"); !v.empty()) {
        cfg.smtp.port = std::move(v);
    }
    if (auto v = env_nonempty("SMTP_USER"); !v.empty()) {
        cfg.smtp.user = std::move(v);
    }
    if (auto v = env_nonempty("SMTP_PASSWORD"); !v.empty()) {
        cfg.smtp.password = std::move(v);
    }
    if (auto v = env_nonempty("SMTP_FROM"); !v.empty()) {
        cfg.smtp.from_email = std::move(v);
    }
    if (auto v = env_nonempty("SMTP_FROM_NAME"); !v.empty()) {
        cfg.smtp.from_name = std::move(v);
    }
    const char* tls = std::getenv("SMTP_TLS");
    if (tls && tls[0] != '\0') {
        cfg.smtp.use_tls = env_bool_smtp_tls(tls);
    }
}

AppConfig load_config(const std::string& path) {
    std::ifstream f(path);
    if (!f.is_open())
        throw std::runtime_error("Cannot open config: " + path);

    json j = json::parse(f);
    AppConfig cfg;

    if (j.contains("smtp") && j["smtp"].is_object()) {
        const auto& s = j["smtp"];
        cfg.smtp.host       = s.value("host", "");
        cfg.smtp.port       = s.value("port", "465");
        cfg.smtp.user       = s.value("user", "");
        cfg.smtp.password   = s.value("password", "");
        cfg.smtp.from_name  = s.value("from_name", "");
        cfg.smtp.from_email = s.value("from_email", "");
        cfg.smtp.use_tls    = s.value("use_tls", true);
    }

    auto& sl = j["seller"];
    cfg.seller.name    = sl.value("name",    "");
    cfg.seller.address = sl.value("address", "");
    cfg.seller.ico     = sl.value("ico",     "");
    cfg.seller.dic     = sl.value("dic",     "");
    cfg.seller.icdph   = sl.value("icdph",   "");
    cfg.seller.iban    = sl.value("iban",    "");
    cfg.seller.bank    = sl.value("bank",    "");
    cfg.seller.email   = sl.value("email",   "");
    cfg.seller.phone   = sl.value("phone",   "");

    cfg.log_dir            = j["app"].value("log_dir",            "/var/log/hermes");
    cfg.invoice_tmp_dir    = j["app"].value("invoice_tmp_dir",    "/tmp/hermes_invoices");
    cfg.due_days_threshold = j["app"].value("due_days_threshold", 30);
    // Nový kľúč: reminder_days_after_issue; starý reminder_grace_days_after_due = fallback
    if (j["app"].contains("reminder_days_after_issue")) {
        cfg.reminder_days_after_issue = j["app"]["reminder_days_after_issue"].get<int>();
    } else {
        cfg.reminder_days_after_issue = j["app"].value("reminder_grace_days_after_due", 7);
    }
    if (cfg.reminder_days_after_issue < 0) {
        cfg.reminder_days_after_issue = 0;
    }
    cfg.reminder_min_days_between = j["app"].value("reminder_min_days_between", 2);
    if (cfg.reminder_min_days_between < 1) {
        cfg.reminder_min_days_between = 1;
    }

    return cfg;
}

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

std::string utc_now_str() {
    auto t = std::chrono::system_clock::to_time_t(
                 std::chrono::system_clock::now());
    std::ostringstream oss;
    oss << std::put_time(std::gmtime(&t), "%Y-%m-%d %H:%M:%S");
    return oss.str();
}

/** Dnešný dátum v lokálnej časovej zóne (YYYY-MM-DD), na porovnanie s due_date z DB. */
std::string local_date_ymd() {
    const std::time_t t = std::time(nullptr);
    std::tm           tm{};
#if defined(_WIN32)
    localtime_s(&tm, &t);
#else
    localtime_r(&t, &tm);
#endif
    char buf[16];
    if (std::strftime(buf, sizeof(buf), "%Y-%m-%d", &tm) == 0) {
        return "1970-01-01";
    }
    return std::string(buf);
}

static const std::string B64 =
    "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";

std::string base64_encode(const std::vector<uint8_t>& data) {
    std::string out;
    out.reserve(((data.size() + 2) / 3) * 4);
    for (size_t i = 0; i < data.size(); i += 3) {
        uint32_t b = (data[i] << 16);
        if (i + 1 < data.size()) b |= (data[i+1] << 8);
        if (i + 2 < data.size()) b |=  data[i+2];
        out += B64[(b >> 18) & 0x3F];
        out += B64[(b >> 12) & 0x3F];
        out += (i+1 < data.size()) ? B64[(b >>  6) & 0x3F] : '=';
        out += (i+2 < data.size()) ? B64[b         & 0x3F] : '=';
    }
    return out;
}

std::string base64_fold(const std::string& b64) {
    std::string out;
    for (size_t i = 0; i < b64.size(); i += 76)
        out += b64.substr(i, 76) + "\r\n";
    return out;
}

std::vector<uint8_t> read_file_bytes(const std::string& path) {
    std::ifstream f(path, std::ios::binary);
    if (!f.is_open())
        throw std::runtime_error("Cannot read file: " + path);
    return { std::istreambuf_iterator<char>(f), {} };
}

// ─────────────────────────────────────────────────────────────────────────────
// Invoice row  (raw DB read)
// ─────────────────────────────────────────────────────────────────────────────

struct InvoiceRow {
    std::uint32_t id;
    std::uint32_t client_id;
    std::uint32_t user_id;
    std::string   invoice_number;
    std::string   currency;
    std::string   issue_date;
    std::string   due_date;
    std::string   vs;
    /** Celková suma s DPH (centy), rovnaký výpočet ako vo webapp (SUM položiek). */
    std::int64_t  total_cents{0};
};

// ─────────────────────────────────────────────────────────────────────────────
// Database queries
// ─────────────────────────────────────────────────────────────────────────────

// Faktúry na odoslanie upomienky (e-mail + PDF). Podmienky:
// - stav nezaplatená: unpaid alebo overdue;
// - buď vystavené aspoň `days_after_issue` dní (issue_date <= dnes − N), ALEBO po splatnosti (due_date < dnes) – vtedy bez čakania;
// - od poslednej úspešnej upomienky pre tú istú faktúru uplynulo aspoň `min_days_between` dní.
//
// Pozn.: Nepoužívame prepared INTERVAL ? DAY – s naším mysql_stmt wrapperom to zle mapovalo výsledkové stĺpce (id=0, prázdne polia).
std::vector<InvoiceRow> fetch_pending(int days_after_issue, int min_days_between) {
    int di = days_after_issue;
    if (di < 0) {
        di = 0;
    }
    if (di > 9999) {
        di = 9999;
    }
    int mb = min_days_between;
    if (mb < 1) {
        mb = 1;
    }
    if (mb > 999) {
        mb = 999;
    }

    auto& db = database::instance();
    std::ostringstream sql;
    sql << "SELECT i.id, i.client_id, COALESCE(i.user_id, 1), i.invoice_number, i.currency, "
        << "DATE_FORMAT(i.issue_date, '%Y-%m-%d'), DATE_FORMAT(i.due_date, '%Y-%m-%d'), COALESCE(i.vs, ''), "
        << "(SELECT COALESCE(SUM( "
        << "(it.unit_price_cents * it.number DIV 10) "
        << "+ (it.unit_price_cents * it.number DIV 10) * it.vat_bp DIV 10000 "
        << "), 0) FROM invoice_items ii INNER JOIN items it ON it.id = ii.item_id "
        << "WHERE ii.invoice_id = i.id) AS total_cents "
        << "FROM invoices i "
        << "WHERE i.status IN ('unpaid', 'overdue') "
        << "AND ( i.issue_date <= DATE_SUB(CURDATE(), INTERVAL " << di
        << " DAY) OR i.due_date < CURDATE() ) "
        << "AND NOT EXISTS ( SELECT 1 FROM reminder_log rl WHERE rl.invoice_id = i.id AND rl.success = 1 "
        << "AND rl.sent_at >= DATE_SUB(NOW(), INTERVAL " << mb << " DAY) ) "
        << "ORDER BY i.issue_date ASC";

    auto raw = db.query(sql.str());
    std::vector<InvoiceRow> rows;
    rows.reserve(raw.size());
    for (const auto& row : raw) {
        if (row.size() < 9) {
            continue;
        }
        InvoiceRow r;
        try {
            r.id = static_cast<std::uint32_t>(std::stoul(row[0]));
        } catch (const std::exception&) {
            continue;
        }
        try {
            r.client_id = row[1].empty() ? 0U : static_cast<std::uint32_t>(std::stoul(row[1]));
        } catch (const std::exception&) {
            r.client_id = 0;
        }
        try {
            r.user_id = static_cast<std::uint32_t>(std::stoul(row[2]));
        } catch (const std::exception&) {
            r.user_id = 1;
        }
        r.invoice_number = row[3];
        r.currency       = row[4];
        r.issue_date     = row[5];
        r.due_date       = row[6];
        r.vs             = row[7];
        try {
            r.total_cents = row[8].empty() ? 0LL : static_cast<std::int64_t>(std::stoll(row[8]));
        } catch (const std::exception&) {
            r.total_cents = 0;
        }
        rows.push_back(std::move(r));
    }
    return rows;
}

void log_reminder(std::uint32_t invoice_id, std::uint32_t client_id,
                  bool success, const std::string& error_msg) {
    auto& db = database::instance();
    auto stmt = db.prepare(
        "INSERT INTO reminder_log(invoice_id, client_id, sent_at, success, error_message)"
        " VALUES(?, ?, NOW(), ?, ?)");
    stmt.bind_uint(invoice_id);
    stmt.bind_uint(client_id);
    stmt.bind_int(success ? 1 : 0);
    stmt.bind_string(error_msg);
    stmt.execute();
}

// ─────────────────────────────────────────────────────────────────────────────
// PDF: seller z users + config, poznámka do QR
// ─────────────────────────────────────────────────────────────────────────────

static std::string euros_dot2_from_cents(std::int64_t cents) {
    const bool neg = cents < 0;
    std::int64_t v = neg ? -cents : cents;
    char buf[64];
    std::snprintf(buf, sizeof(buf), "%s%lld.%02lld",
                  neg ? "-" : "",
                  static_cast<long long>(v / 100),
                  static_cast<long long>(v % 100));
    return std::string(buf);
}

/** Zobrazenie sumy v texte e-mailu (čiarka ako des. oddeľovač, ako v mailer.php). */
static std::string format_amount_sk(std::int64_t cents, const std::string& currency) {
    const bool neg = cents < 0;
    std::int64_t v = neg ? -cents : cents;
    char buf[96];
    std::snprintf(buf, sizeof(buf), "%s%lld,%02lld %s",
                  neg ? "-" : "",
                  static_cast<long long>(v / 100),
                  static_cast<long long>(v % 100),
                  currency.c_str());
    return std::string(buf);
}

static std::string ascii_fallback_name(const std::string& utf8) {
    std::string o;
    for (unsigned char c : utf8) {
        if (c < 128) o.push_back(static_cast<char>(c));
    }
    return o.empty() ? std::string("Hermes") : o;
}

static void split_cfg_address(const std::string& addr, std::string& line1, std::string& line2) {
    auto pos = addr.find(", ");
    if (pos != std::string::npos) {
        line1 = addr.substr(0, pos);
        line2 = addr.substr(pos + 2);
    } else {
        line1 = addr;
        line2.clear();
    }
}

// EPC069-12 pole 11 (Unstructured remittance): číslo faktúry, suma + VS pre bankové čítačky
static std::string trim_vs_for_epc(const std::string& s) {
    std::size_t a = 0, b = s.size();
    while (a < b && std::isspace(static_cast<unsigned char>(s[a])))
        ++a;
    while (b > a && std::isspace(static_cast<unsigned char>(s[b - 1])))
        --b;
    return s.substr(a, b - a);
}

/** VS do QR: bez medzier a bez „-“ (čítačky / PayMe často nepovoľujú pomlčku). */
static std::string vs_for_qr_payload(const std::string& vs_raw) {
    std::string t = trim_vs_for_epc(vs_raw);
    t.erase(std::remove(t.begin(), t.end(), '-'), t.end());
    return t;
}

static std::string build_epc_remittance_short(const std::string& inv_num,
                                              const std::string& amount_eur_dot2,
                                              const std::string& vs_raw) {
    std::string base = inv_num + " " + amount_eur_dot2 + " EUR";
    const std::string vs = vs_for_qr_payload(vs_raw);
    std::string suffix;
    if (!vs.empty())
        suffix = std::string(" VS ") + vs;

    std::string out;
    if (suffix.empty()) {
        out = std::move(base);
    } else if (base.size() + suffix.size() <= 140) {
        out = base + suffix;
    } else {
        const std::size_t max_base = 140 > suffix.size() ? 140 - suffix.size() : 0;
        if (base.size() > max_base)
            base.resize(max_base);
        out = base + suffix;
    }

    for (auto& ch : out) {
        if (ch == '|' || ch == '\n' || ch == '\r')
            ch = ' ';
    }
    if (out.size() > 140)
        out.resize(140);
    return out;
}

static SellerPdfInfo load_seller_merged(std::uint32_t user_id, const AppConfig& cfg) {
    SellerPdfInfo s;
    auto rows = database::instance().query(
        "SELECT COALESCE(NULLIF(TRIM(iban),''), ''), "
        "COALESCE(NULLIF(TRIM(creditor_name_ascii),''), ''), "
        "COALESCE(NULLIF(TRIM(seller_display_name),''), ''), "
        "COALESCE(seller_address, ''), "
        "COALESCE(seller_ico, ''), COALESCE(seller_dic, ''), "
        "COALESCE(seller_phone, ''), COALESCE(seller_email, ''), COALESCE(seller_web, ''), "
        "COALESCE(seller_registry, ''), COALESCE(seller_issuer, '') "
        "FROM users WHERE id=" + std::to_string(user_id) + " LIMIT 1");

    std::string iban_db, creditor_db, name_db, addr_db, ico_db, dic_db,
        phone_db, email_db, web_db, reg_db, issuer_db;

    if (!rows.empty() && rows[0].size() >= 11) {
        const auto& r = rows[0];
        iban_db     = r[0];
        creditor_db = r[1];
        name_db     = r[2];
        addr_db     = r[3];
        ico_db      = r[4];
        dic_db      = r[5];
        phone_db    = r[6];
        email_db    = r[7];
        web_db      = r[8];
        reg_db      = r[9];
        issuer_db   = r[10];
    }

    s.iban_no_spaces = !iban_db.empty() ? iban_db : cfg.seller.iban;
    s.creditor_ascii = !creditor_db.empty() ? creditor_db : ascii_fallback_name(cfg.seller.name);
    s.seller_name_utf8 = !name_db.empty() ? name_db : cfg.seller.name;

    std::string line1, line2;
    if (!addr_db.empty()) {
        split_cfg_address(addr_db, line1, line2);
    } else {
        split_cfg_address(cfg.seller.address, line1, line2);
    }
    s.addr_line1 = line1;
    s.addr_line2 = line2;

    const std::string ico_use = !ico_db.empty() ? ico_db : cfg.seller.ico;
    const std::string dic_use = !dic_db.empty() ? dic_db : cfg.seller.dic;
    if (!ico_use.empty()) s.line_ico = "IČO: " + ico_use;
    if (!dic_use.empty()) s.line_dic = "DIČ: " + dic_use;

    if (!phone_db.empty()) s.line_phone = "Telefón:  " + phone_db;
    else if (!cfg.seller.phone.empty()) s.line_phone = "Telefón:  " + cfg.seller.phone;

    if (!email_db.empty()) s.line_email = "E-mail:   " + email_db;
    else if (!cfg.seller.email.empty()) s.line_email = "E-mail:   " + cfg.seller.email;

    if (!web_db.empty()) s.line_web = "Web:      " + web_db;

    if (!reg_db.empty()) s.line_registry = reg_db;

    if (!issuer_db.empty()) s.line_issuer = issuer_db;

    return s;
}

/** Meno dodávateľa na konci textu upomienky (zhodné s PDF: users.seller_display_name → config seller.name). */
static std::string seller_sign_off_name(std::uint32_t user_id, const AppConfig& cfg) {
    auto rows = database::instance().query(
        "SELECT COALESCE(NULLIF(TRIM(seller_display_name),''), '') FROM users WHERE id=" +
        std::to_string(user_id) + " LIMIT 1");
    if (!rows.empty() && !rows[0].empty() && !rows[0][0].empty()) {
        return rows[0][0];
    }
    if (!cfg.seller.name.empty()) {
        return cfg.seller.name;
    }
    return std::string("Hermes");
}

/**
 * Rovnaká priorita ako PHP Mailer::forUserId / resolveUserSmtp:
 * ak má používateľ v DB vyplnené smtp_host aj smtp_user, použije sa to (+ doplnenie hesla z globálu ak treba);
 * inak globálne SMTP z configu / SMTP_*.
 */
static SmtpConfig resolve_smtp_for_user(std::uint32_t user_id, const AppConfig& glob) {
    auto rows = database::instance().query(
        "SELECT COALESCE(NULLIF(TRIM(smtp_host),''), ''), "
        "COALESCE(smtp_port, 465), "
        "COALESCE(NULLIF(TRIM(smtp_user),''), ''), "
        "COALESCE(smtp_password, ''), "
        "COALESCE(NULLIF(TRIM(smtp_from_email),''), ''), "
        "COALESCE(NULLIF(TRIM(smtp_from_name),''), ''), "
        "COALESCE(smtp_use_tls, 1) "
        "FROM users WHERE id=" +
        std::to_string(user_id) + " LIMIT 1");

    if (!rows.empty() && rows[0].size() >= 7) {
        const auto& r = rows[0];
        const std::string host = r[0];
        const std::string user = r[2];
        if (!host.empty() && !user.empty()) {
            SmtpConfig s;
            s.host = host;
            s.user = user;
            s.port = r[1].empty() ? std::string("465") : r[1];
            s.password = r[3].empty() ? glob.smtp.password : r[3];
            s.from_email = r[4].empty() ? (!user.empty() ? user : glob.smtp.from_email) : r[4];
            s.from_name  = r[5].empty() ? glob.smtp.from_name : r[5];
            try {
                s.use_tls = (std::stoi(r[6]) != 0);
            } catch (const std::exception&) {
                s.use_tls = glob.smtp.use_tls;
            }
            return s;
        }
    }
    return glob.smtp;
}

// ─────────────────────────────────────────────────────────────────────────────
// Email sender  (libcurl SMTP)
// ─────────────────────────────────────────────────────────────────────────────

class EmailSender {
public:
    explicit EmailSender(const SmtpConfig& cfg) : cfg_(cfg) {
        curl_global_init(CURL_GLOBAL_ALL);
    }
    ~EmailSender() { curl_global_cleanup(); }

    bool send(const Client&      client,
              const InvoiceRow&  inv,
              const std::string& pdf_path,
              const std::string& sign_off_name,
              std::string&       out_error) {

        std::vector<uint8_t> pdf;
        try {
            pdf = read_file_bytes(pdf_path);
        } catch (const std::exception& e) {
            out_error = std::string("PDF read: ") + e.what();
            return false;
        }

        std::string payload = build_mime(client, inv, pdf, sign_off_name);

        struct UploadCtx { const char* data; size_t remaining; };
        UploadCtx ctx { payload.c_str(), payload.size() };

        auto read_cb = [](char* ptr, size_t sz, size_t nmemb, void* ud) -> size_t {
            auto* c = static_cast<UploadCtx*>(ud);
            size_t n = std::min(sz * nmemb, c->remaining);
            if (!n) return 0;
            std::memcpy(ptr, c->data, n);
            c->data += n; c->remaining -= n;
            return n;
        };

        std::string url = (cfg_.use_tls ? "smtps://" : "smtp://")
                        + cfg_.host + ":" + cfg_.port;

        CURL* curl = curl_easy_init();
        if (!curl) { out_error = "curl_easy_init failed"; return false; }

        // Use correct Client getter: getEmail()
        curl_slist* rcpts = curl_slist_append(nullptr, client.getEmail().c_str());

        curl_easy_setopt(curl, CURLOPT_URL,            url.c_str());
        curl_easy_setopt(curl, CURLOPT_USERNAME,       cfg_.user.c_str());
        curl_easy_setopt(curl, CURLOPT_PASSWORD,       cfg_.password.c_str());
        curl_easy_setopt(curl, CURLOPT_MAIL_FROM,      cfg_.from_email.c_str());
        curl_easy_setopt(curl, CURLOPT_MAIL_RCPT,      rcpts);
        curl_easy_setopt(curl, CURLOPT_READFUNCTION,
                         static_cast<curl_read_callback>(read_cb));
        curl_easy_setopt(curl, CURLOPT_READDATA,       &ctx);
        curl_easy_setopt(curl, CURLOPT_UPLOAD,         1L);
        curl_easy_setopt(curl, CURLOPT_USE_SSL,        CURLUSESSL_ALL);
        curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 1L);
        curl_easy_setopt(curl, CURLOPT_VERBOSE,        0L);

        CURLcode rc = curl_easy_perform(curl);
        bool ok = (rc == CURLE_OK);
        if (!ok) out_error = curl_easy_strerror(rc);

        curl_slist_free_all(rcpts);
        curl_easy_cleanup(curl);
        return ok;
    }

private:
    SmtpConfig cfg_;

    std::string build_mime(const Client& c, const InvoiceRow& inv,
                           const std::vector<uint8_t>& pdf,
                           const std::string&          sign_off_name) {
        const std::string bnd = "==HermesReminder_" + std::to_string(inv.id) + "==";
        std::ostringstream msg;

        // Use correct getters: getName(), getEmail()
        msg << "Date: "  << utc_now_str()                          << "\r\n"
            << "From: "  << cfg_.from_name
                         << " <" << cfg_.from_email    << ">\r\n"
            << "To: "    << c.getName()
                         << " <" << c.getEmail()       << ">\r\n"
            << "Subject: Upomienka platby - faktura "
                         << inv.invoice_number                     << "\r\n"
            << "MIME-Version: 1.0\r\n"
            << "Content-Type: multipart/mixed; boundary=\"" << bnd << "\"\r\n"
            << "\r\n";

        msg << "--" << bnd                                          << "\r\n"
            << "Content-Type: text/plain; charset=UTF-8\r\n"
            << "Content-Transfer-Encoding: quoted-printable\r\n"
            << "\r\n"
            << build_body(c, inv, sign_off_name)
            << "\r\n";

        if (!pdf.empty()) {
            std::string b64 = base64_fold(base64_encode(pdf));
            std::string fn  = "Faktura_" + inv.invoice_number + ".pdf";
            msg << "--" << bnd                                      << "\r\n"
                << "Content-Type: application/pdf\r\n"
                << "Content-Transfer-Encoding: base64\r\n"
                << "Content-Disposition: attachment; filename=\"" << fn << "\"\r\n"
                << "\r\n"
                << b64
                << "\r\n";
        }

        msg << "--" << bnd << "--\r\n";
        return msg.str();
    }

    std::string build_body(const Client& c, const InvoiceRow& inv,
                           const std::string& sign_off_name) {
        const std::string today           = local_date_ymd();
        const bool        before_due_date = inv.due_date > today; // ISO tvar

        std::ostringstream b;
        b << "Vazeny zakaznik " << c.getName() << ",\r\n\r\n";
        if (before_due_date) {
            b << "Tymto vam pripominame nasledujucu fakturu (termin splatnosti este nenastal):\r\n\r\n";
        } else {
            b << "Dovolujeme si Vas upozornit na nezaplatenie nasledujucej faktury:\r\n\r\n";
        }
        b << "  Cislo faktury    : " << inv.invoice_number << "\r\n"
          << "  Datum vystavenia : " << inv.issue_date     << "\r\n"
          << "  Datum splatnosti : " << inv.due_date       << "\r\n"
          << "  Suma k uhrade    : " << format_amount_sk(inv.total_cents, inv.currency) << "\r\n\r\n"
          << "Faktura je prilozena k tomuto e-mailu.\r\n\r\n";
        if (!before_due_date) {
            b << "Ak ste platbu uz odoslali, prosim ignorujte tento e-mail.\r\n\r\n";
        }
        b << "V pripade otazok nas neváhajte kontaktovať.\r\n\r\n"
          << "S pozdravom,\r\n"
          << sign_off_name << "\r\n";
        return b.str();
    }
};

// ─────────────────────────────────────────────────────────────────────────────
// Logger
// ─────────────────────────────────────────────────────────────────────────────

void setup_logger(const std::string& log_dir) {
    fs::create_directories(log_dir);
    auto file = std::make_shared<spdlog::sinks::rotating_file_sink_mt>(
        log_dir + "/reminder.log", 10 * 1024 * 1024, 5);
    auto con  = std::make_shared<spdlog::sinks::stdout_color_sink_mt>();
    auto lg   = std::make_shared<spdlog::logger>(
        "hermes", spdlog::sinks_init_list{file, con});
    lg->set_level(spdlog::level::info);
    lg->set_pattern("[%Y-%m-%d %H:%M:%S UTC] [%l] %v");
    spdlog::set_default_logger(lg);
}

// ─────────────────────────────────────────────────────────────────────────────
// main
// ─────────────────────────────────────────────────────────────────────────────


// ─────────────────────────────────────────────────────────────────────────────
// Single-invoice PDF mode  (called when --pdf=<id> is passed)
// Uses db.query() (simple string API) to avoid prepared-statement type issues.
// Generates PDF to /tmp/hermes_invoices/inv_<id>_download.pdf, prints path.
// ─────────────────────────────────────────────────────────────────────────────

/** @param emit_path_to_stdout Ak true, vypíše cestu k PDF na stdout (režim --pdf= pre PHP). */
int pdf_single_invoice(std::uint32_t inv_id, const AppConfig& cfg, bool emit_path_to_stdout) {

    fs::create_directories(cfg.invoice_tmp_dir);

    auto& db = database::instance();

    // ── Fetch invoice using simple query API (returns vector<vector<string>>) -
    std::ostringstream sql;
    sql << "SELECT invoice_number, currency, "
        << "DATE_FORMAT(issue_date,'%Y-%m-%d'), "
        << "DATE_FORMAT(due_date,'%Y-%m-%d'), "
        << "COALESCE(vs,''), client_id, COALESCE(user_id, 1) "
        << "FROM invoices WHERE id=" << inv_id << " LIMIT 1";

    auto rows = db.query(sql.str());
    if (rows.empty()) {
        std::cerr << "Invoice " << inv_id << " not found\n";
        return EXIT_FAILURE;
    }

    const auto& r        = rows[0];
    std::string inv_num  = r[0];
    std::string currency = r[1];
    std::string issue    = r[2];
    std::string due      = r[3];
    std::string vs_raw   = r[4];
    std::uint32_t cli_id = static_cast<std::uint32_t>(std::stoul(r[5]));
    std::uint32_t user_id = static_cast<std::uint32_t>(std::stoul(r[6]));

    // ── Fetch client ──────────────────────────────────────────────────────────
    std::ostringstream csql;
    csql << "SELECT name,address,COALESCE(ico,''),COALESCE(dic,''),"
         << "COALESCE(iban,''),COALESCE(email,''),COALESCE(phone,'') "
         << "FROM clients WHERE id=" << cli_id << " LIMIT 1";

    auto crows = db.query(csql.str());
    if (crows.empty()) {
        std::cerr << "Client " << cli_id << " not found\n";
        return EXIT_FAILURE;
    }

    const auto& cr = crows[0];
    Party buyer {
        cr[0],   // name
        cr[1],   // address
        cr[2],   // ico
        cr[3],   // dic
        "",      // icdph
        cr[4],   // iban (raw string – Party accepts it directly)
        "",      // bank
        cr[5],   // email
        cr[6]    // phone
    };

    // ── Fetch items ───────────────────────────────────────────────────────────
    std::ostringstream isql;
    isql << "SELECT it.name, it.description, it.unit, "
         << "it.unit_price_cents, it.vat_bp, it.number "
         << "FROM invoice_items ii "
         << "JOIN items it ON it.id = ii.item_id "
         << "WHERE ii.invoice_id=" << inv_id
         << " ORDER BY ii.position ASC";

    auto irows = db.query(isql.str());

    // ── Build Invoice ─────────────────────────────────────────────────────────
    Invoice inv;
    inv.setNumber(inv_num);
    inv.setIssueDate(issue);
    inv.setDueDate(due);
    inv.setCurrency(currency);

    for (const auto& ir : irows) {
        Item it;
        it.setName(ir[0]);
        it.setDesc(ir[1]);
        it.setUnit(static_cast<unit>(std::stoi(ir[2])));
        it.setUnitPriceCents(std::stoll(ir[3]));
        it.setVatBp(std::stoi(ir[4]));
        it.setNumber(std::stoll(ir[5]));
        inv.addItem(it);
    }

    const SellerPdfInfo seller = load_seller_merged(user_id, cfg);
    const std::string amt = euros_dot2_from_cents(inv.totalWithVatCents());
    const std::string remittance = build_epc_remittance_short(inv_num, amt, vs_raw);

    // ── Render PDF ────────────────────────────────────────────────────────────
    fs::path pdf_path_fs = fs::path(cfg.invoice_tmp_dir)
        / ("inv_" + std::to_string(inv_id) + "_download.pdf");
    std::string pdf_path = pdf_path_fs.string();

    // If a stale file exists (e.g. created by root during manual debug),
    // remove it first so HPDF_SaveToFile can create a fresh file as www-data.
    {
        std::error_code rm_ec;
        fs::remove(pdf_path_fs, rm_ec);
    }

    try {
        inv.printToPDF(pdf_path, buyer, seller, remittance, vs_raw);
    } catch (const std::exception& e) {
        std::cerr << "printToPDF failed: " << e.what() << "\n";
        return EXIT_FAILURE;
    }

    std::error_code ec;
    if (!fs::exists(pdf_path_fs, ec) || ec) {
        std::cerr << "PDF file not created: " << pdf_path << "\n";
        return EXIT_FAILURE;
    }
    if (fs::file_size(pdf_path_fs, ec) == 0 || ec) {
        std::cerr << "PDF file is empty: " << pdf_path << "\n";
        return EXIT_FAILURE;
    }

    if (emit_path_to_stdout)
        std::cout << pdf_path << std::endl;
    return EXIT_SUCCESS;
}

int main(int argc, char* argv[]) {
    // ── Argument parsing ──────────────────────────────────────────────────────
    // Usage:
    //   billing_reminder [config.json]              → cron mode (all pending)
    //   billing_reminder [config.json] --pdf=<id>   → PDF-only mode (single invoice)
    // SMTP: JSON + apply_smtp_env_overrides (SMTP_*), rovnako ako merge-runtime-config.php / PHP.
    // ─────────────────────────────────────────────────────────────────────────

    std::string config_path = "/etc/hermes/config.json";
    std::uint32_t pdf_invoice_id = 0;   // 0 = cron mode

    for (int i = 1; i < argc; ++i) {
        std::string arg(argv[i]);
        if (arg.rfind("--pdf=", 0) == 0) {
            pdf_invoice_id = static_cast<std::uint32_t>(std::stoul(arg.substr(6)));
        } else if (arg[0] != '-') {
            config_path = arg;
        }
    }

    AppConfig cfg;
    try {
        cfg = load_config(config_path);
        apply_smtp_env_overrides(cfg);
    } catch (const std::exception& e) {
        std::cerr << "[FATAL] " << e.what() << '\n';
        return EXIT_FAILURE;
    }

    // In PDF-only mode suppress noisy spdlog output to stdout
    if (pdf_invoice_id == 0) {
        setup_logger(cfg.log_dir);
        spdlog::info("=== Hermes Reminder Job Started [{}] ===", utc_now_str());
    }

    // ── Connect to MariaDB using env vars injected by docker-compose ──────────
    try {
        auto& db = database::instance();
        db.setConfig(
            std::getenv("DB_HOST")     ? std::getenv("DB_HOST")  : "mariadb",
            static_cast<uint16_t>(std::stoi(
                std::getenv("DB_PORT") ? std::getenv("DB_PORT")  : "3306")),
            std::getenv("DB_NAME")     ? std::getenv("DB_NAME")  : "hermes_db",
            std::getenv("DB_USER")     ? std::getenv("DB_USER")  : "hermes",
            std::getenv("DB_PASSWORD") ? std::getenv("DB_PASSWORD") : ""
        );
        db.connect();
        spdlog::info("MariaDB connected.");
    } catch (const std::exception& e) {
        spdlog::critical("DB connect failed: {}", e.what());
        return EXIT_FAILURE;
    }

    // ── Ensure reminder_log table exists ──────────────────────────────────────
    try {
        database::instance().exec(R"SQL(
            CREATE TABLE IF NOT EXISTS reminder_log (
              id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              invoice_id    INT UNSIGNED NOT NULL,
              client_id     INT UNSIGNED NOT NULL,
              sent_at       DATETIME     NOT NULL DEFAULT NOW(),
              success       TINYINT(1)   NOT NULL,
              error_message TEXT,
              CONSTRAINT fk_rl_inv FOREIGN KEY (invoice_id)
                  REFERENCES invoices(id) ON DELETE CASCADE,
              CONSTRAINT fk_rl_cli FOREIGN KEY (client_id)
                  REFERENCES clients(id) ON DELETE CASCADE,
              INDEX idx_rl_invoice (invoice_id),
              INDEX idx_rl_month   (sent_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        )SQL");
    } catch (const std::exception& e) {
        spdlog::warn("reminder_log ensure: {}", e.what());
    }

    // ── PDF-only mode: generate one invoice and exit ─────────────────────────
    if (pdf_invoice_id != 0) {
        return pdf_single_invoice(pdf_invoice_id, cfg, true);
    }

    // ── Cron mode: process all pending reminders ───────────────────────────
    int exit_code = EXIT_SUCCESS;

    try {
        fs::create_directories(cfg.invoice_tmp_dir);

        auto pending = fetch_pending(cfg.reminder_days_after_issue, cfg.reminder_min_days_between);
        spdlog::info("Pending reminders: {}", pending.size());

        if (pending.empty()) {
            spdlog::info("Nothing to do.");
        }

        for (const auto& row : pending) {
            spdlog::info("Invoice {} | client_id {} | due {}",
                         row.invoice_number, row.client_id, row.due_date);

            if (row.id == 0 || row.client_id == 0) {
                spdlog::warn("  Skip: invalid invoice id or missing client_id");
                continue;
            }

            // ── 1. PDF: rovnaký kód ako pri stiahnutí / --pdf=<id> (raw SQL + layout)
            fs::path pdf_path_fs = fs::path(cfg.invoice_tmp_dir)
                / ("inv_" + std::to_string(row.id) + "_download.pdf");
            const std::string pdf_path = pdf_path_fs.string();

            if (pdf_single_invoice(row.id, cfg, false) != EXIT_SUCCESS) {
                spdlog::error("  PDF gen failed (same pipeline as --pdf=)");
                log_reminder(row.id, row.client_id, false, "PDF: generation failed");
                exit_code = EXIT_FAILURE;
                continue;
            }
            spdlog::info("  PDF: {}", pdf_path);

            // ── 2. Load client for SMTP ───────────────────────────────────────
            Client client;
            client.setId(row.client_id);
            try {
                client.load();
            } catch (const std::exception& e) {
                spdlog::error("  Client {} load: {}", row.client_id, e.what());
                log_reminder(row.id, row.client_id, false,
                             "Client load: " + std::string(e.what()));
                exit_code = EXIT_FAILURE;
                std::error_code ec_rm;
                fs::remove(pdf_path_fs, ec_rm);
                continue;
            }

            // ── 3. Send email (globálne SMTP alebo rovnako ako vo webe: users.smtp_*) ──
            SmtpConfig smtp_res = resolve_smtp_for_user(row.user_id, cfg);
            if (smtp_res.host.empty() || smtp_res.user.empty()) {
                spdlog::error(
                    "  SMTP not configured: set SMTP_* in .env / config or fill SMTP in profile (user id {}).",
                    row.user_id);
                log_reminder(row.id, row.client_id, false, "SMTP not configured");
                exit_code = EXIT_FAILURE;
                std::error_code ec_rm;
                fs::remove(pdf_path_fs, ec_rm);
                continue;
            }
            EmailSender mailer(smtp_res);
            std::string err;
            const std::string sign_off = seller_sign_off_name(row.user_id, cfg);
            bool ok = mailer.send(client, row, pdf_path, sign_off, err);
            log_reminder(row.id, row.client_id, ok, err);

            if (ok)
                spdlog::info("  ✓ Sent → {}", client.getEmail());
            else {
                spdlog::error("  ✗ Send failed: {}", err);
                exit_code = EXIT_FAILURE;
            }

            // ── 4. Remove temp PDF ────────────────────────────────────────────
            std::error_code ec;
            fs::remove(pdf_path, ec);
        }

    } catch (const std::exception& e) {
        spdlog::critical("Unhandled: {}", e.what());
        exit_code = EXIT_FAILURE;
    }

    spdlog::info("=== Hermes Reminder Job Done ===");
    return exit_code;
}
