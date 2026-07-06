#pragma once
#include <mysql/mysql.h>
#include <deque>
#include <mutex>
#include <string>
#include <vector>
#include <memory>
#include <cstdint>

class database
{
public:
    static database &instance();

    void setConfig(std::string host, uint16_t port,
                   std::string db, std::string user, std::string pass);
    void connect();
    bool isConnected() const;

    // Jednoduché API (legacy)
    void exec(const std::string &sql);
    std::vector<std::vector<std::string>> query(const std::string &sql);
    std::string escape(const std::string &s);

    // --- Prepared statements (RAII) ---
    class Prepared
    {
    public:
        Prepared(database &db, const std::string &sql);
        ~Prepared();

        // bind parametre (v poradí ?)
        void bind_uint(std::uint32_t v);
        void bind_int(int v);
        void bind_bigint(std::int64_t v);
        void bind_double(double v);
        void bind_string(const std::string &s);
        void bind_nullable_string(const std::string *s); // nullptr -> NULL
        void bind_nullable_int();                        // NULL pre INT/LONG
        void bind_nullable_bigint();                     // NULL pre BIGINT (ak budeš niekedy potrebovať)

        // non-SELECT
        void execute();

        // SELECT – definícia výsledkov a čítanie
        void result_string(unsigned maxlen);
        void result_int();
        void result_uint();
        void result_bigint();
        void result_double();
        bool fetch();

        std::string get_string(unsigned col) const;
        unsigned get_uint(unsigned col) const;
        int get_int(unsigned col) const;
        std::int64_t get_bigint(unsigned col) const;
        double get_double(unsigned col) const;
        bool is_null(unsigned col) const;

    private:
        database &_db;
        MYSQL_STMT *_stmt = nullptr;
        bool _results_bound = false, _stored = false;

        // params — deques keep stable addresses for MYSQL_BIND buffers (vector reallocation
        // invalidates pointers stored in earlier MYSQL_BIND entries → heap corruption).
        std::vector<MYSQL_BIND> _params{};
        std::vector<std::unique_ptr<char[]>> _str_param_storage{};
        std::deque<unsigned long> _str_param_len{};
        std::deque<my_bool> _param_is_null{};
        std::deque<std::uint32_t> _uint_param_storage{};
        std::deque<int> _int_param_storage{};
        std::deque<long long> _bigint_param_storage{};
        std::deque<double> _double_param_storage{};

        // results
        std::vector<MYSQL_BIND> _results{};
        std::vector<std::unique_ptr<char[]>> _str_result_storage{};
        std::deque<unsigned long> _str_result_len{};
        std::deque<my_bool> _result_is_null{};
        std::deque<unsigned> _uint_result_storage{};
        std::deque<std::int64_t> _bigint_result_storage{};
        std::deque<double> _double_result_storage{};
        std::deque<int> _int_result_storage{};
        enum class _ColKind
        {
            STR,
            UINT,
            INT,
            BIG,
            DBL
        };
        std::vector<_ColKind> _col_kind{}; // veľkosť = počet result_* volaní (t.j. stĺpcov)
        std::vector<size_t> _col_offset{}; // index v zodpovedajúcom *_result_storage

        bool _executed = false; // či už prebehol mysql_stmt_execute
        void _rebind_params();
        [[noreturn]] void _throwStmtError(const char *where);
    };

    Prepared prepare(const std::string &sql) { return Prepared(*this, sql); }

    database(const database &) = delete;
    database &operator=(const database &) = delete;
    ~database();

private:
    database();
    void ensureConnectedUnlocked();
    [[noreturn]] void throwLastError(const char *where) const;

    friend class database::Prepared; // Prepared potrebuje _mtx a _conn

    // config + stav
    std::string _host = "127.0.0.1", _dbname = "hermes_db", _user = "hermes", _pass = "pass";
    uint16_t _port = 3306;
    MYSQL *_conn = nullptr;
    mutable std::mutex _mtx;
};
