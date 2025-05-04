using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace cviko4
{
    class Program
    {
        /// <summary>
        /// Funkcia ktora vypocita koren linearnej rovnice.
        /// </summary>
        /// <param name="a">Koeficient A</param>
        /// <param name="b">Koeficient B</param>
        /// <returns>Koren linearnej rovnice</returns>
     
        static double Linear(double a,double b)
        {
            return -b / a;
        }

        static void Main(string[] args)
        {
            //vytvorte program, ktory vypocita koren linearnej rovnice so zadanych koeficientov. Uvazujte so vsetkymi tromi alternativami.


            /*  double a, b, x;
              Console.WriteLine("zadaj A a B: ");
              a = Convert.ToDouble(Console.ReadLine());
              b = Convert.ToDouble(Console.ReadLine());

              x = Linear(a,b);
              if (double.IsNaN(x)) Console.WriteLine("riesenie su vsetky R cisla");
              else if (double.IsInfinity(x)) Console.WriteLine("nema riesenie");
              else Console.WriteLine("ma riesenie x= "+ x); */


            Console.WriteLine("zadaj n ");
            int n = Convert.ToInt32(Console.ReadLine()); 
            int[] pole= new int[n];
            double avg = 0;
            for (int i = 0; i < pole.Length; i++)
            {
                pole[i] = Convert.ToInt32(Console.ReadLine());
                avg += pole[i] ;
            }
               
            Console.WriteLine("Avg: "+ avg/ pole.Length);
            Console.ReadKey();
        }
    }
}
