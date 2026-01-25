// epc2d.h
// EPC SEPA QR payload builder (EPC 069-12 v1 – "001")
// Lines are LF-separated and encoded as UTF‑8.

#pragma once
#include <string>

namespace epc2d
{

    struct EpcData
    {
        std::string creditor_name;   // max 70 chars (banky často skrátia)
        std::string iban_upper;      // IBAN bez medzier, UPPER
        std::string amount_eur_dot2; // napr. "123.45" alebo "0.00"
        std::string remittance_text; // neštruktúrovaná poznámka (pole 11)
        std::string purpose;         // voliteľné (pole 9), napr. "OTHR" – nechaj "" ak nechceš
        std::string bic;             // voliteľné (pole 5), odporúča sa nechať prázdne
    };

    // Vytvorí EPC payload pre QR (SEPA Credit Transfer)
    // Špecifikácia:
    //  1: "BCD"
    //  2: "001"    (version)
    //  3: "1"      (charset: UTF-8)
    //  4: "SCT"    (SEPA Credit Transfer)
    //  5: BIC      (optional; prázdne = lepšia interoperabilita)
    //  6: Creditor Name
    //  7: IBAN
    //  8: "EUR" + amount (napr. EUR12.34)
    //  9: Purpose code (optional, napr. "OTHR" alebo prázdne)
    // 10: Remittance structured (RF...), nechávame prázdne
    // 11: Remittance unstructured (text pre príjemcu)
    // 12: Information to payee (voliteľné, nechávame prázdne)
    std::string buildPayload(const EpcData &d);

} // namespace epc2d
