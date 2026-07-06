// iban.cpp
// This file is part of the Hermes project.
#include "iban.h"
#include <algorithm>
#include <cctype>
#include <stdexcept>

// odstráni whitespace, prekonvertuje na UPPER
std::string IBAN::normalize(const std::string& s) {
    std::string t; t.reserve(s.size());
    for (unsigned char ch : s)
        if (!std::isspace(ch)) t.push_back(std::toupper(ch));
    return t;
}

bool IBAN::all_digits(const std::string& s) {
    return !s.empty() && std::all_of(s.begin(), s.end(),
        [](unsigned char c){ return std::isdigit(c); });
}

// MOD97 nad alfanumerickým reťazcom (A..Z -> 10..35)
int IBAN::mod97_alnum(const std::string& s) {
    int rem = 0;
    for (unsigned char ch : s) {
        if (std::isdigit(ch)) {
            rem = (rem * 10 + (ch - '0')) % 97;
        } else if (std::isalpha(ch)) {
            int v = (std::toupper(ch) - 'A') + 10;   // 10..35
            rem = (rem * 100 + v) % 97;              // písmeno reprezentuje dve číslice
        } else {
            return -1; // neplatný znak
        }
    }
    return rem;
}

// MOD97 pre plný IBAN: presun prvých 4 znakov na koniec a spočítaj mod97
int IBAN::iban_mod97(const std::string& iban_no_spaces_upper) {
    std::string s = iban_no_spaces_upper.substr(4) + iban_no_spaces_upper.substr(0, 4);
    return mod97_alnum(s);
}

// 98 - mod97(BBAN + COUNTRY + "00")
std::string IBAN::compute_check(const std::string& country, const std::string& bban20) {
    int rem = mod97_alnum(bban20 + country + "00");
    if (rem < 0) throw std::invalid_argument("Invalid characters for MOD97");
    int chk = 98 - rem;
    return (chk < 10 ? "0" : "") + std::to_string(chk);
}

// doplň vľavo nulami na width, kontrola číselnosti a dĺžky
std::string IBAN::left_pad_digits(std::string s, std::size_t width) {
    if (s.empty()) s = "0";
    if (!all_digits(s) || s.size() > width)
        throw std::invalid_argument("Field not numeric or too long");
    if (s.size() < width) s.insert(s.begin(), width - s.size(), '0');
    return s;
}

std::string IBAN::getIBAN() const noexcept {
    return _ibanNumber;
}

void IBAN::setIBAN(const std::string& cc,
                   const std::string& bankCode,
                   const std::string& prefix,
                   const std::string& account)
{
    std::string C  = normalize(cc);
    if (C != "SK")
        throw std::invalid_argument("Only SK IBAN is supported");

    std::string bc = left_pad_digits(normalize(bankCode), 4);
    std::string pr = left_pad_digits(normalize(prefix),    6);
    std::string ac = left_pad_digits(normalize(account),  10);

    std::string bban20 = bc + pr + ac;           // 20 číslic
    std::string check  = compute_check(C, bban20);
    std::string full   = C + check + bban20;     // 24 znakov

    if (!isCorrect(full))
        throw std::runtime_error("IBAN failed final validation");
    _ibanNumber = full;
}

void IBAN::setIBAN(const std::string& iban) {
    std::string s = normalize(iban);
    if (!isCorrect(s))
        throw std::runtime_error("IBAN is not correct");
    _ibanNumber = s;
}

bool IBAN::isCorrect(const std::string& iban) noexcept {
    try {
        std::string s = normalize(iban);
        if (s.size() != 24) return false;
        if (s[0] != 'S' || s[1] != 'K') return false; // SK
        if (!std::isdigit((unsigned char)s[2]) || !std::isdigit((unsigned char)s[3])) return false;
        if (!all_digits(s.substr(4))) return false;   // zvyšok samé číslice
        return iban_mod97(s) == 1;                    // ISO-13616: platný IBAN má mod97 == 1
    } catch (...) {
        return false;
    }
}
