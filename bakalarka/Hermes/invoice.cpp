// invoice.cpp
// This file is part of the Hermes project.
#include "invoice.h"
#include "database.h"
#include <stdexcept>
#include <iostream>
#include <numeric>
#include <limits>
#include <cmath>
#include <cstdio>
#include "printing.h"
#include "epc2d.h"
#include "layout.h"
#include <filesystem>
#include <fstream>
#include <vector>

// Pomocné: spočíta základ riadku z centov a množstva v desatinách (BIGINT)
static inline std::int64_t lineBaseFrom(const Item &it)
{
    // base = unit_price_cents * (quantity_tenths / 10)
    // v Iteme predpokladáme number() ako BIGINT "desatiny"
    return (it.unitPriceCents() * it.number()) / 10;
}

bool Invoice::_checkDbStructure() const
{
    try
    {
        auto &db = database::instance();
        (void)db.query("SELECT 1 FROM invoices LIMIT 1;");
        (void)db.query("SELECT 1 FROM invoice_items LIMIT 1;");
        return true;
    }
    catch (...)
    {
        return false;
    }
}

void Invoice::_createDbStructure()
{
    auto &db = database::instance();
    const char *sql_invoices = R"SQL(
        CREATE TABLE IF NOT EXISTS invoices (
          id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          client_id      INT UNSIGNED,
          issue_date     DATE,
          due_date       DATE,
          number         VARCHAR(64),
          currency       CHAR(3) NOT NULL DEFAULT 'EUR',
          notes          TEXT,
          CONSTRAINT fk_inv_client FOREIGN KEY (client_id) REFERENCES clients(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    )SQL";
    db.exec(sql_invoices);

    const char *sql_bridge = R"SQL(
        CREATE TABLE IF NOT EXISTS invoice_items (
          invoice_id INT UNSIGNED NOT NULL,
          item_id    INT UNSIGNED NOT NULL,
          position   INT UNSIGNED NOT NULL,
          PRIMARY KEY (invoice_id, position),
          UNIQUE KEY uniq_invoice_item (invoice_id, item_id),
          CONSTRAINT chk_pos CHECK (position > 0),
          CONSTRAINT fk_iitems_inv  FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
          CONSTRAINT fk_iitems_item FOREIGN KEY (item_id)    REFERENCES items(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    )SQL";
    db.exec(sql_bridge);
}

bool Invoice::_exists() const
{
    if (_id == 0)
        return false;
    auto &db = database::instance();
    auto stmt = db.prepare("SELECT COUNT(1) FROM invoices WHERE id=?");
    stmt.bind_uint(_id);
    stmt.result_uint();
    return stmt.fetch() && stmt.get_uint(0) > 0;
}

void Invoice::_insert()
{
    auto &db = database::instance();
    auto stmt = db.prepare(
        "INSERT INTO invoices(client_id,issue_date,due_date,number,currency,notes) "
        "VALUES(?,?,?,?,?,?)");

    if (_clientId == 0 && _client.id() != 0)
        _clientId = _client.id();

    // client_id môže byť NULL
    if (_clientId == 0)
        stmt.bind_nullable_int();
    else
        stmt.bind_uint(_clientId);

    stmt.bind_nullable_string(_issueDate.empty() ? nullptr : &_issueDate);
    stmt.bind_nullable_string(_dueDate.empty() ? nullptr : &_dueDate);
    stmt.bind_nullable_string(_number.empty() ? nullptr : &_number);

    const std::string cur = _currency.empty() ? std::string("EUR") : _currency;
    stmt.bind_string(cur);

    stmt.bind_nullable_string(_notes.empty() ? nullptr : &_notes);

    stmt.execute();

    auto rid = db.query("SELECT LAST_INSERT_ID();");
    if (rid.empty() || rid[0].empty())
        throw std::runtime_error("Cannot fetch LAST_INSERT_ID()");
    _id = static_cast<unsigned>(std::stoul(rid[0][0]));
}

void Invoice::_update()
{
    if (_id == 0)
        throw std::logic_error("Update called with id=0");
    auto &db = database::instance();
    auto stmt = db.prepare(
        "UPDATE invoices SET client_id=?,issue_date=?,due_date=?,number=?,currency=?,notes=? "
        "WHERE id=?");

    if (_clientId == 0 && _client.id() != 0)
        _clientId = _client.id();

    if (_clientId == 0)
        stmt.bind_nullable_int();
    else
        stmt.bind_uint(_clientId);

    stmt.bind_nullable_string(_issueDate.empty() ? nullptr : &_issueDate);
    stmt.bind_nullable_string(_dueDate.empty() ? nullptr : &_dueDate);
    stmt.bind_nullable_string(_number.empty() ? nullptr : &_number);

    const std::string cur = _currency.empty() ? std::string("EUR") : _currency;
    stmt.bind_string(cur);

    stmt.bind_nullable_string(_notes.empty() ? nullptr : &_notes);

    stmt.bind_uint(_id);
    stmt.execute();
}

void Invoice::_load()
{
    if (_id == 0)
        throw std::logic_error("Load called with id=0");
    auto &db = database::instance();
    auto stmt = db.prepare(
        "SELECT client_id,issue_date,due_date,number,currency,notes "
        "FROM invoices WHERE id=?");
    stmt.bind_uint(_id);

    stmt.result_uint();       // client_id
    stmt.result_string(16);   // issue_date
    stmt.result_string(16);   // due_date
    stmt.result_string(128);  // number
    stmt.result_string(8);    // currency
    stmt.result_string(2048); // notes

    if (!stmt.fetch())
        throw std::runtime_error("Invoice not found");

    _clientId = (stmt.is_null(0)) ? 0 : stmt.get_uint(0);
    _issueDate = stmt.get_string(1);
    _dueDate = stmt.get_string(2);
    _number = stmt.get_string(3);
    _currency = stmt.get_string(4).empty() ? "EUR" : stmt.get_string(4);
    _notes = stmt.get_string(5);

    // Pozn.: položky do RAM nenačítavam automaticky – na to je loadItems()
}

void Invoice::_delete()
{
    if (_id == 0)
        return;
    auto &db = database::instance();
    auto stmt = db.prepare("DELETE FROM invoices WHERE id=?");
    stmt.bind_uint(_id);
    stmt.execute();
    _id = 0; // ON DELETE CASCADE zmaže invoice_items
}

// ---------- bridge metódy skryté v Invoice ----------

void Invoice::appendItemId(std::uint32_t itemId)
{
    if (_id == 0)
        throw std::logic_error("appendItemId: invoice id not set");

    auto &db = database::instance();
    // zisti najbližšiu pozíciu
    auto sel = db.prepare("SELECT COALESCE(MAX(position),0) FROM invoice_items WHERE invoice_id=?");
    sel.bind_uint(_id);
    sel.result_uint();
    if (!sel.fetch())
        throw std::runtime_error("appendItemId: cannot compute position");
    unsigned nextPos = sel.get_uint(0) + 1;

    auto ins = db.prepare("INSERT INTO invoice_items(invoice_id,item_id,position) VALUES(?,?,?)");
    ins.bind_uint(_id);
    ins.bind_uint(itemId);
    ins.bind_uint(nextPos);
    ins.execute();
}

void Invoice::addItemAt(std::uint32_t itemId, std::uint32_t pos)
{
    if (_id == 0)
        throw std::logic_error("addItemAt: invoice id not set");
    auto &db = database::instance();
    auto ins = db.prepare("INSERT INTO invoice_items(invoice_id,item_id,position) VALUES(?,?,?)");
    ins.bind_uint(_id);
    ins.bind_uint(itemId);
    ins.bind_uint(pos);
    ins.execute();
}

void Invoice::removeItem(std::uint32_t itemId)
{
    if (_id == 0)
        throw std::logic_error("removeItem: invoice id not set");
    auto &db = database::instance();
    auto del = db.prepare("DELETE FROM invoice_items WHERE invoice_id=? AND item_id=?");
    del.bind_uint(_id);
    del.bind_uint(itemId);
    del.execute();
}

void Invoice::clearItems()
{
    if (_id == 0)
        throw std::logic_error("clearItems: invoice id not set");
    auto &db = database::instance();
    auto del = db.prepare("DELETE FROM invoice_items WHERE invoice_id=?");
    del.bind_uint(_id);
    del.execute();
}

void Invoice::loadItems()
{
    if (_id == 0)
        throw std::logic_error("loadItems: invoice id not set");
    auto &db = database::instance();
    _items.clear();

    // 1) načítaj item_id v poradí
    auto stmt = db.prepare("SELECT item_id FROM invoice_items WHERE invoice_id=? ORDER BY position ASC");
    stmt.bind_uint(_id);
    stmt.result_uint();

    while (stmt.fetch())
    {
        unsigned itemId = stmt.get_uint(0);
        Item it;
        it.setId(itemId);
        it.load(); // využije Item::_load()
        _items.emplace_back(std::move(it));
    }
}

// ---------- výpočty (z RAM položiek) ----------

std::int64_t Invoice::totalBaseCents() const noexcept
{
    std::int64_t sum = 0;
    for (const auto &it : _items)
        sum += lineBaseFrom(it);
    return sum;
}

std::int64_t Invoice::totalWithVatCents() const noexcept
{
    std::int64_t sum = 0;
    for (const auto &it : _items)
    {
        const auto base = lineBaseFrom(it);
        const auto vat = (base * it.vatBp()) / 10000;
        sum += base + vat;
    }
    return sum;
}

static std::string euros_dot2(std::int64_t cents)
{
    const bool neg = cents < 0;
    std::int64_t v = neg ? -cents : cents;
    char buf[64];
    std::snprintf(buf, sizeof(buf), "%s%lld.%02lld",
                  neg ? "-" : "",
                  static_cast<long long>(v / 100),
                  static_cast<long long>(v % 100));
    return std::string(buf);
}

static std::string vat_percent_from_bp(int bp)
{
    // 2000 -> "20"
    char buf[16];
    std::snprintf(buf, sizeof(buf), "%d", bp / 100);
    return std::string(buf);
}

static std::string iso_to_sk(const std::string &ymd)
{
    std::tm tm{};
    std::istringstream iss(ymd);
    iss >> std::get_time(&tm, "%Y-%m-%d");
    if (!iss.fail())
    {
        std::ostringstream oss;
        oss << std::put_time(&tm, "%d.%m. %Y"); // s medzerou pred rokom
        return oss.str();
    }
    // fallback: jednoduché „slice“, ak by parsing zlyhal
    if (ymd.size() >= 10 && ymd[4] == '-' && ymd[7] == '-')
        return ymd.substr(8, 2) + "." + ymd.substr(5, 2) + ". " + ymd.substr(0, 4);
    return ymd; // nechaj pôvodné, ak formát nepoznáme
}

void Invoice::printToPDF(const std::string &path,
                         const Party &buyer,
                         const std::string &remittance_text)
{
    // prevod splatnosti "YYYY-MM-DD" → "YYYYMMDD" (ak je vyplnená)
    std::string dt = this->dueDate();
    dt.erase(std::remove(dt.begin(), dt.end(), '-'), dt.end());

    // PI (payment identification): jednoduché mapovanie VS → "/VSxxxx"
    auto buildPI = [](const std::string &txt) -> std::string
    {
        // podpora viacerých tvarov: "VS:12345" | "VS 12345" | "VS12345"
        std::string digits;
        bool seenVS = false;
        for (size_t i = 0; i < txt.size(); ++i)
        {
            unsigned char c = std::toupper((unsigned char)txt[i]);
            if (!seenVS)
            {
                if (c == 'V' && i + 1 < txt.size() && std::toupper((unsigned char)txt[i + 1]) == 'S')
                {
                    seenVS = true;
                    ++i; // preskoč 'S'
                }
            }
            else
            {
                if (std::isdigit((unsigned char)c))
                    digits.push_back((char)c);
            }
        }
        return digits.empty() ? std::string() : ("/VS" + digits);
    };

    // MSG (zachováme pôvodný remittance_text do správy)
    const std::string msg = remittance_text;
    const std::string pi = buildPI(remittance_text);

    // IBAN → bez medzier, UPPER (tvoja IBAN trieda to vie)
    IBAN iban;
    iban.setIBAN("SK9511000000002943217802");

    // PAYME QR (suma = total s DPH)
    const std::string amount_num = euros_dot2(this->totalWithVatCents()); // "123.45"
    const std::string payme = Printing::makePaymeLink(
        iban.getIBAN(), amount_num, "Ing. Dušan Horváth", msg, dt, pi);
    const auto qr = Printing::makeQr(payme);

    // QR pre vyplnenie platby v bankovej aplikacii (EPC SEPA)
    epc2d::EpcData ed;
    ed.creditor_name = "Ing. Dušan Horváth"; // názov príjemcu
    ed.iban_upper = iban.getIBAN();          // IBAN bez medzier, UPPER
    ed.amount_eur_dot2 = amount_num;         // "123.45"
    ed.remittance_text = msg;                // unstructured text
    ed.purpose = "";                         // voliteľné
    ed.bic = "";                             // voliteľné

    const std::string epcPayload = epc2d::buildPayload(ed);
    const auto qr_epc = Printing::makeQr(epcPayload);

    // PDF
    HPDF_Doc pdf = HPDF_New(nullptr, nullptr);
    if (!pdf)
        throw std::runtime_error("HPDF_New failed");
    HPDF_Page page = HPDF_AddPage(pdf);
    HPDF_Page_SetSize(page, HPDF_PAGE_SIZE_A4, HPDF_PAGE_PORTRAIT);

    const float pageW = HPDF_Page_GetWidth(page);
    const float pageH = HPDF_Page_GetHeight(page);
    Layout layout = Layout::loadFromJson("layout.json", pageW, pageH);

    // Zapnúť UTF-8 encoder a načítať TTF font s Unicode
    HPDF_UseUTFEncodings(pdf);
    HPDF_SetCurrentEncoder(pdf, "UTF-8");

    // Fonty z layoutu (fallback na DejaVu)
    const char *regName = nullptr;
    const char *boldName = nullptr;
    if (!layout.font.empty())
        regName = HPDF_LoadTTFontFromFile(pdf, layout.font.c_str(), HPDF_TRUE);
    if (!layout.fontBold.empty())
        boldName = HPDF_LoadTTFontFromFile(pdf, layout.fontBold.c_str(), HPDF_TRUE);
    if (!regName)
        regName = HPDF_LoadTTFontFromFile(pdf, "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf", HPDF_TRUE);
    if (!boldName)
        boldName = HPDF_LoadTTFontFromFile(pdf, "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf", HPDF_TRUE);

    HPDF_Font font = HPDF_GetFont(pdf, regName, "UTF-8");
    HPDF_Font fontBold = HPDF_GetFont(pdf, boldName, "UTF-8");

    // default text size
    HPDF_Page_SetFontAndSize(page, fontBold, 10);

    const float L = layout.L;
    const float R = layout.R;
    // const float B = layout.B;
    const float T = layout.T;
    float x, y;

    // std::cout << "L:" << L << " R:" << R << " B:" << B << " T:" << T << std::endl;

    // -- HLAVIČKA

    std::string title = "Faktúra " + this->number();
    HPDF_Page_SetFontAndSize(page, fontBold, 16);
    HPDF_Page_BeginText(page);
    HPDF_Page_TextOut(page, L, T, title.c_str());
    HPDF_Page_EndText(page);

    // -- DODÁVATEĽ
    x = layout.head.supplier.x;
    y = layout.head.supplier.y;
    HPDF_Page_BeginText(page);
    HPDF_Page_SetFontAndSize(page, fontBold, layout.head.font.label);
    HPDF_Page_TextOut(page, x, y - 24, "Dodávateľ");
    HPDF_Page_SetFontAndSize(page, font, layout.head.font.value);
    HPDF_Page_TextOut(page, x, y - 40, "Ing. Dušan Horváth");
    HPDF_Page_TextOut(page, x, y - 54, "gen. Goliana 6009/23");
    HPDF_Page_TextOut(page, x, y - 68, "91702 Trnava");
    HPDF_Page_TextOut(page, x, y - 82, "IČO: 50761773");
    HPDF_Page_TextOut(page, x, y - 96, "DIČ: 1081429888");
    HPDF_Page_TextOut(page, x, y - 124, "Zapísané v registri:   250-38965");
    HPDF_Page_TextOut(page, x, y - 152, "Telefón:  0910 160 112");
    HPDF_Page_TextOut(page, x, y - 166, "E-mail:   info@dusanhorvath.sk");
    HPDF_Page_TextOut(page, x, y - 180, "Web:      https://dusanhorvath.sk");
    HPDF_Page_TextOut(page, x, y - 194, "Vystavil: Dušan Horváth");
    HPDF_Page_EndText(page);

    // -- ODBERATEĽ
    x = layout.head.buyer.x;
    y = layout.head.buyer.y;
    std::string adr1 = buyer.address, adr2;
    if (auto pos = buyer.address.find(", "); pos != std::string::npos)
    {
        adr1 = buyer.address.substr(0, pos);
        adr2 = buyer.address.substr(pos + 2);
    }
    std::string ico = "IČO:    " + buyer.ico;
    std::string dic = "DIČ:    " + buyer.dic;
    std::string icd = "IČDPH:  " + buyer.icdph;

    HPDF_Page_BeginText(page);
    HPDF_Page_SetFontAndSize(page, fontBold, layout.head.font.label);
    HPDF_Page_TextOut(page, x, y - 24, "Odberateľ");
    HPDF_Page_SetFontAndSize(page, font, layout.head.font.value);
    HPDF_Page_TextOut(page, x, y - 40, buyer.name.c_str());
    HPDF_Page_TextOut(page, x, y - 54, adr1.c_str());
    if (!adr2.empty())
        HPDF_Page_TextOut(page, x, y - 70, adr2.c_str());
    HPDF_Page_TextOut(page, x, y - 84, ico.c_str());
    HPDF_Page_TextOut(page, x, y - 98, dic.c_str());
    HPDF_Page_TextOut(page, x, y - 112, icd.c_str());
    HPDF_Page_EndText(page);

    HPDF_Page_SetLineWidth(page, 0.7f);

    auto hline = [&](float yy)
    {
        HPDF_Page_MoveTo(page, L, yy);
        HPDF_Page_LineTo(page, R, yy);
        HPDF_Page_Stroke(page);
    };

    hline(y - 6);

    // -- Platobné údaje / IBAN
    {
        float x = layout.payment.header.x;
        float y = layout.payment.header.y;
        HPDF_Page_BeginText(page);
        HPDF_Page_SetFontAndSize(page, font, 10);
        std::string s_iban = "IBAN: " + iban.getIBAN();
        HPDF_Page_TextOut(page, x, y, s_iban.c_str());
        HPDF_Page_EndText(page);
        HPDF_Page_SetLineWidth(page, 0.7f);
        HPDF_Page_MoveTo(page, L, y - 6);
        HPDF_Page_LineTo(page, R, y - 6);
        HPDF_Page_Stroke(page);
    }

    // -- POLOŽKY - TABUĽKA (hlavička)
    {
        std::int64_t sumBase = 0, sumVat = 0, sumTotal = 0;
        const float colX[] = {
            layout.table.col_no,
            layout.table.col_name,
            layout.table.col_qty,
            layout.table.col_unit,
            layout.table.col_unit_price,
            layout.table.col_total};
        float y = layout.table.header.y;

        HPDF_Page_BeginText(page);
        HPDF_Page_SetFontAndSize(page, fontBold, 10);
        HPDF_Page_TextOut(page, colX[0], y, "#");
        HPDF_Page_TextOut(page, colX[1], y, "Položka");
        HPDF_Page_TextOut(page, colX[2], y, "Počet");
        HPDF_Page_TextOut(page, colX[3], y, "MJ");
        HPDF_Page_TextOut(page, colX[4], y, "J. cena");
        HPDF_Page_TextOut(page, colX[5], y, "Cena spolu");
        HPDF_Page_EndText(page);

        HPDF_Page_MoveTo(page, L, y - 6);
        HPDF_Page_LineTo(page, R, y - 6);
        HPDF_Page_Stroke(page);

        hline(y - 6);

        // -- POLOŽKY - TABUĽKA (Riadky)

        int row = 1;
        y = layout.table.rowStartY;
        for (const auto &it : _items)
        {
            const auto base = lineBaseFrom(it);
            const auto vat = (base * it.vatBp()) / 10000;
            const auto tot = base + vat;
            sumBase += base;
            sumVat += vat;
            sumTotal += tot;

            // Qty: desatiny
            char qty[32];
            std::snprintf(qty, sizeof(qty), "%.1f", it.number() / 10.0);

            HPDF_Page_BeginText(page);
            HPDF_Page_SetFontAndSize(page, font, 10);
            std::string no = std::to_string(row++);
            HPDF_Page_TextOut(page, colX[0], y, no.c_str());
            HPDF_Page_TextOut(page, colX[1], y, it.name().c_str());
            HPDF_Page_TextOut(page, colX[2], y, qty);
            std::string unitPrice = euros_dot2(it.unitPriceCents());
            HPDF_Page_TextOut(page, colX[3], y, unitPrice.c_str());
            std::string vatpc = vat_percent_from_bp(it.vatBp());
            HPDF_Page_TextOut(page, colX[4], y, vatpc.c_str());
            std::string lineTotal = euros_dot2(tot);
            HPDF_Page_TextOut(page, colX[5], y, lineTotal.c_str());
            HPDF_Page_EndText(page);

            y -= (int)layout.table.rowLineHeight;
            if (y < 150.0f)
                break; // jednoduchá ochrana pred pretečením
        }

        y -= 2;
        hline(y);

        // -- SÚČTY
        y -= 18;
        std::string grand = euros_dot2(sumTotal) + " " + this->currency();
        HPDF_Page_BeginText(page);
        HPDF_Page_SetFontAndSize(page, fontBold, 12);
        HPDF_Page_TextOut(page, layout.summary.xLabel, y, "Celkom s DPH:");
        HPDF_Page_SetFontAndSize(page, fontBold, 12);
        HPDF_Page_TextOut(page, layout.summary.xValue, y, grand.c_str());
        HPDF_Page_SetFontAndSize(page, font, 10);
        HPDF_Page_TextOut(page, layout.summary.xLabel, y - 12, "Nie som platiteľ DPH.");
        HPDF_Page_EndText(page);
    }

    // -- Datumy
    std::string issue = iso_to_sk(this->issueDate().c_str());
    std::string due = iso_to_sk(this->dueDate().c_str());
    HPDF_Page_BeginText(page);
    HPDF_Page_SetFontAndSize(page, fontBold, 8);
    HPDF_Page_TextOut(page, L + 330, T - 150, "Dátum vystavenia: ");
    HPDF_Page_SetFontAndSize(page, font, 10);
    HPDF_Page_TextOut(page, L + 360, T - 162, issue.c_str());
    HPDF_Page_SetFontAndSize(page, fontBold, 8);
    HPDF_Page_TextOut(page, L + 330, T - 175, "Dátum splatnosti: ");
    HPDF_Page_SetFontAndSize(page, font, 10);
    HPDF_Page_TextOut(page, L + 360, T - 187, due.c_str());
    HPDF_Page_EndText(page);

    // -- QR kódy
    Printing::drawQrToPage(page, qr, layout.qr.payme.x, layout.qr.payme.y, layout.qr.payme.scale);
    Printing::drawQrToPage(page, qr_epc, layout.qr.epc.x, layout.qr.epc.y, layout.qr.epc.scale);

    HPDF_Page_BeginText(page);
    HPDF_Page_SetFontAndSize(page, font, layout.qr.font.label);
    HPDF_Page_TextOut(page, layout.qr.payme.x, layout.qr.payme.y - 14, "Platba mobilom");
    HPDF_Page_TextOut(page, layout.qr.epc.x, layout.qr.epc.y - 14, "Platobné údaje");
    HPDF_Page_EndText(page);

    // Uloženie do súboru (klasická cesta)
    HPDF_SaveToFile(pdf, path.c_str());
    HPDF_Free(pdf);
};