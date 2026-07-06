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
                                     const std::string &vs_raw);           // variabilný symbol → PI=/VS…/SS0/KS0

    static void drawQrToPage(HPDF_Page page,
                             const qrcodegen::QrCode &qr,
                             float x, float y,
                             float module = 3.0f);
};

// Dodávateľ pre PDF + QR (EPC / PayMe) – vyplnené z DB používateľa, doplnené z configu
struct SellerPdfInfo
{
    std::string creditor_ascii;   // meno účtu pre QR – bez diakritiky, max ~70 znakov
    std::string iban_no_spaces;   // IBAN bez medzier
    std::string seller_name_utf8; // názov v hlavičke PDF (môže mať diakritiku)
    std::string addr_line1;
    std::string addr_line2;
    std::string line_ico;         // napr. "IČO: 50761773"
    std::string line_dic;
    std::string line_phone;
    std::string line_email;
    std::string line_web;
    std::string line_registry;
    std::string line_issuer;      // "Vystavil: …"
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
