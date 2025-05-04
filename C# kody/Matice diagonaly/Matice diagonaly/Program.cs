namespace Matice_diagonaly
{
    internal class Program
    {

        static void VypisMaticu(int[,] matica)
        {
            Console.WriteLine("matica: ");
            for (int i = 0; i < matica.GetLength(0); i++)
            {
                for (int j = 0; j < matica.GetLength(0); j++)
                    Console.Write(" {0,2} ", matica[i, j]);
                Console.WriteLine();
            }
            Console.WriteLine();
        }
        static int VelkostMatice()
        {
        opravchyb:
            Console.WriteLine("Zadajte velkost matice (max 10): ");
            int size = Convert.ToInt32(Console.ReadLine());
            if (size < 1 || size > 10)
            {
                Console.WriteLine("Velkost matice  musi byt medzi 1 a 10.");
                goto opravchyb;
            }
            return size;
        }
        static int[,] plnMaticu(int size)
        {
            Random rand = new Random();
            int[,] matica = new int[size, size];
            for (int i = 0; i < size; i++)
                for (int j = 0; j < size; j++)
                    matica[i, j] = rand.Next(1, 100);
            return matica;
        }
        static int SucetHlDiag(int[,] matica)
        {
            int sucet = 0;
            for (int i = 0; i < matica.GetLength(0); i++)
                sucet += matica[i, i];
            return sucet;
        }

        static int SucetVlDiag(int[,] matica)
        {
            int sucet = 0;
            int size = matica.GetLength(0);
            for (int i = 0; i < size; i++)
                sucet += matica[i, size - 1 - i];
            return sucet;
        }
        static void Main(string[] args)
        {
            int size = VelkostMatice();
            int[,] matica = plnMaticu(size);
            VypisMaticu(matica);

            int sucetHlavnej = SucetHlDiag(matica);
            int sucetVedlajsej = SucetVlDiag(matica);

            Console.WriteLine("Sucet hlavnej diagonaly: " + sucetHlavnej);
            Console.WriteLine("Sucet vedlajsej diagonaly: " + sucetVedlajsej);

            if (sucetHlavnej > sucetVedlajsej)
                Console.WriteLine("Hlavna diagonala ma vacsi sucet.");
            else if (sucetHlavnej < sucetVedlajsej)
                Console.WriteLine("Vedlajsia diagonala ma vacsi sucet.");
            else
                Console.WriteLine("Obe diagonaly maju rovnaky sucet.");

            Console.WriteLine();
            Console.ReadKey();
        }
    }
}
