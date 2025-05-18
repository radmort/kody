using System;
using System.IO;

namespace KonverziaMerani
{
    class Program
    {
        static void Main(string[] args)
        {
            FileStream fsCitanie = null;
            StreamReader srCitanie = null;
            FileStream fsZapis = null;
            StreamWriter swZapis = null;

            try
            {
                fsCitanie = new FileStream("vstup.txt", FileMode.Open);
                srCitanie = new StreamReader(fsCitanie);

                fsZapis = new FileStream("vystup.txt", FileMode.Create);
                swZapis = new StreamWriter(fsZapis);

                string riadok = null;

                while ((riadok = srCitanie.ReadLine()) != null)
                {
                    string[] casti = riadok.Split(' ');
                    string datumCas = casti[0] + " " + casti[1];
                    int pocet = casti.Length - 2;
                    double[] hodnoty = new double[pocet];

                    for (int i = 0; i < pocet; i++)
                    {
                        try
                        {
                            hodnoty[i] = double.Parse(casti[i + 2].Replace(',', '.'));
                        }
                        catch (FormatException)
                        {
                            Console.WriteLine("Chybný formát čísla: " + casti[i + 2] + " na dátume: " + datumCas);
                            break;
                        }
                    }

                    double suma = 0;
                    for (int i = 0; i < pocet; i++)
                    {
                        suma += hodnoty[i];
                    }

                    double priemer = suma / pocet;
                    double priemerZaokruhleny = Math.Round(priemer,2);

                    swZapis.WriteLine(datumCas + "\t" + pocet + "\t" + priemerZaokruhleny.ToString().Replace('.', ','));
                }

                Console.WriteLine("Spracovanie dokončené.");
            }
            catch (FileNotFoundException)
            {
                Console.WriteLine("Vstupný súbor neexistuje.");
            }
            catch (IOException e)
            {
                Console.WriteLine("Chyba pri práci so súborom: " + e.Message);
            }
            catch (Exception e)
            {
                Console.WriteLine("Neočakávaná chyba: " + e.Message);
            }
            finally
            {
                if (swZapis != null) swZapis.Close();
                if (fsZapis != null) fsZapis.Close();
                if (srCitanie != null) srCitanie.Close();
                if (fsCitanie != null) fsCitanie.Close();
            }
        }
    }
}
