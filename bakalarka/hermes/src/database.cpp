#include "database.h"
#include <stdexcept>
#include <sstream>
#include <cstring>

// --- core ---

database &database::instance()
{
    static database s;
    return s;
}

database::database() = default;

database::~database()
{
    std::lock_guard<std::mutex> lk(_mtx);
    if (_conn)
    {
        mysql_close(_conn);
        _conn = nullptr;
    }
}

void database::setConfig(std::string host, uint16_t port,
                         std::string db, std::string user, std::string pass)
{
    std::lock_guard<std::mutex> lk(_mtx);
    _host = std::move(host);
    _port = port;
    _dbname = std::move(db);
    _user = std::move(user);
    _pass = std::move(pass);
}

bool database::isConnected() const
{
    std::lock_guard<std::mutex> lk(_mtx);
    return _conn != nullptr;
}

void database::connect()
{
    std::lock_guard<std::mutex> lk(_mtx);
    if (_conn)
        return;
    _conn = mysql_init(nullptr);
    if (!_conn)
        throw std::runtime_error("mysql_init() failed");
    mysql_options(_conn, MYSQL_SET_CHARSET_NAME, "utf8mb4");
    if (!mysql_real_connect(_conn, _host.c_str(), _user.c_str(), _pass.c_str(),
                            _dbname.c_str(), _port, nullptr, 0))
    {
        throwLastError("mysql_real_connect");
    }
}

void database::ensureConnectedUnlocked()
{
    if (!_conn)
        throw std::runtime_error("DB not connected");
}

void database::exec(const std::string &sql)
{
    std::lock_guard<std::mutex> lk(_mtx);
    ensureConnectedUnlocked();
    if (mysql_query(_conn, sql.c_str()) != 0)
        throwLastError("exec");
}

std::vector<std::vector<std::string>> database::query(const std::string &sql)
{
    std::lock_guard<std::mutex> lk(_mtx);
    ensureConnectedUnlocked();
    if (mysql_query(_conn, sql.c_str()) != 0)
        throwLastError("query");
    MYSQL_RES *res = mysql_store_result(_conn);
    if (!res)
        throwLastError("store_result");
    std::vector<std::vector<std::string>> out;
    unsigned n = mysql_num_fields(res);
    for (MYSQL_ROW row; (row = mysql_fetch_row(res));)
    {
        unsigned long *lens = mysql_fetch_lengths(res);
        std::vector<std::string> r;
        r.reserve(n);
        for (unsigned i = 0; i < n; ++i)
            r.emplace_back(row[i] ? std::string(row[i], lens[i]) : std::string{});
        out.emplace_back(std::move(r));
    }
    mysql_free_result(res);
    return out;
}

std::string database::escape(const std::string &s)
{
    std::lock_guard<std::mutex> lk(_mtx);
    ensureConnectedUnlocked();
    std::string out;
    out.resize(s.size() * 2 + 1);
    unsigned long n = mysql_real_escape_string(_conn, out.data(), s.data(), s.size());
    out.resize(n);
    return out;
}

[[noreturn]] void database::throwLastError(const char *where) const
{
    std::ostringstream os;
    os << where << " failed (" << (_conn ? mysql_errno(_conn) : 0) << ") "
       << (_conn ? mysql_error(_conn) : "no connection");
    throw std::runtime_error(os.str());
}

// --- Prepared ---

database::Prepared::Prepared(database &db, const std::string &sql) : _db(db)
{
    std::lock_guard<std::mutex> lk(_db._mtx);
    _db.ensureConnectedUnlocked();
    _stmt = mysql_stmt_init(_db._conn);
    if (!_stmt)
        throw std::runtime_error("mysql_stmt_init failed");
    if (mysql_stmt_prepare(_stmt, sql.c_str(), (unsigned long)sql.size()) != 0)
        _throwStmtError("mysql_stmt_prepare");
    _executed = false;
    _params.clear();
    _results.clear();
    _str_param_storage.clear();
    _str_param_len.clear();
    _param_is_null.clear();
    _str_result_storage.clear();
    _str_result_len.clear();
    _result_is_null.clear();
    _uint_result_storage.clear();
    _int_result_storage.clear();
    _bigint_result_storage.clear();
    _double_result_storage.clear();
    _uint_param_storage.clear();
    _int_param_storage.clear();
    _bigint_param_storage.clear();
    _double_param_storage.clear();
    _col_kind.clear();
    _col_offset.clear();
}

database::Prepared::~Prepared()
{
    if (_stmt)
        mysql_stmt_close(_stmt);
}

[[noreturn]] void database::Prepared::_throwStmtError(const char *where)
{
    std::ostringstream os;
    os << where << " failed (" << mysql_stmt_errno(_stmt) << ") " << mysql_stmt_error(_stmt);
    throw std::runtime_error(os.str());
}

// --------------------- PARAM BINDS ---------------------

void database::Prepared::bind_uint(std::uint32_t v)
{
    MYSQL_BIND b{};
    b.buffer_type = MYSQL_TYPE_LONG;
    b.is_unsigned = 1;
    _uint_param_storage.push_back(v);
    b.buffer = &_uint_param_storage.back();
    _params.push_back(b);
}

void database::Prepared::bind_int(int v)
{
    MYSQL_BIND b{};
    b.buffer_type = MYSQL_TYPE_LONG;
    b.is_unsigned = 0;
    _int_param_storage.push_back(v);
    b.buffer = &_int_param_storage.back();
    _params.push_back(b);
}

void database::Prepared::bind_bigint(std::int64_t v)
{
    MYSQL_BIND b{};
    b.buffer_type = MYSQL_TYPE_LONGLONG;
    b.is_unsigned = 0;
    _bigint_param_storage.push_back((long long)v);
    b.buffer = &_bigint_param_storage.back();
    _params.push_back(b);
}

void database::Prepared::bind_double(double v)
{
    MYSQL_BIND b{};
    b.buffer_type = MYSQL_TYPE_DOUBLE;
    _double_param_storage.push_back(v);
    b.buffer = &_double_param_storage.back();
    _params.push_back(b);
}

void database::Prepared::bind_string(const std::string &s)
{
    MYSQL_BIND b{};
    b.buffer_type = MYSQL_TYPE_STRING;
    if (s.empty())
    {
        _str_param_len.push_back(0);
        b.length = &_str_param_len.back();
        b.buffer = nullptr; // <<<
        b.buffer_length = 0;
    }
    else
    {
        auto arr = std::make_unique<char[]>(s.size());
        std::memcpy(arr.get(), s.data(), s.size());
        _str_param_len.push_back((unsigned long)s.size());
        b.length = &_str_param_len.back();
        b.buffer = arr.get();
        b.buffer_length = (unsigned long)s.size();
        _str_param_storage.push_back(std::move(arr));
    }
    _params.push_back(b);
}

void database::Prepared::bind_nullable_string(const std::string *s)
{
    MYSQL_BIND b{};
    b.buffer_type = MYSQL_TYPE_STRING;
    _param_is_null.push_back(s ? (my_bool)0 : (my_bool)1);
    b.is_null = &_param_is_null.back();
    if (s)
    {
        auto arr = std::make_unique<char[]>(s->size());
        std::memcpy(arr.get(), s->data(), s->size());
        _str_param_len.push_back((unsigned long)s->size());
        b.length = &_str_param_len.back();
        b.buffer = arr.get();
        b.buffer_length = (unsigned long)s->size();
        _str_param_storage.push_back(std::move(arr));
    }
    else
    {
        b.buffer = nullptr;
        b.buffer_length = 0;
    }
    _params.push_back(b);
}

void database::Prepared::bind_nullable_int()
{
    MYSQL_BIND b{};
    b.buffer_type = MYSQL_TYPE_LONG;
    b.is_unsigned = 0;
    _param_is_null.push_back((my_bool)1);
    b.is_null = &_param_is_null.back();
    b.buffer = nullptr;
    b.buffer_length = 0;
    _params.push_back(b);
}
void database::Prepared::bind_nullable_bigint()
{
    MYSQL_BIND b{};
    b.buffer_type = MYSQL_TYPE_LONGLONG;
    b.is_unsigned = 0;
    _param_is_null.push_back((my_bool)1);
    b.is_null = &_param_is_null.back();
    b.buffer = nullptr;
    b.buffer_length = 0;
    _params.push_back(b);
}

void database::Prepared::_rebind_params()
{
    if (_params.empty())
        return;
    if (mysql_stmt_bind_param(_stmt, _params.data()) != 0)
        _throwStmtError("mysql_stmt_bind_param");
}

// --------------------- EXECUTE / FETCH ---------------------

void database::Prepared::execute()
{
    std::lock_guard<std::mutex> lk(_db._mtx);
    _rebind_params();
    if (mysql_stmt_execute(_stmt) != 0)
        _throwStmtError("mysql_stmt_execute");
    _executed = true;
}

bool database::Prepared::fetch()
{
    std::lock_guard<std::mutex> lk(_db._mtx);

    // auto-execute pri prvom fetch-i (pre SELECT-y)
    if (!_executed)
    {
        _rebind_params();
        if (mysql_stmt_execute(_stmt) != 0)
            _throwStmtError("mysql_stmt_execute");
        _executed = true;
    }

    if (!_results_bound && !_results.empty())
    {
        if (mysql_stmt_bind_result(_stmt, _results.data()) != 0)
            _throwStmtError("mysql_stmt_bind_result");
        _results_bound = true;
    }
    if (!_stored)
    {
        if (mysql_stmt_store_result(_stmt) != 0)
            _throwStmtError("mysql_stmt_store_result");
        _stored = true;
    }

    int rc = mysql_stmt_fetch(_stmt);
    if (rc == 0)
        return true;
    if (rc == MYSQL_NO_DATA)
        return false;
    if (rc == MYSQL_DATA_TRUNCATED)
        return true;
    _throwStmtError("mysql_stmt_fetch");
}

// --------------------- RESULT BINDS ---------------------

void database::Prepared::result_string(unsigned maxlen)
{
    MYSQL_BIND b{};
    b.buffer_type = MYSQL_TYPE_STRING;
    auto arr = std::make_unique<char[]>(maxlen);
    _str_result_storage.push_back(std::move(arr));
    _str_result_len.push_back(0);
    _result_is_null.push_back((my_bool)0);
    b.buffer = _str_result_storage.back().get();
    b.buffer_length = maxlen;
    b.length = &_str_result_len.back();
    b.is_null = &_result_is_null.back();
    _results.push_back(b);
    _col_kind.push_back(_ColKind::STR);
    _col_offset.push_back(_str_result_storage.size() - 1);
}

void database::Prepared::result_uint()
{
    MYSQL_BIND b{};
    b.buffer_type = MYSQL_TYPE_LONG;
    b.is_unsigned = 1;
    _uint_result_storage.push_back(0);
    b.buffer = &_uint_result_storage.back();
    _results.push_back(b);
    _col_kind.push_back(_ColKind::UINT);
    _col_offset.push_back(_uint_result_storage.size() - 1);
}

void database::Prepared::result_int()
{
    MYSQL_BIND b{};
    b.buffer_type = MYSQL_TYPE_LONG;
    b.is_unsigned = 0;
    _int_result_storage.push_back(0);
    b.buffer = &_int_result_storage.back();
    _results.push_back(b);
    _col_kind.push_back(_ColKind::INT);
    _col_offset.push_back(_int_result_storage.size() - 1);
}

void database::Prepared::result_bigint()
{
    MYSQL_BIND b{};
    b.buffer_type = MYSQL_TYPE_LONGLONG;
    b.is_unsigned = 0;
    _bigint_result_storage.push_back(0);
    b.buffer = &_bigint_result_storage.back();
    _results.push_back(b);
    _col_kind.push_back(_ColKind::BIG);
    _col_offset.push_back(_bigint_result_storage.size() - 1);
}

void database::Prepared::result_double()
{
    MYSQL_BIND b{};
    b.buffer_type = MYSQL_TYPE_DOUBLE;
    _double_result_storage.push_back(0.0);
    b.buffer = &_double_result_storage.back();
    _results.push_back(b);
    _col_kind.push_back(_ColKind::DBL);
    _col_offset.push_back(_double_result_storage.size() - 1);
}

// --------------------- GETTERS ---------------------

static inline void _assert_col_bounds(const std::vector<MYSQL_BIND> &res, unsigned col)
{
    if (col >= res.size())
        throw std::out_of_range("Invalid column index");
}

std::string database::Prepared::get_string(unsigned col) const
{
    _assert_col_bounds(_results, col);
    if (_col_kind[col] != _ColKind::STR)
        throw std::logic_error("Column is not string");
    if (_result_is_null[col])
        return {};
    auto idx = _col_offset[col];
    return std::string(_str_result_storage[idx].get(), _str_result_len[idx]);
}

unsigned database::Prepared::get_uint(unsigned col) const
{
    _assert_col_bounds(_results, col);
    if (_col_kind[col] != _ColKind::UINT)
        throw std::logic_error("Column is not uint");
    auto idx = _col_offset[col];
    return (unsigned)_uint_result_storage[idx];
}

int database::Prepared::get_int(unsigned col) const
{
    _assert_col_bounds(_results, col);
    if (_col_kind[col] != _ColKind::INT)
        throw std::logic_error("Column is not int");
    auto idx = _col_offset[col];
    return _int_result_storage[idx];
}

std::int64_t database::Prepared::get_bigint(unsigned col) const
{
    _assert_col_bounds(_results, col);
    if (_col_kind[col] != _ColKind::BIG)
        throw std::logic_error("Column is not bigint");
    auto idx = _col_offset[col];
    return (std::int64_t)_bigint_result_storage[idx];
}

double database::Prepared::get_double(unsigned col) const
{
    _assert_col_bounds(_results, col);
    if (_col_kind[col] != _ColKind::DBL)
        throw std::logic_error("Column is not double");
    auto idx = _col_offset[col];
    return _double_result_storage[idx];
}

bool database::Prepared::is_null(unsigned col) const
{
    _assert_col_bounds(_results, col);
    return _result_is_null[col];
}
