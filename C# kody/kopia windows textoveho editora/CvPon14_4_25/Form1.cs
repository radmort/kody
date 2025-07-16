using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;
using System.IO;

namespace CvPon14_4_25
{
    public partial class Form1 : Form
    {
        public Form1()
        {
            InitializeComponent();
        }

        private void koniecToolStripMenuItem_Click(object sender, EventArgs e)
        {
            //  this.Close(); // zatvori len jedno konkretne okno
            Application.Exit(); // zatvori vsetko = viac okien a tak 

        }

        private void otvoritToolStripMenuItem_Click(object sender, EventArgs e)
        {
            DialogResult odpo =  openFileDialog1.ShowDialog();
            if (odpo == DialogResult.OK)
            {
                textBox1.Text = File.ReadAllText(openFileDialog1.FileName);
                this.Text = Path.GetFileName(openFileDialog1.FileName) + " - NotePad";
            }
            // Directory.GetCurrentDirectory(); // len pre info
            // FileInfo;                        // len pre info
            // Path.                            // len pre info
        }

        private void saveFileDialog1_FileOk(object sender, CancelEventArgs e)
        {

        }

        private void ulozitToolStripMenuItem_Click(object sender, EventArgs e)
        {
            DialogResult odpo = saveFileDialog1.ShowDialog();
            if (odpo == DialogResult.OK)
            {
                File.WriteAllText(saveFileDialog1.FileName, textBox1.Text);
                this.Text = Path.GetFileName(openFileDialog1.FileName) + " - NotePad";
            }
        }

        private void Form1_FormClosing(object sender, FormClosingEventArgs e)
        {
            DialogResult odp = MessageBox.Show("Chcete ulozit otvoreny subor ? ", "Zatvorenie aplikacie",
                            MessageBoxButtons.YesNoCancel,
                            MessageBoxIcon.Exclamation,
                            MessageBoxDefaultButton.Button3);
            if(odp == DialogResult.Yes)
            {
                ulozitToolStripMenuItem_Click(sender, e);
            }
            else if (odp == DialogResult.Cancel)
            {
                e.Cancel = true;
            }
        }
    }
}
