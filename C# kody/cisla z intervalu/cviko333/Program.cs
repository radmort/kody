using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace cviko333
{
    class Program
    {
        static void Main(string[] args)
        {
            /* // vytvor program ktory, vypise cisla zo zadaneho intervalu zo zadaným krokom.
               // Hranice a krok bude mozne zadat po spusteni programu

               double zac, kon, krok;

               Console.WriteLine("zadaj hranice: ");
               zac = Convert.ToDouble(Console.ReadLine());
               kon = Convert.ToDouble(Console.ReadLine());
               krok = Convert.ToDouble(Console.ReadLine());

               for (double i = zac; i <= kon; i = i + krok)   // zadaj 10  1000 krok 1,1 (1.1 v konzole nieje def.)
               {
                   Console.Write(i.ToString("0.#") + "  ");
               } */

            //Vytvorte program ktory zo zadanej sumy pociatocneho vkladu,
            //percenta urocenia a konecnej sumy vypocita pocet rokov sporenia,
            // Vypis realizujte pre kazdy rok sporenia a na zaver vypiste dobu trvania
            // pouzi iny cyklus ako predtym.

            double zac, kon, urok;

            Console.WriteLine("zadaj hranice: ");
            Console.WriteLine("zadaj zaciatok: ");
            zac = Convert.ToDouble(Console.ReadLine());
            Console.WriteLine("zadaj koniec: ");
            kon = Convert.ToDouble(Console.ReadLine());
            Console.WriteLine("zadaj urok v %: ");
            urok = Convert.ToDouble(Console.ReadLine());

            int  rok = 0; 
            while (zac<= kon )
            {
                
                Console.WriteLine(rok + " suma: " + zac );
                double final = zac/urok;
                zac += Math.Round(final, 2  );
                rok++;

            }
            Console.WriteLine(rok + " suma: " + zac);

            Console.ReadKey();

        }
    }
}
