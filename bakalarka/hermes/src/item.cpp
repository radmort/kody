// Item.cpp
#include "item.h"
#include "database.h"
#include <stdexcept>
#include <iostream>
#include <type_traits>

// enum ↔ integer
static inline std::uint8_t unit_to_u8(unit u)
{
    return static_cast<std::uint8_t>(u);
}

static inline unit u8_to_unit(unsigned v)
{
    switch (v)
    {
    case 0:
        return unit::ks;
    case 1:
        return unit::hod;
    default:
        return unit::ks; // fallback
    }
}

bool Item::_checkDbStructure() const
{
    try
    {
        auto &db = database::instance();
        (void)db.query("SELECT 1 FROM items LIMIT 1;");
        return true;
    }
    catch (...)
    {
        return false;
    }
}

void Item::_createDbStructure()
{
    auto &db = database::instance();
    // Jednoduchá tabuľka; FK na faktúry rieš v invoice_items
    const char *sql = R"SQL(
        CREATE TABLE IF NOT EXISTS items (
          id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          name              VARCHAR(255) NOT NULL,
          description       TEXT,
          unit              TINYINT UNSIGNED NOT NULL,  -- 0=ks,1=hod
          unit_price_cents  BIGINT NOT NULL,            -- celé centy
          vat_bp            INT NOT NULL,               -- basis points
          number            BIGINT NOT NULL             -- množstvo
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        )SQL";
    db.exec(sql);
}

bool Item::_exists() const
{
    if (_id == 0)
        return false;
    auto &db = database::instance();
    auto stmt = db.prepare("SELECT COUNT(1) FROM items WHERE id=?");
    stmt.bind_uint(_id);
    stmt.result_uint();
    if (!stmt.fetch())
        return false;
    return stmt.get_uint(0) > 0;
}

void Item::_insert()
{
    auto &db = database::instance();

    auto stmt = db.prepare("INSERT INTO items(name,description,unit,unit_price_cents,vat_bp,number) VALUES(?,?,?,?,?,?)");
    stmt.bind_string(_name);
    stmt.bind_nullable_string(_desc.empty() ? nullptr : &_desc);
    stmt.bind_uint(unit_to_u8(_unit));
    stmt.bind_bigint(_unitPriceCents);
    stmt.bind_uint(_vatBp);
    stmt.bind_bigint(_number);
    stmt.execute();

    // priraď id
    auto rid = db.query("SELECT LAST_INSERT_ID();");
    if (rid.empty() || rid[0].empty())
        throw std::runtime_error("Cannot fetch LAST_INSERT_ID()");
    _id = static_cast<unsigned>(std::stoul(rid[0][0]));
}

void Item::_update()
{
    if (_id == 0)
        throw std::logic_error("Update called with id=0");
    auto &db = database::instance();

    auto stmt = db.prepare(
        "UPDATE items SET name=?,description=?,unit=?,unit_price_cents=?,vat_bp=?,number=? "
        "WHERE id=?");
    stmt.bind_string(_name);
    stmt.bind_nullable_string(_desc.empty() ? nullptr : &_desc);
    stmt.bind_uint(unit_to_u8(_unit));
    stmt.bind_bigint(_unitPriceCents);
    stmt.bind_uint(_vatBp);
    stmt.bind_bigint(_number);
    stmt.bind_uint(_id);
    stmt.execute();
}

void Item::_load()
{
    if (_id == 0)
        throw std::logic_error("Load called with id=0");
    auto &db = database::instance();

    auto stmt = db.prepare(
        "SELECT name,description,unit,unit_price_cents,vat_bp,number "
        "FROM items WHERE id=?");
    stmt.bind_uint(_id);

    stmt.result_string(255);  // name
    stmt.result_string(2048); // description
    stmt.result_int();        // unit
    stmt.result_bigint();     // unit_price_cents
    stmt.result_int();        // vat_bp
    stmt.result_bigint();     // number

    if (!stmt.fetch())
        throw std::runtime_error("Item not found");

    _name = stmt.get_string(0);
    _desc = stmt.get_string(1);
    _unit = u8_to_unit((unsigned)stmt.get_int(2));
    _unitPriceCents = stmt.get_bigint(3);
    _vatBp = stmt.get_int(4);
    _number = stmt.get_bigint(5); // BIGINT, nie std::stod
}

void Item::_delete()
{
    if (_id == 0)
        return;
    auto &db = database::instance();
    auto stmt = db.prepare("DELETE FROM items WHERE id=?");
    stmt.bind_uint(_id);
    stmt.execute();
    _id = 0;
}
