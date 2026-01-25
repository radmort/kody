// printing.h
// This file is part of the Hermes project.
#pragma once
#include "extern/qrcode/qrcodegen.hpp"
#include <hpdf.h>
#include <string>
using qrcodegen::QrCode;

class Printing
{
public:
    static std::string makeEpcPayload(const std::string &name,
                                      const std::string &iban_no_spaces,
                                      const std::string &amount_eur_or_empty, // "EUR123.45" alebo ""
                                      const std::string &remittance_unstructured);

    static qrcodegen::QrCode makeQr(const std::string &payload);

    static std::string makePaymeLink(const std::string &iban_no_spaces,
                                     const std::string &amount_eur_dot2,   // napr. "123.45" alebo "0"
                                     const std::string &creditor_name,     // CN
                                     const std::string &message,           // MSG (voľné pole)
                                     const std::string &due_date_yyyymmdd, // "" alebo "20251231"
                                     const std::string &payment_ident_pi); // napr. "/VS123/SS0/KS0" alebo ""

    static void drawQrToPage(HPDF_Page page,
                             const qrcodegen::QrCode &qr,
                             float x, float y,
                             float module = 3.0f);
};

// Jednoduché dáta o strane faktúry – použi čo máš, ostatné nechaj prázdne
struct Party
{
    std::string name;    // názov firmy/osoby
    std::string address; // ulica, PSČ, mesto
    std::string ico;     // IČO
    std::string dic;     // DIČ
    std::string icdph;   // IČ DPH (ak má)
    std::string iban;    // IBAN (bez medzier) – dôležité pre EPC QR
    std::string bank;    // názov banky (len na výpis)
    std::string email;
    std::string phone;
};
