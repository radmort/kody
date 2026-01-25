// iban.h
// Hermes – IBAN utilities (SK only)
// ---------------------------------
#pragma once
#include <string>

class IBAN {
private:
    std::string _ibanNumber; // uložený IBAN bez medzier, veľkými písmenami

    // helpers (definované v iban.cpp)
    static std::string normalize(const std::string& s);
    static bool        all_digits(const std::string& s);
    static int         mod97_alnum(const std::string& s);
    static int         iban_mod97(const std::string& iban_no_spaces_upper);
    static std::string compute_check(const std::string& country, const std::string& bban20);
    static std::string left_pad_digits(std::string s, std::size_t width);

public:
    IBAN() = default;
    explicit IBAN(const std::string& iban) { setIBAN(iban); }

    // Getter (vracia vždy bez medzier, UPPER)
    std::string getIBAN() const noexcept;

    // SK IBAN z častí:
    //   cc="SK", bankCode=4 číslice, prefix=0–6 číslic (prázdne -> 000000), account=1–10 číslic
    void setIBAN(const std::string& cc,
                 const std::string& bankCode,
                 const std::string& prefix,
                 const std::string& account);

    // Nastav z plného reťazca (medzery povolené). Vyhodí std::runtime_error pri neplatnom IBAN.
    void setIBAN(const std::string& iban);

    // Validácia SK IBAN (dĺžka, znaky, MOD97 == 1). Nevyhadzuje – len true/false.
    static bool isCorrect(const std::string& iban) noexcept;
};
