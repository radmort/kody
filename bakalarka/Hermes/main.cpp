// // main.cpp
// // This file is part of the Hermes project.
// #include <iostream>
// #include <iomanip>
// #include <cstdint>
// #include <sstream>

// #include "invoice.h"
// #include "item.h"
// #include "client.h"
// #include "iban.h"

// static std::string euros(std::int64_t cents)
// {
//     const bool neg = cents < 0;
//     std::int64_t v = neg ? -cents : cents;
//     std::ostringstream os;
//     os << (neg ? "-" : "") << (v / 100) << '.' << std::setw(2) << std::setfill('0') << (v % 100);
//     return os.str();
// }

// int main()
// {
//     bool running = true;
//     std::cout << "Welcome to the Hermes Invoice System!\n";
//     std::cout << "-------------------------------------\n";
//     while (running) {
//         std::cout << "Please select an option:\n";
//         std::cout << "1. Create a new invoice\n";
//         std::cout << "2. View existing invoices\n";
//         std::cout << "3. Create a new client\n";
//         std::cout << "4. View existing clients\n";
//         std::cout << "0. Exit\n";
//         std::cout << "Your choice: ";

//         int choice;
//         std::cin >> choice;

//         switch (choice) {
//             case 1:
//                 // Code to create a new invoice
//                 break;
//             case 2:
//                 // Code to view existing invoices
//                 break;
//             case 3:
//                 // Code to create a new client
//                 break;
//             case 4:
//                 // Code to view existing clients
//                 break;
//             case 0:
//                 running = false;
//                 break;
//             default:
//                 std::cout << "Invalid choice, please try again.\n";
//                 break;
//         }
//     }
//     {
//         /* code */
//     }

//     return 0;
// }
#include <iostream>
#include "invoice.h"
#include "item.h"
#include "printing.h"

int main()
{
    Invoice inv;
    inv.setNumber("2025-0002");
    inv.setIssueDate("2025-08-20");
    inv.setDueDate("2025-09-03");
    inv.setCurrency("EUR");

    Item i1;
    i1.setName("Služba A");
    i1.setUnitPriceCents(1999);
    i1.setNumber(15);
    i1.setVatBp(2000); // 1.5 ks
    Item i2;
    i2.setName("Služba B");
    i2.setUnitPriceCents(5000);
    i2.setNumber(10);
    i2.setVatBp(2000); // 1.0 ks
    inv.addItem(i1);
    inv.addItem(i2);

    Party seller{
        "Ing. Dušan Horváth",
        "Golianova 23, 91702 Trnava",
        "50761773", "1081429888", "",
        "SK9511000000002943217802", "Tatra banka",
        "info@dusanhorvath.sk", "+421 910 160 112"};
    Party buyer{
        "Hanss, s.r.o.",
        "Jeruzalemská 304/13, 91701 Trnava",
        "51853469", "2120810252", "SK2120810252",
        "", "", "petergerek001@gmail.com", ""};

    inv.printToPDF("invoice.pdf", buyer, "VS:20250002");
    std::cout << "invoice.pdf ready\n";
    return 0;
}
