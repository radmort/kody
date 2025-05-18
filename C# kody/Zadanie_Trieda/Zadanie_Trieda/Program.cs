namespace Matice_diagonaly
{
    class DiagonalyMatice
    {
        private int[,] matica;

        public DiagonalyMatice(int velkost)
        {
            this.matica = new int[velkost, velkost];
            NaplnMaticuNahodnymiCislami();
        }

        private void NaplnMaticuNahodnymiCislami()
        {
            Random rand = new Random();
            for (int i = 0; i < matica.GetLength(0); i++)
            {
                for (int j = 0; j < matica.GetLength(1); j++)
                {
                    matica[i, j] = rand.Next(1, 100);
                }
            }
        }

        public int[,] GetMatica()
        {
            return this.matica;
        }

        public int SucetHlavnejDiagonaly()
        {
            int sucet = 0;
            for (int i = 0; i < matica.GetLength(0); i++)
            {
                sucet += matica[i, i];
            }
            return sucet;
        }

        public int SucetVedlajsejDiagonaly()
        {
            int sucet = 0;
            int velkost = matica.GetLength(0);
            for (int i = 0; i < velkost; i++)
            {
                sucet += matica[i, velkost - 1 - i];
            }
            return sucet;
        }
    }

    class Program
    {
        static void VypisMaticu(int[,] matica)
        {
            Console.WriteLine("matica: ");
            for (int i = 0; i < matica.GetLength(0); i++)
            {
                for (int j = 0; j < matica.GetLength(1); j++)
                {
                    Console.Write(" {0,2} ", matica[i, j]);
                }
                Console.WriteLine();
            }
            Console.WriteLine();
        }

        static int VelkostMatice()
        {
        opravchyb:
            Console.WriteLine("Zadajte velkost matice (max 10): ");
            try
            {
                int size = Convert.ToInt32(Console.ReadLine());
                if (size < 1 || size > 10)
                {
                    Console.WriteLine("Velkost matice musi byt medzi 1 a 10.");
                    goto opravchyb;
                }
                return size;
            }
            catch (FormatException)
            {
                Console.WriteLine("Neplatny vstup. Zadajte cele cislo.");
                goto opravchyb;
            }
        }

        static void Main(string[] args)
        {
            int size = VelkostMatice();
            DiagonalyMatice diagonaly = new DiagonalyMatice(size);
            int[,] matica = diagonaly.GetMatica();
            VypisMaticu(matica);

            int sucetHlavnej = diagonaly.SucetHlavnejDiagonaly();
            int sucetVedlajsej = diagonaly.SucetVedlajsejDiagonaly();

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