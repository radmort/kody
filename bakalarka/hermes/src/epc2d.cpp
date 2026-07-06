// epc2d.cpp
#include "epc2d.h"
#include <algorithm>
#include <cctype>

namespace
{
    // Trim a skrátenie na maxN (bankové čítačky niekedy striktne limitujú)
    static std::string trimmed(const std::string &s, std::size_t maxN)
    {
        std::string t = s;
        // trim left
        t.erase(t.begin(), std::find_if(t.begin(), t.end(), [](unsigned char c)
                                        { return !std::isspace(c); }));
        // trim right
        t.erase(std::find_if(t.rbegin(), t.rend(), [](unsigned char c)
                             { return !std::isspace(c); })
                    .base(),
                t.end());
        if (t.size() > maxN)
            t.resize(maxN);
        return t;
    }

    static std::string upperNoSpaces(std::string s)
    {
        s.erase(std::remove_if(s.begin(), s.end(), ::isspace), s.end());
        for (auto &ch : s)
            ch = (char)std::toupper((unsigned char)ch);
        return s;
    }
}

namespace epc2d
{

    std::string buildPayload(const EpcData &d)
    {
        std::string name = trimmed(d.creditor_name, 70);
        std::string iban = upperNoSpaces(d.iban_upper); // už má byť správny/validný
        std::string amt = d.amount_eur_dot2.empty() ? "0.00" : d.amount_eur_dot2;

        // Pole 8: "EUR" + amount (bodka ako desatinný oddeľovač)
        std::string eur_amount = "EUR" + amt;

        // Voliteľné polia
        std::string bic = trimmed(d.bic, 11);
        std::string purpose = trimmed(d.purpose, 4);
        std::string ustr = trimmed(d.remittance_text, 140); // unstructured, bežne ~140 znakov
        for (auto &ch : ustr) {
            if (ch == '|' || ch == '\n' || ch == '\r')
                ch = ' ';
        }

        // EPC payload – LF (\n) separátor
        std::string out;
        out.reserve(64 + name.size() + iban.size() + eur_amount.size() + ustr.size());

        out += "BCD\n";
        out += "001\n"; // version
        out += "1\n";   // charset UTF‑8
        out += "SCT\n"; // SEPA Credit Transfer
        out += bic;
        out += "\n";
        out += name;
        out += "\n";
        out += iban;
        out += "\n";
        out += eur_amount;
        out += "\n";
        out += purpose;
        out += "\n"; // purpose (optional)
        out += "\n"; // structured remittance (RF…), my nechávame prázdne
        out += ustr;
        out += "\n"; // unstructured remittance
        out += "\n"; // information to payee (nepoužijeme)

        return out;
    }

} // namespace epc2d
