// client.cpp
#include "client.h"
#include "database.h"
#include <stdexcept>
#include <iostream>
#include <string>

bool Client::_checkDbStructure() const
{
    try
    {
        auto &db = database::instance();
        (void)db.query("SELECT 1 FROM clients LIMIT 1;");
        return true;
    }
    catch (...)
    {
        std::cerr << "Client::_checkDbStructure: Table `clients` does not exist." << std::endl;
        return false;
    }
}

void Client::_createDbStructure()
{
    std::cout << "Creating table `clients`... ";
    auto &db = database::instance();
    const char *sql = R"SQL(
        CREATE TABLE IF NOT EXISTS clients (
        id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name    VARCHAR(255) NOT NULL,
        address TEXT,
        email   VARCHAR(255) NOT NULL, -- nemusi byt unique
        phone   VARCHAR(50),
        ico     CHAR(8),
        dic     CHAR(10),
        iban    VARCHAR(34),
        CONSTRAINT chk_ico CHECK (ico IS NULL OR ico REGEXP '^[1-9][0-9]{7}$'),
        CONSTRAINT chk_dic CHECK (dic IS NULL OR dic REGEXP '^[0-9]{10}$')
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        )SQL";
    try
    {
        db.exec(sql);
        std::cout << "OK" << std::endl;
    }
    catch (const std::exception &e)
    {
        std::cerr << "failed\n"
                  << e.what() << std::endl;
    }
}

bool Client::_exists() const
{
    if (_id == 0)
        return false;
    auto &db = database::instance();
    auto stmt = db.prepare("SELECT COUNT(1) FROM clients WHERE id=?");
    stmt.bind_uint(_id);
    stmt.result_uint();
    if (!stmt.fetch())
        return false;
    return stmt.get_uint(0) > 0;
}

void Client::_insert()
{
    auto &db = database::instance();

    auto stmt = db.prepare(
        "INSERT INTO clients(name,address,email,phone,ico,dic,iban) VALUES(?,?,?,?,?,?,?)");
    stmt.bind_string(_name);
    stmt.bind_nullable_string(_address.empty() ? nullptr : &_address);
    stmt.bind_nullable_string(_email.empty() ? nullptr : &_email);
    stmt.bind_nullable_string(_phone.empty() ? nullptr : &_phone);
    stmt.bind_nullable_string(_ico.empty() ? nullptr : &_ico);
    stmt.bind_nullable_string(_dic.empty() ? nullptr : &_dic);
    const std::string iban_s = _iban.getIBAN();
    stmt.bind_nullable_string(iban_s.empty() ? nullptr : &iban_s);
    stmt.execute();

    // získať ID (jednoducho)
    auto rid = db.query("SELECT LAST_INSERT_ID();");
    if (rid.empty() || rid[0].empty())
        throw std::runtime_error("Cannot fetch LAST_INSERT_ID()");
    _id = static_cast<unsigned>(std::stoul(rid[0][0]));
}

void Client::_update()
{
    if (_id == 0)
        throw std::logic_error("Update called with id=0");
    auto &db = database::instance();

    auto stmt = db.prepare(
        "UPDATE clients SET name=?,address=?,email=?,phone=?,ico=?,dic=?,iban=? WHERE id=?");
    stmt.bind_string(_name);
    stmt.bind_nullable_string(_address.empty() ? nullptr : &_address);
    stmt.bind_string(_email);
    stmt.bind_nullable_string(_phone.empty() ? nullptr : &_phone);
    stmt.bind_nullable_string(_ico.empty() ? nullptr : &_ico);
    stmt.bind_nullable_string(_dic.empty() ? nullptr : &_dic);
    const std::string iban_s = _iban.getIBAN();
    stmt.bind_nullable_string(iban_s.empty() ? nullptr : &iban_s);
    stmt.bind_uint(_id);
    stmt.execute();
}

void Client::_load()
{
    if (_id == 0)
        throw std::logic_error("Load called with id=0");
    auto &db = database::instance();

    // query() namiesto prepared + viacnásobného result_string – rovnaký problém ako pri fetch_pending
    // (zlé mapovanie stĺpcov → „Column is not string“ / heap corruption).
    std::string sql =
        "SELECT name,address,email,phone,ico,dic,iban FROM clients WHERE id="
        + std::to_string(_id);
    auto rows = db.query(sql);
    if (rows.empty() || rows[0].size() < 7)
        throw std::runtime_error("Client not found");

    const auto &r = rows[0];
    _name    = r[0];
    _address = r[1];
    _email   = r[2];
    _phone   = r[3];
    _ico     = r[4];
    _dic     = r[5];
    _iban    = IBAN(r[6]);
}

void Client::_delete()
{
    if (_id == 0)
        return;
    auto &db = database::instance();
    auto stmt = db.prepare("DELETE FROM clients WHERE id=?");
    stmt.bind_uint(_id);
    stmt.execute();
    _id = 0;
}
