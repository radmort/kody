// layout.h
#pragma once
#include <string>
#include <vector>

struct Layout
{
    float L = 50.f, R = 545.f, B = 20.f, T = 782.f;

    std::string font;
    std::string fontBold;

    struct Point
    {
        float x = 10.f;
        float y = 10.f;
    };

    struct FontSize
    {
        float label = 11.f;
        float value = 10.f;
    };

    struct Table
    {
        Point header; // header.x, header.y
        float rowStartY = 358.f;
        float rowLineHeight = 14.f;
        float col_no = 50.f;
        float col_name = 80.f;
        float col_qty = 330.f;
        float col_unit = 370.f;
        float col_unit_price = 430.f;
        float col_total = 500.f;
    } table;

    struct Head
    {
        FontSize font; // font.label, font.value
        Point supplier;
        Point buyer; // JSON podporí aj kľúč "client"
    } head;

    struct Dates
    {
        FontSize font; // font.label, font.value
        Point issue;
        Point due;
    } dates;

    struct Payment
    {
        Point header; // napr. „Platobné údaje“/IBAN blok
    } payment;

    struct Summary
    {
        float xLabel = 350.f;
        float xValue = 500.f;
    } summary;

    struct QrPos
    {
        float x = 100.f;
        float y = 36.f;
        float scale = 2.0f;
    };
    struct QrGroup
    {
        FontSize font; // font.label použijeme na popisky QR
        QrPos payme;
        QrPos epc;
    } qr;

    static Layout loadFromJson(const std::string &path, float pageW, float pageH);
};
