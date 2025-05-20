namespace Kalkulacka
{
    public partial class Kalkulačka : Form
    {
        Double result = 0;
        string enterFirstValue, enterSecondValue;
        string op = string.Empty;
        bool enterValue = false;
        public Kalkulačka()
        {
            InitializeComponent();
        }

        private void button1_Click(object sender, EventArgs e)
        {

        }

        private void panel1_Paint(object sender, PaintEventArgs e)
        {

        }

        private void button26_Click(object sender, EventArgs e)
        {

        }

        private void polevys_TextChanged(object sender, EventArgs e)
        {

        }

        private void button1_Click_1(object sender, EventArgs e)
        {

        }

        private void Btn_clikNum(object sender, EventArgs e)
        {
            if (disp1.Text == "0" || enterValue) disp1.Text = string.Empty;
            enterValue = false;
            Button num = (Button)sender;
            if (num.Text == ".")
            {
                if (!disp1.Text.Contains("."))
                {
                    disp1.Text = disp1.Text + num.Text;
                }
            }
            else
            {
                disp1.Text = disp1.Text + num.Text;
            }
        }

        private void numOpera(object sender, EventArgs e)
        {
            if (result != 0) btEqual.PerformClick();
            else result = Double.Parse(disp1.Text);

            Button num = (Button)sender;
            enterValue = true;
            op = num.Text;
            if (num.Text != "0")
            {
                disp2.Text = enterFirstValue = string.Format("{0}{1}", result, op);
                disp1.Text = string.Empty;
            }
            disp1.Text = "";
        }

        private void btEquals_clik(object sender, EventArgs e)
        {
            enterSecondValue = disp1.Text;
            disp2.Text = $"{disp2.Text}{disp1.Text}=";
            if (disp1.Text != string.Empty)
            {
                switch (op)
                {
                    case "+":
                        disp1.Text = (result + Double.Parse(enterSecondValue)).ToString();
                        txtHistory.AppendText(enterFirstValue + enterSecondValue + " = " + disp1.Text + "\n");
                        break;
                    case "-":
                        disp1.Text = (result - Double.Parse(enterSecondValue)).ToString();
                        txtHistory.AppendText(enterFirstValue + enterSecondValue + " = " + disp1.Text + "\n");
                        break;
                    case "*":
                        disp1.Text = (result * Double.Parse(enterSecondValue)).ToString();
                        txtHistory.AppendText(enterFirstValue + enterSecondValue + " = " + disp1.Text + "\n");
                        break;
                    case "/":
                        disp1.Text = (result / Double.Parse(enterSecondValue)).ToString();
                        txtHistory.AppendText(enterFirstValue + enterSecondValue + " = " + disp1.Text + "\n");
                        break;
                    default:
                        disp2.Text = string.Format("{0}=", disp1.Text);
                        break;
                }
                result = Double.Parse(disp1.Text);
                op = string.Empty;
            }

        }

        private void btHi_Click(object sender, EventArgs e)
        {
            if (paDe.Height == 5)
                paDe.Height = 400;
            else
                paDe.Height = 5;
        }

        private void btHClear_Click(object sender, EventArgs e)
        {
            txtHistory.Clear();
            if (txtHistory.Text == string.Empty)
            {
                txtHistory.AppendText("Historie je prázdná");
            }
        }

        private void btDEL_clik(object sender, EventArgs e)
        {
            if (disp1.Text.Length > 0)
            {
                disp1.Text = disp1.Text.Remove(disp1.Text.Length - 1, 1);
            }
            if (disp1.Text == string.Empty)
            {
                disp1.Text = "0";
            }
        }

        private void btC_Clik(object sender, EventArgs e)
        {
            disp1.Text = "0";
            result = 0;
            disp2.Text = string.Empty;
        }

        private void btCE_Clik(object sender, EventArgs e)
        {
            disp1.Text = "0";
        }

        private void Operaciabt(object sender, EventArgs e)
        {
            Button num = (Button)sender;
            op = num.Text;
            switch (op)
            {
                case "sin":
                    disp1.Text = Math.Sin(Double.Parse(disp1.Text)).ToString();
                    break;
                case "cos":
                    disp1.Text = Math.Cos(Double.Parse(disp1.Text)).ToString();
                    break;
                case "tan":
                    disp1.Text = Math.Tan(Double.Parse(disp1.Text)).ToString();
                    break;
                case "asin":
                    disp1.Text = Math.Asin(Double.Parse(disp1.Text)).ToString();
                    break;
                case "acos":
                    disp1.Text = Math.Acos(Double.Parse(disp1.Text)).ToString();
                    break;
                case "atan":
                    disp1.Text = Math.Atan(Double.Parse(disp1.Text)).ToString();
                    break;
                case "√x":
                    disp1.Text = Math.Sqrt(Double.Parse(disp1.Text)).ToString();
                    break;
                case "x^2":
                    disp1.Text = Math.Pow(Double.Parse(disp1.Text), 2).ToString();
                    break;
                case "log":
                    disp1.Text = Math.Log(Double.Parse(disp1.Text)).ToString();
                    break;

            }
        }
    }
}
