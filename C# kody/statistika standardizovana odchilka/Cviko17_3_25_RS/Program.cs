using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace Cviko17_3_25_RS
{
    class Program
    {
        static void Main(string[] args)
        {
            //vytvorte program ktory nacita N cisel z klavesnice
            // a vypocita ich aritmeticky priemer a standartnu odchylku.

            Console.WriteLine("Zadaj rozsah pola: ");
            int n= Convert.ToInt32(Console.ReadLine()); ;
            int[] pola = new int[n];

            Random nahodne = new Random();

            for(int i=0; i < pola.Length; i++)
            {
                Console.WriteLine("nahodne hodnoty z intervalu 100 az 300 ");
                pola[i] = nahodne.Next(100, 300);
                Console.WriteLine(pola[i]);
            }
            /*Console.WriteLine("Zadaj A,B,C: ");
            int a = Convert.ToInt32(Console.ReadLine());
            int b = Convert.ToInt32(Console.ReadLine());
            int c = Convert.ToInt32(Console.ReadLine());*/

            double avg = 0;
            avg = poleavg(pola);

            Console.WriteLine("Priemer: " + avg);



            double vys = polerozptyl(pola, avg);

            // Console.WriteLine("Suma stvorcov " + pocetnost);
            Console.WriteLine("Odchylka: " + Poleodchylka(pola));
            Console.ReadKey();
        }

        static double poleavg(int[] pola)
        {
            double avg = 0;
            for (int i = 0; i < pola.LongLength; i++)
            {
                avg += pola[i];
            }
           return  avg / pola.Length;
        }

        static double polerozptyl(int[] pola, double avg)
        {
            double pocetnost = 0;
            for (int i = 0; i < pola.Length; i++)
            {
                pocetnost += Math.Pow((avg - pola[i]), 2);
            }

            return Math.Sqrt(pocetnost * (1.0 / (pola.Length - 1)));

        }

        static double Polerozptyl(int [] pole)
        {
            return polerozptyl(pole, poleavg(pole));
        }

        static double Poleodchylka (int[] pole)
        {
            return Math.Sqrt(polerozptyl(pole, poleavg(pole)));
        }
    }
}
