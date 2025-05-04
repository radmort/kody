using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace Cviko17_06
{
    public partial class Form1 : Form
    {
        public Form1()
        {
            InitializeComponent();
        }

        private void button1_Click(object sender, EventArgs e)
        {
            try
            {
                double a = Convert.ToDouble(textBox1.Text);
                double b = Convert.ToDouble(textBox2.Text);

                double vysledok = 0;

                if (radioButton1.Checked)
                {
                    label3.Text = "Plus";
                    vysledok = a + b;
                }
                else if (radioButton2.Checked)
                {
                    label3.Text = "minus";
                    vysledok = a - b;
                }
                else if (radioButton3.Checked)
                {
                    label3.Text = "deleno";
                    vysledok = a / b;
                }
                else if (radioButton4.Checked) 
                {
                    label3.Text = "krat";
                    vysledok = a * b;
                }

                textBox3.Text = Convert.ToString(vysledok);

            }
            catch (FormatException exf)
            {
                MessageBox.Show("Nespravne zadane vstupy..." + exf.Message, "Chyba"); // ulozime tu chybu do premenej exf a vypise co by vypisal debugger, dobra vec
            }
            catch (Exception ex)
            {
                MessageBox.Show("Neocakavana chyba: " + ex.Message, "Chyba");
            }
        }
    }
}
