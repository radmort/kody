using System;
using System.Drawing;
using System.Windows.Forms;

namespace RectangleRotation
{
    public partial class NapovedaForm : Form
    {
        public NapovedaForm()
        {
            InitializeComponent();
            InitializeForm();
        }

        private void InitializeForm()
        {
            this.Text = "Nápoveda";
            this.ClientSize = new Size(400, 350);
            this.FormBorderStyle = FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.MinimizeBox = false;
            this.StartPosition = FormStartPosition.CenterParent;
            
         
          
        }

        private void richTextBox1_TextChanged(object sender, EventArgs e)
        {
        }

        private void zatvoritbtn_Click(object sender, EventArgs e)
        {
            this.Close();
        }

        private void NapovedaForm_Load(object sender, EventArgs e)
        {

        }

        private void zatvoritbtn_Click_1(object sender, EventArgs e)
        {
            this.Close();
        }

        private void richTextBox1_TextChanged_1(object sender, EventArgs e)
        {

        }
    }
}
