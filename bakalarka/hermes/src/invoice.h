// invoice.h
// This file is part of the Hermes project.
#pragma once
#include <string>
#include <vector>
#include <cstdint>
#include <mutex>
#include "item.h"
#include "client.h"
#include "IStorable.h"
#include "printing.h" // pre Party

class Invoice : public IStorable
{
public:
    Invoice() = default;
    ~Invoice() override = default;

private:
    // IStorable primitives
    bool _checkDbStructure() const override;
    void _createDbStructure() override;

    void _insert() override;
    void _update() override;
    void _delete() override;
    bool _exists() const override;
    void _load() override;

public:
    // Fluent: načítaj podľa id
    Invoice &loadID(unsigned id)
    {
        setId(id);
        load();
        return *this;
    }

    // Položky v pamäti (po volaní loadItems() sú sync s DB)
    const std::vector<Item> &items() const { return _items; }
    void addItem(const Item &it) { _items.push_back(it); } // len do RAM (nepíše do DB)

    // CRUD nad väzbami (schované „service“)
    void appendItemId(std::uint32_t itemId);                 // dá na koniec (position = max+1)
    void addItemAt(std::uint32_t itemId, std::uint32_t pos); // konkrétna pozícia (bez posunov)
    void removeItem(std::uint32_t itemId);                   // zmaže väzbu
    void clearItems();                                       // zmaže všetky väzby
    void loadItems();                                        // načíta _items z DB (JOIN-om cez id->Item::load)

    // Výpočty zo zoznamu _items v RAM
    std::int64_t totalBaseCents() const noexcept;
    std::int64_t totalWithVatCents() const noexcept;

    // Meta (DB fields)
    void setClientId(std::uint32_t v) { _clientId = v; }
    std::uint32_t clientId() const { return _clientId; }

    void setClient(const Client &c) { _client = c; }
    const Client &getClient() const { return _client; }

    void setIssueDate(const std::string &d) { _issueDate = d; }
    const std::string &issueDate() const { return _issueDate; }

    void setDueDate(const std::string &d) { _dueDate = d; }
    const std::string &dueDate() const { return _dueDate; }

    void setNumber(const std::string &n) { _number = n; }
    const std::string &number() const { return _number; }

    void setCurrency(const std::string &c) { _currency = c; }
    const std::string &currency() const { return _currency; }

    void setNotes(const std::string &n) { _notes = n; }
    const std::string &notes() const { return _notes; }

    // EPC pole 11: krátky text (číslo fa + suma); vs_raw = VS do tlače a odvodenie PI
    void printToPDF(const std::string &path,
                    const Party &buyer,
                    const SellerPdfInfo &seller,
                    const std::string &epc_remittance_unstructured,
                    const std::string &vs_raw);

private:
    // DB polia (stĺpce v `invoices`)
    std::uint32_t _clientId = 0;
    std::string _issueDate; // YYYY-MM-DD
    std::string _dueDate;   // YYYY-MM-DD
    std::string _number;    // číslo faktúry
    std::string _currency = "EUR";
    std::string _notes;

    // pomocný cache objekt (nenačítava sa automaticky v _load)
    Client _client;

    // položky v pamäti (synchro cez loadItems/appendItemId/…)
    std::vector<Item> _items;
};
