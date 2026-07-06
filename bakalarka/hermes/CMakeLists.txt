cmake_minimum_required(VERSION 3.20)
project(hermes VERSION 1.0.0 LANGUAGES CXX)

set(CMAKE_CXX_STANDARD          20)
set(CMAKE_CXX_STANDARD_REQUIRED ON)
set(CMAKE_CXX_EXTENSIONS        OFF)

add_compile_options(-Wall -Wextra -O2)

# ── Dependencies ──────────────────────────────────────────────────────────────
find_package(PkgConfig REQUIRED)
pkg_check_modules(MARIADB REQUIRED libmariadb)
pkg_check_modules(LIBCURL REQUIRED libcurl)

find_library(HPDF_LIB hpdf REQUIRED)
find_path(HPDF_INC   hpdf.h REQUIRED)

find_package(nlohmann_json 3.10 REQUIRED)
find_package(spdlog        REQUIRED)

# ── All source files ──────────────────────────────────────────────────────────
add_executable(billing_reminder
    src/main.cpp
    src/database.cpp
    src/client.cpp
    src/item.cpp
    src/invoice.cpp
    src/invoice_items.cpp
    src/printing.cpp
    src/layout-faktury.cpp
    src/iban.cpp
    src/epc2d.cpp
    src/extern/qrcode/qrcodegen.cpp
)

target_include_directories(billing_reminder PRIVATE
    src/
    src/extern/qrcode/
    ${MARIADB_INCLUDE_DIRS}
    ${LIBCURL_INCLUDE_DIRS}
    ${HPDF_INC}
)

target_link_libraries(billing_reminder PRIVATE
    ${MARIADB_LIBRARIES}
    ${LIBCURL_LIBRARIES}
    ${HPDF_LIB}
    nlohmann_json::nlohmann_json
    spdlog::spdlog
    stdc++fs
)

target_compile_options(billing_reminder PRIVATE
    ${MARIADB_CFLAGS_OTHER}
    ${LIBCURL_CFLAGS_OTHER}
)

install(TARGETS billing_reminder DESTINATION /usr/local/bin)
install(FILES   config/config.json DESTINATION /etc/hermes)
# layout pre PDF faktúr (billing_reminder načíta layout.json z prac. adresára alebo /etc/hermes)
install(FILES   config/layout.json DESTINATION /etc/hermes)
