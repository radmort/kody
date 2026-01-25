// printing.cpp
// This file is part of the Hermes project.
#include "printing.h"
using qrcodegen::QrCode;

std::string Printing::makeEpcPayload(const std::string &name,
                                     const std::string &iban,
                                     const std::string &amount_eur,
                                     const std::string &remittance)
{
    std::string p;
    p += "BCD\n";
    p += "002\n"; // version
    p += "1\n";   // charset UTF-8
    p += "SCT\n";
    p += "\n"; // BIC (prázdne)
    p += name + "\n";
    p += iban + "\n";
    p += amount_eur + "\n"; // alebo "" pre bez-sumové QR
    p += "\n";              // Purpose (prázdne)
    p += remittance + "\n";
    return p;
}

QrCode Printing::makeQr(const std::string &payload)
{
    return QrCode::encodeText(payload.c_str(), QrCode::Ecc::MEDIUM);
}

void Printing::drawQrToPage(HPDF_Page page, const QrCode &qr, float x, float y, float module)
{
    const int sz = qr.getSize();
    HPDF_Page_SetGrayFill(page, 1.0f);
    HPDF_Page_Rectangle(page, x, y, module * sz, module * sz);
    HPDF_Page_Fill(page);

    HPDF_Page_SetGrayFill(page, 0.0f);
    for (int r = 0; r < sz; ++r)
        for (int c = 0; c < sz; ++c)
            if (qr.getModule(c, sz - 1 - r))
            {
                HPDF_Page_Rectangle(page, x + c * module, y + r * module, module, module);
                HPDF_Page_Fill(page);
            }
}
// URL-encoding podľa štandardu: percent-encode, medzery ako '+'
static std::string urlEncode(const std::string &s)
{
    std::string out;
    out.reserve(s.size() * 3);
    for (unsigned char c : s)
    {
        if ((c >= 'A' && c <= 'Z') || (c >= 'a' && c <= 'z') ||
            (c >= '0' && c <= '9') || c == '-' || c == '_' || c == '.' || c == '~')
        {
            out.push_back(char(c));
        }
        else if (c == ' ')
        {
            out.push_back('+');
        }
        else
        {
            char buf[4];
            std::snprintf(buf, sizeof(buf), "%%%02X", c);
            out.append(buf);
        }
    }
    return out;
}

// Zostavenie payme URL podľa SBA Payment Link Standard (V=1)
std::string Printing::makePaymeLink(const std::string &iban_no_spaces,
                                    const std::string &amount_eur_dot2,
                                    const std::string &creditor_name,
                                    const std::string &message,
                                    const std::string &due_date_yyyymmdd,
                                    const std::string &payment_ident_pi)
{
    // Základ: povinné parametre
    std::string url = "https://payme.sk/?V=1";
    url += "&IBAN=" + urlEncode(iban_no_spaces);
    url += "&AM=" + urlEncode(amount_eur_dot2);
    url += "&CC=EUR";

    // Voliteľné
    if (!due_date_yyyymmdd.empty())
        url += "&DT=" + urlEncode(due_date_yyyymmdd);
    if (!payment_ident_pi.empty())
        url += "&PI=" + urlEncode(payment_ident_pi); // napr. "/VS20250002"
    if (!message.empty())
        url += "&MSG=" + urlEncode(message);
    if (!creditor_name.empty())
        url += "&CN=" + urlEncode(creditor_name);

    return url;
}
