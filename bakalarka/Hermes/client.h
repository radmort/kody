// client.h
#pragma once
#include <string>
#include <mutex>
#include "iban.h"
#include "IStorable.h"
#include <algorithm>
#include <cctype>
#include <stdexcept>

class Client : public IStorable
{
public:
    Client() = default;
    ~Client() override = default;

    // get/set
    void setName(const std::string &v) { _name = v; }
    const std::string &getName() const { return _name; }
    void setAddress(const std::string &v) { _address = v; }
    const std::string &getAddress() const { return _address; }
    void setEmail(const std::string &v)
    {
        if (v.empty())
            throw std::invalid_argument("Email cannot be empty.");
        _email = v;
    }
    const std::string &getEmail() const { return _email; }
    void setPhone(const std::string &v) { _phone = v; }
    const std::string &getPhone() const { return _phone; }
    void setICO(const std::string &v)
    {
        // prázdny -> povolené (uložíš ako NULL cez bind_nullable_string)
        if (v.empty())
        {
            _ico.clear();
            return;
        }
        // normalizácia: vyhoď medzery a nečíselné znaky
        std::string x = digitsOnly(v);
        if (x.size() != 8 || x[0] == '0')
            throw std::invalid_argument("ICO must be exactly 8 digits and cannot start with 0 (or empty).");
        _ico = x;
    }
    const std::string &getICO() const { return _ico; }
    void setDIC(const std::string &v)
    {
        if (v.empty())
        {
            _dic.clear();
            return;
        }
        std::string x = digitsOnly(v);
        if (x.size() != 10)
            throw std::invalid_argument("DIC must be exactly 10 digits (or empty).");
        _dic = x;
    }
    const std::string &getDIC() const { return _dic; }
    void setIBAN(const IBAN &v) { _iban = v; }
    const IBAN &getIBAN() const { return _iban; }
    unsigned id() const { return _id; }
    void setId(unsigned v) { _id = v; }

private:
    static inline std::string digitsOnly(const std::string &s)
    {
        std::string out;
        out.reserve(s.size());
        for (unsigned char c : s)
            if (std::isdigit(c))
                out.push_back(char(c));
        return out;
    }

    // IStorable primitíva
    bool _checkDbStructure() const override;
    void _createDbStructure() override;

    void _insert() override;
    void _update() override;
    void _delete() override;
    void _load() override;
    bool _exists() const override;

    // dáta
    std::string _name;
    std::string _address;
    std::string _email;
    std::string _phone;
    std::string _ico;
    std::string _dic;
    IBAN _iban;
};
