using System;
using System.IO;
using System.Windows.Forms;

namespace Kalkulacka
{
    public partial class Kalkulačka : Form
    {
        double memory = 0;
        Double result = 0;
        string enterFirstValue, enterSecondValue;
        string op = string.Empty;
        bool enterValue = false;


        string historyFilePath = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.Desktop), "kalkulacka_historia.txt");

        public Kalkulačka()
        {
            InitializeComponent();
            File.WriteAllText(historyFilePath, string.Empty);
            this.KeyPreview = true;
            this.AcceptButton = null;
            this.KeyDown += new KeyEventHandler(Kalkulačka_KeyDown);
        }
        private void Kalkulačka_Load(object sender, EventArgs e)
        {
            UpdateMemoryButton();
        }
        private void Kalkulačka_KeyDown(object sender, KeyEventArgs e)
        {
            if (e.KeyCode >= Keys.D0 && e.KeyCode <= Keys.D9)
            {
                string digit = (e.KeyCode - Keys.D0).ToString();
                disp1.Text = (disp1.Text == "0" || enterValue) ? digit : disp1.Text + digit;
                enterValue = false;
            }
            else if (e.KeyCode >= Keys.NumPad0 && e.KeyCode <= Keys.NumPad9)
            {
                string digit = (e.KeyCode - Keys.NumPad0).ToString();
                disp1.Text = (disp1.Text == "0" || enterValue) ? digit : disp1.Text + digit;
                enterValue = false;
            }
            else if (e.KeyCode == Keys.Space)
            {
                btEqual.PerformClick();
                e.Handled = true;
                btEqual.Focus();
            }
            else if (e.KeyCode == Keys.Multiply)
            {
                btkrat.PerformClick();
            }
            else if (e.KeyCode == Keys.Divide || e.KeyCode == Keys.OemQuestion)
            {
                btdeleno.PerformClick();
            }
            else if (e.KeyCode == Keys.Add || e.KeyCode == Keys.Oemplus)
            {
                btplus.PerformClick();
            }
            else if (e.KeyCode == Keys.Subtract || e.KeyCode == Keys.OemMinus)
            {
                btminus.PerformClick();
            }
            else if (e.KeyCode == Keys.Back)
            {
                btDEL.PerformClick();
            }
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
                    if (disp1.Text == "")
                    {
                        disp1.Text = "0.";
                    }
                    else
                    {
                        disp1.Text += ".";
                    }
                }
            }
            else
            {
                disp1.Text += num.Text;
            }
        }

        private void numOpera(object sender, EventArgs e)
        {
            try
            {
                if (result != 0) btEqual.PerformClick();
                else result = Double.Parse(disp1.Text);

                Button num = (Button)sender;
                enterValue = true;
                op = num.Text;
                if (num.Text != "0")
                {
                    if (op == "x^y")
                    {
                        disp2.Text = enterFirstValue = string.Format("{0}^", result);
                        disp1.Text = string.Empty;
                    }
                    else if (op == "y√x")
                    {
                        disp2.Text = enterFirstValue = string.Format("{0}√", result);
                        disp1.Text = string.Empty;
                    }
                    else
                    {
                        disp2.Text = enterFirstValue = string.Format("{0}{1}", result, op);
                        disp1.Text = string.Empty;
                    }
                }
                disp1.Text = "";
            }
            catch (FormatException)
            {
                MessageBox.Show("Neplatný vstup. Zadajte číslo.", "Chyba vstupu", MessageBoxButtons.OK, MessageBoxIcon.Error);
                disp1.Text = "0";
                disp2.Text = "0";
                enterValue = false;
            }
        }

        private void btEquals_clik(object sender, EventArgs e)
        {
            try
            {
                enterSecondValue = disp1.Text;
                disp2.Text = $"{disp2.Text}{disp1.Text}=";
                if (disp1.Text != string.Empty)
                {
                    switch (op)
                    {
                        case "+":
                            disp1.Text = (result + Double.Parse(enterSecondValue)).ToString();
                            break;
                        case "-":
                            disp1.Text = (result - Double.Parse(enterSecondValue)).ToString();
                            break;
                        case "*":
                            disp1.Text = (result * Double.Parse(enterSecondValue)).ToString();
                            break;
                        case "/":
                            disp1.Text = (result / Double.Parse(enterSecondValue)).ToString();
                            break;
                        case "x^y":
                            disp1.Text = Math.Pow(result, Double.Parse(enterSecondValue)).ToString();
                            break;
                        case "y√x":
                            disp1.Text = Math.Pow(Double.Parse(enterSecondValue), 1.0 / result).ToString();
                            break;
                        default:
                            disp2.Text = string.Format("{0}=", disp1.Text);
                            break;

                    }
                    AppendHistory(enterFirstValue + enterSecondValue + " = " + disp1.Text);
                    result = Double.Parse(disp1.Text);
                    op = string.Empty;
                }
            }
            catch (FormatException)
            {
                MessageBox.Show("Neplatný vstup. Zadajte číslo.", "Chyba vstupu", MessageBoxButtons.OK, MessageBoxIcon.Error);
                disp1.Text = "0";
                disp2.Text = "0";
                enterValue = false;
            }
            catch (DivideByZeroException)
            {
                MessageBox.Show("Delenie nulou nie je povolené.", "Chyba výpočtu", MessageBoxButtons.OK, MessageBoxIcon.Error);
                disp1.Text = "0";
                disp2.Text = "0";
                enterValue = false;
            }
            btEqual.Focus();
        }

        private void btHi_Click(object sender, EventArgs e)
        {
            if (paDe.Height == 5)
                paDe.Height = 400;
            else
                paDe.Height = 5;
            if (txtHistory.Text == "Historia je prázdná" && paDe.Height == 5)
            {
                txtHistory.Clear();
            }
        }

        private void btHClear_Click(object sender, EventArgs e)
        {
            txtHistory.Clear();
            if (txtHistory.Text == string.Empty)
            {
                txtHistory.Text = "Historia je prázdná";
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
            try
            {
                Button num = (Button)sender;
                op = num.Text;
                switch (op)
                {
                    case "sin":
                        disp2.Text = string.Format("sin({0})", disp1.Text);
                        disp1.Text = Math.Sin(Double.Parse(disp1.Text)).ToString();
                        break;
                    case "cos":
                        disp2.Text = string.Format("cos({0})", disp1.Text);
                        disp1.Text = Math.Cos(Double.Parse(disp1.Text)).ToString();
                        break;
                    case "tan":
                        disp2.Text = string.Format("tan({0})", disp1.Text);
                        disp1.Text = Math.Tan(Double.Parse(disp1.Text)).ToString();
                        break;
                    case "asin":
                        disp2.Text = string.Format("asin({0})", disp1.Text);
                        disp1.Text = Math.Asin(Double.Parse(disp1.Text)).ToString();
                        break;
                    case "acos":
                        disp2.Text = string.Format("acos({0})", disp1.Text);
                        disp1.Text = Math.Acos(Double.Parse(disp1.Text)).ToString();
                        break;
                    case "atan":
                        disp2.Text = string.Format("atan({0})", disp1.Text);
                        disp1.Text = Math.Atan(Double.Parse(disp1.Text)).ToString();
                        break;
                    case "√x":
                        disp2.Text = string.Format("√({0})", disp1.Text);
                        disp1.Text = Math.Sqrt(Double.Parse(disp1.Text)).ToString();
                        break;
                    case "x^2":
                        disp2.Text = string.Format("({0})^2", disp1.Text);
                        disp1.Text = Math.Pow(Double.Parse(disp1.Text), 2).ToString();
                        break;
                    case "log":
                        disp2.Text = string.Format("log({0})", disp1.Text);
                        disp1.Text = Math.Log(Double.Parse(disp1.Text)).ToString();
                        break;
                    case "%":
                        disp2.Text = string.Format("{0}%", disp1.Text);
                        disp1.Text = (Double.Parse(disp1.Text) / 100.0).ToString();
                        break;
                    case "±":
                        disp1.Text = (-1.0 * Double.Parse(disp1.Text)).ToString();
                        break;
                    case "1/x":
                        disp2.Text = string.Format("1/{0}", disp1.Text);
                        disp1.Text = (1.0 / Double.Parse(disp1.Text)).ToString();
                        break;
                }
                AppendHistory(disp2.Text + " = " + disp1.Text);
            }
            catch (FormatException)
            {
                MessageBox.Show("Neplatný vstup. Zadajte číslo.", "Chyba vstupu", MessageBoxButtons.OK, MessageBoxIcon.Error);
                disp1.Text = "0";
                disp2.Text = "0";
                enterValue = false;
            }
            catch (Exception ex)
            {
                MessageBox.Show("Chyba: " + ex.Message, "Chyba", MessageBoxButtons.OK, MessageBoxIcon.Error);
                disp1.Text = "0";
                disp2.Text = "0";
                enterValue = false;
            }
        }


        private void UpdateMemoryButton()
        {
            btMR.Enabled = (memory != 0);
        }

        private void btMplus_Click(object sender, EventArgs e)
        {
            memory += Double.Parse(disp1.Text);
            UpdateMemoryButton();
        }

        private void btMmin_Click(object sender, EventArgs e)
        {
            memory -= Double.Parse(disp1.Text);
            UpdateMemoryButton();
        }

        private void btMR_Click(object sender, EventArgs e)
        {
            disp1.Text = memory.ToString();
            UpdateMemoryButton();
        }

        private void btMC_Click(object sender, EventArgs e)
        {
            memory = 0;
            UpdateMemoryButton();
        }

        private void btMS_Click(object sender, EventArgs e)
        {
            memory = Double.Parse(disp1.Text);
            UpdateMemoryButton();
        }

        private void btShift_Click(object sender, EventArgs e)
        {
            panel1.Visible = !panel1.Visible;
        }
        private void panel1_Paint(object sender, PaintEventArgs e)
        {

        }


        private void AppendHistory(string text)
        {
            txtHistory.AppendText(text + "\n");

            try
            {
                FileStream fsZapis = new FileStream(historyFilePath, FileMode.Append);
                StreamWriter writer = new StreamWriter(fsZapis);
                writer.WriteLine(text);
                writer.Close();
                fsZapis.Close();
            }
            catch (Exception ex)
            {
                MessageBox.Show("Nepodarilo sa uložiť históriu: " + ex.Message);
            }
        }

        private void pi_Click(object sender, EventArgs e)
        {
            disp1.Text = Math.PI.ToString();
            enterSecondValue = disp1.Text;
            enterValue = false;
        }

    }
}
