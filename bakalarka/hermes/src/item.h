// Item.h
#pragma once
#include <string>
#include <cstdint>
#include <mutex>
#include "IStorable.h"

enum class unit : std::uint8_t
{
    ks = 0,
    hod = 1
};

class Item : public IStorable
{
public:
    Item() = default;
    explicit Item(unsigned id) : _id(id) {}
    ~Item() override = default;

    // --- getters/setters ---
    std::uint32_t id() const { return _id; }
    void setId(unsigned v) { _id = v; }

    const std::string &name() const { return _name; }
    void setName(const std::string &v) { _name = v; }

    const std::string &desc() const { return _desc; }
    void setDesc(const std::string &v) { _desc = v; }

    const unit &getUnit() const { return _unit; }
    void setUnit(unit u) { _unit = u; }

    const std::int64_t &unitPriceCents() const { return _unitPriceCents; }
    void setUnitPriceCents(std::int64_t v) { _unitPriceCents = v; }

    const int &vatBp() const { return _vatBp; }
    void setVatBp(int v) { _vatBp = v; }

    const std::int64_t &number() const { return _number; }
    void setNumber(std::int64_t v)  { _number = v; }
    void setNumber(long long v)     { _number = static_cast<std::int64_t>(v); }
    void setNumber(double v)        { _number = static_cast<std::int64_t>(v); }

private:
    // --- IStorable primitives ---
    bool _checkDbStructure() const override;
    void _createDbStructure() override;

    void _insert() override;
    void _update() override;
    void _delete() override;
    bool _exists() const override;
    void _load() override;

    // --- data ---
    std::uint32_t _id = 0;
    std::string _name;
    std::string _desc;
    unit _unit = unit::ks;
    std::int64_t _unitPriceCents = 0; // v centoch
    int _vatBp = 0;                   // basis points (napr. 2000 = 20 %)
    std::int64_t _number = 10;        // pocet v desatinach, napr. 10 => 1.0, 25 => 2,5
};
