// invoice_items.cpp
// Hermes
#include "invoice_items.h"
#include "item.h"
#include <stdexcept>
#include <iostream>

std::once_flag invoice_items::s_once;

void invoice_items::_createSchema_()
{
    auto &db = database::instance();
    const char *sql = R"SQL(
        CREATE TABLE IF NOT EXISTS invoice_items (
          invoice_id INT UNSIGNED NOT NULL,
          item_id    INT UNSIGNED NOT NULL,
          position   INT UNSIGNED NOT NULL,
          PRIMARY KEY (invoice_id, position),
          UNIQUE KEY uniq_invoice_item (invoice_id, item_id),
          CONSTRAINT chk_pos CHECK (position > 0),
          CONSTRAINT fk_iitems_inv  FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
          CONSTRAINT fk_iitems_item FOREIGN KEY (item_id)    REFERENCES items(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    )SQL";
    db.exec(sql);
}

bool invoice_items::_checkSchema_()
{
    try
    {
        auto &db = database::instance();
        (void)db.query("SELECT 1 FROM invoice_items LIMIT 1;");
        return true;
    }
    catch (...)
    {
        return false;
    }
}

void invoice_items::_ensureOnce_()
{
    std::call_once(s_once, []
                   {
        if (!_checkSchema_()) _createSchema_(); });
}

void invoice_items::ensureSchema() { _ensureOnce_(); }

void invoice_items::append(std::uint32_t invoiceId, std::uint32_t itemId)
{
    _ensureOnce_();
    auto &db = database::instance();

    // Zisti ďalšiu pozíciu: COALESCE(MAX(position),0)+1
    {
        auto stmt = db.prepare("SELECT COALESCE(MAX(position),0) FROM invoice_items WHERE invoice_id=?");
        stmt.bind_uint(invoiceId);
        stmt.result_uint();
        if (!stmt.fetch())
            throw std::runtime_error("append(): SELECT MAX(position) failed");
        unsigned nextPos = stmt.get_uint(0) + 1;

        auto ins = db.prepare(
            "INSERT INTO invoice_items(invoice_id, item_id, position) VALUES(?,?,?)");
        ins.bind_uint(invoiceId);
        ins.bind_uint(itemId);
        ins.bind_uint(nextPos);
        ins.execute();
    }
}

void invoice_items::add(std::uint32_t invoiceId, std::uint32_t itemId, std::uint32_t position)
{
    _ensureOnce_();
    auto &db = database::instance();
    auto ins = db.prepare(
        "INSERT INTO invoice_items(invoice_id, item_id, position) VALUES(?,?,?)");
    ins.bind_uint(invoiceId);
    ins.bind_uint(itemId);
    ins.bind_uint(position);
    ins.execute();
}

void invoice_items::remove(std::uint32_t invoiceId, std::uint32_t itemId)
{
    _ensureOnce_();
    auto &db = database::instance();
    auto del = db.prepare(
        "DELETE FROM invoice_items WHERE invoice_id=? AND item_id=?");
    del.bind_uint(invoiceId);
    del.bind_uint(itemId);
    del.execute();
}

void invoice_items::clear(std::uint32_t invoiceId)
{
    _ensureOnce_();
    auto &db = database::instance();
    auto del = db.prepare(
        "DELETE FROM invoice_items WHERE invoice_id=?");
    del.bind_uint(invoiceId);
    del.execute();
}

std::vector<Item> invoice_items::listItems(std::uint32_t invoiceId)
{
    _ensureOnce_();
    auto &db = database::instance();
    std::vector<Item> out;

    // 1) Získaj item_id v správnom poradí
    auto stmt = db.prepare(
        "SELECT item_id FROM invoice_items WHERE invoice_id=? ORDER BY position ASC");
    stmt.bind_uint(invoiceId);
    stmt.result_uint();

    while (stmt.fetch())
    {
        unsigned itemId = stmt.get_uint(0);

        // 2) Načítaj konkrétny Item cez IStorable API (MVP jednoduchosť)
        Item it;
        it.setId(itemId);
        it.load(); // využije tvoje Item::_load()

        out.emplace_back(std::move(it));
    }
    return out;
}
