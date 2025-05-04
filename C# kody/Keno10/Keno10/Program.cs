using System;
using System.IO.Compression;

namespace Keno10
{
    internal class Program
    {
        static int[] Losovanie(int PocetCisel)
        {
            int[] losovanie_pole = new int[PocetCisel];
            Random nahodne = new Random();

            for (int i = 0; i < losovanie_pole.Length; i++)
            {
            kontro:
                losovanie_pole[i] = nahodne.Next(1, 81);
                for (int j = 0; j < i; j++)
                {
                    if (losovanie_pole[j] == losovanie_pole[i]) goto kontro;
                }

            }

            return losovanie_pole;
        }


        static int[] NacitajTipy(int PocetTipov)
        {
            int[] tipy_pole = new int[PocetTipov];

            for (int i = 0; i < tipy_pole.Length; i++)
            {
            kontrolazad:
                Console.WriteLine("zadaj typ na pozicii " + (i + 1) + ". od 1 po 80: ");
                tipy_pole[i] = Convert.ToInt32(Console.ReadLine());
                for(int j = 0;j < i; j++)
                {
                    if (tipy_pole[i] < 1 || tipy_pole[i] > 80)
                    {
                        Console.WriteLine("zly rozsah cisla");
                        goto kontrolazad;
                    }
                    else if (tipy_pole[i] == tipy_pole[j])
                    {
                        Console.WriteLine("uz zadane cislo");
                        goto kontrolazad;
                    }
                    else continue;
                }
            }

            return tipy_pole;
        }


        static void Main(string[] args)
        {
            Console.WriteLine("Zadaj počet typov aspon 1 max 10: ");
            int uzi = Convert.ToInt32((Console.ReadLine()));
            int[] vylosovane = Losovanie(20);
            int[] zadane = NacitajTipy(uzi);

            Console.WriteLine("Vyzrebovane cisla: ");

            foreach (int i in vylosovane)
            {
                Console.Write(i + " ");
            }
            Console.WriteLine();

            if(uzi == 1) Console.WriteLine("Typ uzivatela: ");
            else Console.WriteLine("Typy uzivatela: ");

            foreach (int i in zadane)
            {
                Console.Write(i + " ");
            }

            Console.WriteLine();

            int pocet = 0;
            for (int i = 0; i < vylosovane.Length; i++)
            {
                for (int j = 0; j < zadane.Length; j++)
                {
                    if(vylosovane[i] == zadane[j]) pocet++; 
                }
            }
            Console.WriteLine("Pocet spravnych typov: " + pocet);

            Console.ReadKey();
        }
    }
}
