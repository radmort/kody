// layout.cpp
#include "layout.h"
#include <fstream>
#include <sstream>
#include <cctype>
#include <stdexcept>
#include <iomanip>

// Single-header JSON (vloz do projektu napr. thirdparty/json.hpp)
#include <nlohmann/json.hpp>
using nlohmann::json;

namespace
{

    // Jednoduchý evaluátor výrazov v stringu typu: "L+30", "pageW-50", "B+16", "T-400", "200"
    // Podporované identifikátory: L,R,B,T,pageW,pageH
    struct Vars
    {
        double L, R, B, T, pageW, pageH;
    };

    double parseNumber(const std::string &s, size_t &i)
    {
        const size_t n = s.size();
        size_t start = i;
        while (i < n && (std::isdigit((unsigned char)s[i]) || s[i] == '.'))
            ++i;
        if (start == i)
            throw std::runtime_error("Expected number at: " + s.substr(i));
        return std::strtod(s.c_str() + start, nullptr);
    }

    bool parseIdent(const std::string &s, size_t &i, std::string &outIdent)
    {
        const size_t n = s.size();
        size_t start = i;
        while (i < n && (std::isalpha((unsigned char)s[i])))
            ++i;
        if (start == i)
            return false;
        outIdent.assign(s.begin() + start, s.begin() + i);
        return true;
    }

    double valueOfIdent(const std::string &id, const Vars &v)
    {
        if (id == "L")
            return v.L;
        if (id == "R")
            return v.R;
        if (id == "B")
            return v.B;
        if (id == "T")
            return v.T;
        if (id == "pageW")
            return v.pageW;
        if (id == "pageH")
            return v.pageH;
        throw std::runtime_error("Unknown identifier: " + id);
    }

    // Evaluuje výraz bez medzier s +/-, napr. "L+30", "pageW-50", "500"
    double evalExpr(const std::string &expr, const Vars &v)
    {
        // odstráň medzery
        std::string s;
        s.reserve(expr.size());
        for (unsigned char c : expr)
            if (!std::isspace(c))
                s.push_back((char)c);

        const size_t n = s.size();
        size_t i = 0;
        double acc = 0.0;
        int sign = +1;

        auto readTerm = [&]() -> double
        {
            if (i >= n)
                throw std::runtime_error("Unexpected end in expr: " + s);
            if (std::isdigit((unsigned char)s[i]) || s[i] == '.')
            {
                return parseNumber(s, i);
            }
            else
            {
                std::string id;
                if (!parseIdent(s, i, id))
                    throw std::runtime_error("Expected ident/number in: " + s);
                return valueOfIdent(id, v);
            }
        };

        while (i < n)
        {
            if (s[i] == '+')
            {
                sign = +1;
                ++i;
                continue;
            }
            if (s[i] == '-')
            {
                sign = -1;
                ++i;
                continue;
            }
            double term = readTerm();
            acc += sign * term;
            if (i < n && s[i] != '+' && s[i] != '-') // po terme môžu ísť len +/-
                throw std::runtime_error("Unexpected char in expr: " + s.substr(i));
        }
        return acc;
    }

    float getFloat(const json &j, const char *key, float def, const Vars &v)
    {
        if (!j.contains(key))
            return def;
        const auto &val = j.at(key);
        if (val.is_number())
            return (float)val.get<double>();
        if (val.is_string())
            return (float)evalExpr(val.get<std::string>(), v);
        return def;
    }

    std::string getString(const json &j, const char *key, std::string def, const Vars &v)
    {
        (void)v; // zatiaľ nevyužité
        if (!j.contains(key))
            return def;
        const auto &val = j.at(key);

        if (val.is_string())
            return val.get<std::string>();
        if (val.is_number_integer())
            return std::to_string(val.get<long long>());
        if (val.is_number_float())
        {
            double d = val.get<double>();
            std::ostringstream oss;
            oss.setf(std::ios::fmtflags(0), std::ios::floatfield);
            oss << std::setprecision(15) << d;
            std::string s = oss.str();
            auto pos = s.find('.');
            if (pos != std::string::npos)
            {
                while (!s.empty() && s.back() == '0')
                    s.pop_back();
                if (!s.empty() && s.back() == '.')
                    s.pop_back();
            }
            return s;
        }
        if (val.is_boolean())
            return val.get<bool>() ? "true" : "false";
        return def;
    }

} // namespace

Layout Layout::loadFromJson(const std::string &path, float pageW, float pageH)
{
    static const char k_fallback[] = "/etc/hermes/layout.json";
    std::ifstream f(path);
    if (!f.good())
    {
        // Relatívne „layout.json“ funguje len s cwd (napr. web cez cd /etc/hermes).
        // Cron má iný prac. adresár → bez súboru sú len holé defaulty a PDF je nepoužiteľné.
        if (path != k_fallback)
            return loadFromJson(k_fallback, pageW, pageH);
        Layout lay; // defaulty, ak chýba aj /etc/hermes
        return lay;
    }
    Layout lay; // defaulty (prepíšu sa z JSON)

    json cfg = json::parse(f, nullptr, /*allow_exceptions=*/true);

    // Marže → L/R/B/T
    float mLeft = 50.f, mRight = 50.f, mBottom = 20.f, mTop = 60.f;
    if (cfg.contains("margins"))
    {
        const json &m = cfg["margins"];
        Vars v0{0, 0, 0, 0, pageW, pageH};
        mLeft = getFloat(m, "left", mLeft, v0);
        mRight = getFloat(m, "right", mRight, v0);
        mBottom = getFloat(m, "bottom", mBottom, v0);
        mTop = getFloat(m, "top", mTop, v0);
    }
    lay.L = mLeft;
    lay.R = pageW - mRight;
    lay.B = mBottom;
    lay.T = pageH - mTop;

    Vars v{lay.L, lay.R, lay.B, lay.T, pageW, pageH};

    // Fonty (cesty) – ak chýbajú, necháme prázdne (fallback urobí kód PDF)
    if (cfg.contains("font"))
        lay.font = getString(cfg, "font", lay.font, v);
    if (cfg.contains("fontBold"))
        lay.fontBold = getString(cfg, "fontBold", lay.fontBold, v);

    // HEAD (Dodávateľ/ Odberateľ)
    if (cfg.contains("head"))
    {
        const json &h = cfg["head"];
        lay.head.font.label = getFloat(h, "labelFontSize", lay.head.font.label, v);
        lay.head.font.value = getFloat(h, "valueFontSize", lay.head.font.value, v);

        if (h.contains("supplier"))
        {
            const json &s = h["supplier"];
            lay.head.supplier.x = getFloat(s, "x", lay.head.supplier.x, v);
            lay.head.supplier.y = getFloat(s, "y", lay.head.supplier.y, v);
        }

        // podpor obidva názvy: "buyer" aj "client"
        const json *bptr = nullptr;
        if (h.contains("buyer"))
            bptr = &h["buyer"];
        else if (h.contains("client"))
            bptr = &h["client"];
        if (bptr)
        {
            const json &b = *bptr;
            lay.head.buyer.x = getFloat(b, "x", lay.head.buyer.x, v);
            lay.head.buyer.y = getFloat(b, "y", lay.head.buyer.y, v);
        }
    }

    // DATES (issue/due)
    if (cfg.contains("dates"))
    {
        const json &d = cfg["dates"];
        lay.dates.font.label = getFloat(d, "labelFontSize", lay.dates.font.label, v);
        lay.dates.font.value = getFloat(d, "valueFontSize", lay.dates.font.value, v);

        if (d.contains("issue"))
        {
            const json &i = d["issue"];
            lay.dates.issue.x = getFloat(i, "x", lay.dates.issue.x, v);
            lay.dates.issue.y = getFloat(i, "y", lay.dates.issue.y, v);
        }
        if (d.contains("due"))
        {
            const json &u = d["due"];
            lay.dates.due.x = getFloat(u, "x", lay.dates.due.x, v);
            lay.dates.due.y = getFloat(u, "y", lay.dates.due.y, v);
        }
    }

    // QR
    if (cfg.contains("qr"))
    {
        const json &q = cfg["qr"];
        // spätná kompatibilita: ak je len "labelFontSize", použijeme ho pre qr.font.label (a value = label)
        lay.qr.font.label = getFloat(q, "labelFontSize", lay.qr.font.label, v);
        lay.qr.font.value = getFloat(q, "valueFontSize", lay.qr.font.value, v);
        if (q.contains("labelFontSize") && !q.contains("valueFontSize"))
        {
            lay.qr.font.value = lay.qr.font.label;
        }

        if (q.contains("payme"))
        {
            const json &p = q["payme"];
            lay.qr.payme.x = getFloat(p, "x", lay.qr.payme.x, v);
            lay.qr.payme.y = getFloat(p, "y", lay.qr.payme.y, v);
            lay.qr.payme.scale = getFloat(p, "scale", lay.qr.payme.scale, v);
        }
        if (q.contains("epc"))
        {
            const json &e = q["epc"];
            lay.qr.epc.x = getFloat(e, "x", lay.qr.epc.x, v);
            lay.qr.epc.y = getFloat(e, "y", lay.qr.epc.y, v);
            lay.qr.epc.scale = getFloat(e, "scale", lay.qr.epc.scale, v);
        }
    }

    // PAYMENT (hlavička/IBAN blok)
    if (cfg.contains("payment"))
    {
        const json &p = cfg["payment"];
        lay.payment.header.x = getFloat(p, "x", lay.payment.header.x, v);
        lay.payment.header.y = getFloat(p, "y", lay.payment.header.y, v);
    }

    // TABLE
    if (cfg.contains("table"))
    {
        const json &t = cfg["table"];

        if (t.contains("header"))
        {
            const json &h = t["header"];
            lay.table.header.x = getFloat(h, "x", lay.table.header.x, v);
            lay.table.header.y = getFloat(h, "y", lay.table.header.y, v);
        }

        lay.table.rowStartY = getFloat(t, "rowStartY", lay.table.rowStartY, v);
        lay.table.rowLineHeight = getFloat(t, "rowLineHeight", lay.table.rowLineHeight, v);

        if (t.contains("colsX"))
        {
            const json &c = t["colsX"];
            lay.table.col_no = getFloat(c, "no", lay.table.col_no, v);
            lay.table.col_name = getFloat(c, "name", lay.table.col_name, v);
            lay.table.col_qty = getFloat(c, "qty", lay.table.col_qty, v);
            lay.table.col_unit = getFloat(c, "unit", lay.table.col_unit, v);
            lay.table.col_unit_price = getFloat(c, "unit_price", lay.table.col_unit_price, v);
            lay.table.col_total = getFloat(c, "total", lay.table.col_total, v);
        }
    }

    // SUMMARY
    if (cfg.contains("summary"))
    {
        const json &s = cfg["summary"];
        lay.summary.xLabel = getFloat(s, "xLabel",
                                      getFloat(s, "x", lay.summary.xLabel, v), v);
        float defXValue = lay.summary.xLabel + 150.f;
        lay.summary.xValue = getFloat(s, "xValue", defXValue, v);
    }

    return lay;
}
