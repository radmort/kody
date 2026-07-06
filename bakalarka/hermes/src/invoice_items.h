// invoice_items.h
// Hermes

#pragma once
#include <vector>
#include <cstdint>
#include <mutex>
#include "database.h"
#include "item.h"

class invoice_items
{
public:
    static void ensureSchema();

    // Pridá item na najbližšiu voľnú pozíciu (1,2,3,...)
    static void append(std::uint32_t invoiceId, std::uint32_t itemId);

    // Pridá item na konkrétnu pozíciu (ak koliduje s existujúcim, DB hodí error – MVP)
    static void add(std::uint32_t invoiceId, std::uint32_t itemId, std::uint32_t position);

    // Odstráni väzbu (podľa item_id)
    static void remove(std::uint32_t invoiceId, std::uint32_t itemId);

    // Zmaže všetky položky danej faktúry
    static void clear(std::uint32_t invoiceId);

    // Vráti položky faktúry v poradí (načítané cez Item::load)
    static std::vector<Item> listItems(std::uint32_t invoiceId);

private:
    static void _createSchema_();
    static bool _checkSchema_();
    static void _ensureOnce_();

    static std::once_flag s_once;
};
