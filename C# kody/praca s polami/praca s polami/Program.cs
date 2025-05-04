using System;

namespace Cvicenie_Polia
{
    class Program
    {
        /// <summary>
        /// Vypíše zadane pole na obrazovku do riadku
        /// Táto funkcia je vytvorená pretože C# nemáme hotový žiadny spôsob vypíšu poľa, a v príklade ho budeme potrebovať
        /// </summary>
        /// <param name="pole">pole ktoré chceme vypísať</param>
        public static void vypisPole(int[] pole)
        {
            foreach (int prvok in pole)
            {
                Console.Write(prvok + " ");
            }
            Console.WriteLine();
        }

        /// <summary>
        /// Príklad práce s poľom a použitie funkcií z triedy Array
        /// Príklad kopírovania polí
        /// </summary>
        public static void Main(string[] args)
        {
            Console.WriteLine("Zadaj dĺžku poľa?");
            int rozmer = Convert.ToInt32((Console.ReadLine()));

            // Pri vytváraní poľa definujeme nový objekt.
            // Na rozdiel od C++ sú polia vždy alokované dynamicky, teda za behu programu
            int[] pole = new int[rozmer];
            int[] pole2 = new int[rozmer];

            // V C# pole vie ako je dlhé a nedovolí vám prístup mimo existujúce indexy.
            Console.WriteLine("Dĺžka poľa je " + pole.Length);
            Console.WriteLine("Dĺžka dlhého poľa je " + pole.LongLength);

            // Nové pole je vždy vynulované.
            Console.WriteLine("Nové pole:");
            vypisPole(pole);

            // S polom pracujeme ako vždy pomocou indexov. Pre generovanie náhodných čísel používame triedu Random
            Random nahodne = new Random();
            for (int i = 0; i < pole.Length; i++)
            {
                pole[i] = nahodne.Next(100 * rozmer); // Keďže dlžka pola je z klávesnice, maximálnu hodnotu meníme podľa poťtu čísel
            }

            Console.WriteLine("Pole náhodných čísel:");
            vypisPole(pole);

            // Trieda Array obsahuje niekoľko funkcií ktoré nám ulahčia základné operácie s poľom
            // Treba poznamenať že sú vhodné najme pre jednorozmerná polia, pri maticiach je ich použitie problematické

            // Pre zoradenie poľa môžeme použiť metódu Sort
            Array.Sort(pole);

            Console.WriteLine("Zoradené pole:");
            vypisPole(pole);

            // Pre obrátenie poradia prvkov v poli máme metódu Reverse
            Array.Reverse(pole);

            Console.WriteLine("Obrátené pole:");
            vypisPole(pole);

            //
            // Vyhľadávanie
            //

            // Pre vyhľadávanie v poli existuje viacero funkcií
            // Vzhľadom na to že máme číselne pole, vybral som funkcie ktoré vracajú indexy (vedia vracať aj hodnoty)
            Console.WriteLine("Zadaj číslo ktoré chceme nájsť v poli:");
            int cislo = Convert.ToInt32((Console.ReadLine()));

            // Funkcia IndexOf nájde prvý výskyt čísla v poli
            // Druhý parameter musí byť rovnakého typu ako sú prvky poľa, v našom prípade hľadané číslo
            int index = Array.IndexOf(pole, cislo);
            if (index == -1)
            {
                // Nenájdený index je z historických dôvodov -1
                Console.WriteLine("Zadané číslo nebolo nájdené...");
            }
            else
            {
                Console.WriteLine("Číslo je na pozícií: " + index);
                Console.WriteLine("Kontrola: " + pole[index]);
            }

            // Funkcie FindIndex robí to isté či funkcia IndexOf, rozdiel je vo forme zápisu
            // Pre hľadanie zhody používa tzv. Lambda výraz
            //     (https://docs.microsoft.com/cs-cz/dotnet/csharp/programming-guide/statements-expressions-operators/lambda-expressions)
            // Použitie lambda výrazu umožňuje omnoho väčšiu flexibilitu, napr. prvok => prvok % cislo
            int find = Array.FindIndex(pole, prvok => prvok == cislo);
            if (find == -1)
            {
                // Nenájdený index je z historických dôvodov -1
                Console.WriteLine("Zadané číslo nebolo nájdené...");
            }
            else
            {
                Console.WriteLine("Číslo je na pozícií: " + find);
                Console.WriteLine("Kontrola: " + pole[find]);
            }

            // V špecifických prípadoch niekedy potrebujeme pole vynulovať
            Array.Clear(pole, 0, pole.Length);

            Console.WriteLine("Prázdne pole:");
            vypisPole(pole);

            //
            // Kopírovanie polí
            //

            // Pre polia v C# je špecifické ich predávanie referenciu, aj keď ju nikde nedefinujeme
            int[] cpole1 = { 2, 4, 6, 8, 10, 12 };
            int[] cpole2 = new int[6];

            // Pri štandardnom priradení sa vytvorí referencia
            // Obidve polia odkazujú na  ten istý objekt v pamäti
            cpole2 = cpole1;

            // Pre skutočné skopírovanie obsahu poľa, môžeme použiť metódu Copy
            // Riadok „cpole2 = cpole1;“ treba zakomentovať
            //Array.Copy(pole1, pole2, pole1.Length);

            // Obrátime cpole1 aby sme spravili nejakú zmenu
            Array.Reverse(cpole1);

            // Vypísanie obidvoch polí
            Console.WriteLine("Kopírovanie polí: cpole1:");
            vypisPole(cpole1);
            Console.WriteLine("Kopírovanie polí: cpole2:");
            vypisPole(cpole2);


            Console.Write("Press any key to continue . . . ");
            Console.ReadKey(true);

        }
    }
}
