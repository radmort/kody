// IStorable.h
// This file is part of the Hermes project.
#pragma once

class IStorable
{
public:
    virtual ~IStorable() = default;
    void save()
    {
        ensureDbStructure();
        _exists() ? _update() : _insert();
    }
    void load()
    {
        ensureDbStructure();
        _load();
    }
    void remove()
    {
        ensureDbStructure();
        _delete();
    }
    virtual void setId(unsigned v) { _id = v; }

protected:
    std::uint32_t _id = 0;
    void ensureDbStructure()
    {
        if (_structureEnsured)
            return;
        if (!_checkDbStructure())
        {
            _createDbStructure();
            _structureEnsured = true;
        }
    }

private:
    bool _structureEnsured = false;

    virtual bool _checkDbStructure() const = 0; // Skontroluje, či tabuľka existuje a má správnu štruktúru
    virtual void _createDbStructure() = 0;      // Vytvorí tabuľku

    virtual void _insert() = 0;       // Insert new record into the database
    virtual void _update() = 0;       // Update existing record in the database
    virtual void _delete() = 0;       // Delete record from the database
    virtual void _load() = 0;         // Load record from the database
    virtual bool _exists() const = 0; // Check if the record exists in the database
};
