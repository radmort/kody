namespace Kalkulacka
{
    partial class Kalkulačka
    {
        /// <summary>
        ///  Required designer variable.
        /// </summary>
        private System.ComponentModel.IContainer components = null;

        /// <summary>
        ///  Clean up any resources being used.
        /// </summary>
        /// <param name="disposing">true if managed resources should be disposed; otherwise, false.</param>
        protected override void Dispose(bool disposing)
        {
            if (disposing && (components != null))
            {
                components.Dispose();
            }
            base.Dispose(disposing);
        }

        #region Windows Form Designer generated code

        /// <summary>
        ///  Required method for Designer support - do not modify
        ///  the contents of this method with the code editor.
        /// </summary>
        private void InitializeComponent()
        {
            components = new System.ComponentModel.Container();
            imageList1 = new ImageList(components);
            paHi = new Panel();
            btShift = new Button();
            btHi = new Button();
            panel1 = new Panel();
            pi = new Button();
            button8 = new Button();
            powto = new Button();
            button9 = new Button();
            button6 = new Button();
            button5 = new Button();
            button4 = new Button();
            button3 = new Button();
            button2 = new Button();
            button1 = new Button();
            paDe = new Panel();
            btHClear = new Button();
            txtHistory = new RichTextBox();
            disp2 = new TextBox();
            disp1 = new TextBox();
            btDEL = new Button();
            btMS = new Button();
            btMmin = new Button();
            btMplus = new Button();
            btMR = new Button();
            btMC = new Button();
            btC = new Button();
            btCE = new Button();
            btPercent = new Button();
            btsqrt = new Button();
            btpow = new Button();
            btdelx = new Button();
            btplusminus = new Button();
            bt9 = new Button();
            bt8 = new Button();
            bt7 = new Button();
            bt6 = new Button();
            bt5 = new Button();
            bt4 = new Button();
            bt3 = new Button();
            bt2 = new Button();
            bt1 = new Button();
            bt0 = new Button();
            btcoma = new Button();
            btdeleno = new Button();
            btkrat = new Button();
            btminus = new Button();
            btplus = new Button();
            btEqual = new Button();
            paHi.SuspendLayout();
            panel1.SuspendLayout();
            paDe.SuspendLayout();
            SuspendLayout();
            // 
            // imageList1
            // 
            imageList1.ColorDepth = ColorDepth.Depth32Bit;
            imageList1.ImageSize = new Size(16, 16);
            imageList1.TransparentColor = Color.Transparent;
            // 
            // paHi
            // 
            paHi.BackColor = SystemColors.WindowFrame;
            paHi.Controls.Add(btShift);
            paHi.Controls.Add(btHi);
            paHi.Dock = DockStyle.Top;
            paHi.Location = new Point(0, 0);
            paHi.Margin = new Padding(3, 4, 3, 4);
            paHi.Name = "paHi";
            paHi.Size = new Size(500, 49);
            paHi.TabIndex = 0;
            // 
            // btShift
            // 
            btShift.BackColor = Color.FromArgb(64, 64, 64);
            btShift.Dock = DockStyle.Left;
            btShift.FlatAppearance.BorderColor = Color.White;
            btShift.FlatAppearance.BorderSize = 0;
            btShift.FlatAppearance.MouseOverBackColor = Color.Black;
            btShift.FlatStyle = FlatStyle.Flat;
            btShift.ForeColor = SystemColors.Control;
            btShift.Location = new Point(0, 0);
            btShift.Margin = new Padding(0);
            btShift.Name = "btShift";
            btShift.Size = new Size(95, 49);
            btShift.TabIndex = 1;
            btShift.Text = "Shift";
            btShift.UseVisualStyleBackColor = false;
            btShift.Click += btShift_Click;
            // 
            // btHi
            // 
            btHi.BackColor = Color.FromArgb(64, 64, 64);
            btHi.Dock = DockStyle.Right;
            btHi.FlatAppearance.BorderColor = SystemColors.AppWorkspace;
            btHi.FlatAppearance.BorderSize = 0;
            btHi.FlatAppearance.MouseOverBackColor = Color.Black;
            btHi.FlatStyle = FlatStyle.Flat;
            btHi.ForeColor = SystemColors.Control;
            btHi.Location = new Point(409, 0);
            btHi.Margin = new Padding(0);
            btHi.Name = "btHi";
            btHi.Size = new Size(91, 49);
            btHi.TabIndex = 0;
            btHi.Text = "History";
            btHi.UseVisualStyleBackColor = false;
            btHi.Click += btHi_Click;
            // 
            // panel1
            // 
            panel1.Controls.Add(pi);
            panel1.Controls.Add(button8);
            panel1.Controls.Add(powto);
            panel1.Controls.Add(button9);
            panel1.Controls.Add(button6);
            panel1.Controls.Add(button5);
            panel1.Controls.Add(button4);
            panel1.Controls.Add(button3);
            panel1.Controls.Add(button2);
            panel1.Controls.Add(button1);
            panel1.Location = new Point(10, 152);
            panel1.Margin = new Padding(1);
            panel1.Name = "panel1";
            panel1.Size = new Size(165, 183);
            panel1.TabIndex = 1;
            panel1.Visible = false;
            panel1.Paint += panel1_Paint;
            // 
            // pi
            // 
            pi.BackColor = Color.FromArgb(64, 64, 64);
            pi.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            pi.FlatAppearance.BorderSize = 0;
            pi.FlatAppearance.MouseOverBackColor = Color.Gray;
            pi.FlatStyle = FlatStyle.Flat;
            pi.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            pi.ForeColor = Color.White;
            pi.Location = new Point(82, 113);
            pi.Margin = new Padding(0);
            pi.Name = "pi";
            pi.Size = new Size(85, 35);
            pi.TabIndex = 5;
            pi.Text = "π";
            pi.UseVisualStyleBackColor = false;
            pi.Click += pi_Click;
            // 
            // button8
            // 
            button8.BackColor = Color.FromArgb(64, 64, 64);
            button8.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            button8.FlatAppearance.BorderSize = 0;
            button8.FlatAppearance.MouseOverBackColor = Color.Gray;
            button8.FlatStyle = FlatStyle.Flat;
            button8.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            button8.ForeColor = Color.White;
            button8.Location = new Point(82, 150);
            button8.Margin = new Padding(0);
            button8.Name = "button8";
            button8.Size = new Size(85, 35);
            button8.TabIndex = 5;
            button8.Text = "y√x";
            button8.UseVisualStyleBackColor = false;
            button8.Click += numOpera;
            // 
            // powto
            // 
            powto.BackColor = Color.FromArgb(64, 64, 64);
            powto.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            powto.FlatAppearance.BorderSize = 0;
            powto.FlatAppearance.MouseOverBackColor = Color.Gray;
            powto.FlatStyle = FlatStyle.Flat;
            powto.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            powto.ForeColor = Color.White;
            powto.Location = new Point(-6, 150);
            powto.Margin = new Padding(0);
            powto.Name = "powto";
            powto.Size = new Size(85, 35);
            powto.TabIndex = 5;
            powto.Text = "x^y";
            powto.UseVisualStyleBackColor = false;
            powto.Click += numOpera;
            // 
            // button9
            // 
            button9.BackColor = Color.FromArgb(64, 64, 64);
            button9.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            button9.FlatAppearance.BorderSize = 0;
            button9.FlatAppearance.MouseOverBackColor = Color.Gray;
            button9.FlatStyle = FlatStyle.Flat;
            button9.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            button9.ForeColor = Color.White;
            button9.Location = new Point(-6, 113);
            button9.Margin = new Padding(0);
            button9.Name = "button9";
            button9.Size = new Size(85, 35);
            button9.TabIndex = 5;
            button9.Text = "log";
            button9.UseVisualStyleBackColor = false;
            button9.Click += Operaciabt;
            // 
            // button6
            // 
            button6.BackColor = Color.FromArgb(64, 64, 64);
            button6.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            button6.FlatAppearance.BorderSize = 0;
            button6.FlatAppearance.MouseOverBackColor = Color.Gray;
            button6.FlatStyle = FlatStyle.Flat;
            button6.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            button6.ForeColor = Color.White;
            button6.Location = new Point(82, 76);
            button6.Margin = new Padding(0);
            button6.Name = "button6";
            button6.Size = new Size(85, 35);
            button6.TabIndex = 5;
            button6.Text = "atan";
            button6.UseVisualStyleBackColor = false;
            button6.Click += Operaciabt;
            // 
            // button5
            // 
            button5.BackColor = Color.FromArgb(64, 64, 64);
            button5.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            button5.FlatAppearance.BorderSize = 0;
            button5.FlatAppearance.MouseOverBackColor = Color.Gray;
            button5.FlatStyle = FlatStyle.Flat;
            button5.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            button5.ForeColor = Color.White;
            button5.Location = new Point(-6, 76);
            button5.Margin = new Padding(0);
            button5.Name = "button5";
            button5.Size = new Size(85, 35);
            button5.TabIndex = 5;
            button5.Text = "tan";
            button5.UseVisualStyleBackColor = false;
            button5.Click += Operaciabt;
            // 
            // button4
            // 
            button4.BackColor = Color.FromArgb(64, 64, 64);
            button4.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            button4.FlatAppearance.BorderSize = 0;
            button4.FlatAppearance.MouseOverBackColor = Color.Gray;
            button4.FlatStyle = FlatStyle.Flat;
            button4.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            button4.ForeColor = Color.White;
            button4.Location = new Point(82, 1);
            button4.Margin = new Padding(0);
            button4.Name = "button4";
            button4.Size = new Size(85, 35);
            button4.TabIndex = 5;
            button4.Text = "asin";
            button4.UseVisualStyleBackColor = false;
            button4.Click += Operaciabt;
            // 
            // button3
            // 
            button3.BackColor = Color.FromArgb(64, 64, 64);
            button3.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            button3.FlatAppearance.BorderSize = 0;
            button3.FlatAppearance.MouseOverBackColor = Color.Gray;
            button3.FlatStyle = FlatStyle.Flat;
            button3.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            button3.ForeColor = Color.White;
            button3.Location = new Point(82, 38);
            button3.Margin = new Padding(0);
            button3.Name = "button3";
            button3.Size = new Size(85, 35);
            button3.TabIndex = 5;
            button3.Text = "acos";
            button3.UseVisualStyleBackColor = false;
            button3.Click += Operaciabt;
            // 
            // button2
            // 
            button2.BackColor = Color.FromArgb(64, 64, 64);
            button2.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            button2.FlatAppearance.BorderSize = 0;
            button2.FlatAppearance.MouseOverBackColor = Color.Gray;
            button2.FlatStyle = FlatStyle.Flat;
            button2.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            button2.ForeColor = Color.White;
            button2.Location = new Point(-6, 1);
            button2.Margin = new Padding(0);
            button2.Name = "button2";
            button2.Size = new Size(85, 35);
            button2.TabIndex = 5;
            button2.Text = "sin";
            button2.UseVisualStyleBackColor = false;
            button2.Click += Operaciabt;
            // 
            // button1
            // 
            button1.BackColor = Color.FromArgb(64, 64, 64);
            button1.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            button1.FlatAppearance.BorderSize = 0;
            button1.FlatAppearance.MouseOverBackColor = Color.Gray;
            button1.FlatStyle = FlatStyle.Flat;
            button1.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            button1.ForeColor = Color.White;
            button1.Location = new Point(-6, 38);
            button1.Margin = new Padding(0);
            button1.Name = "button1";
            button1.Size = new Size(85, 35);
            button1.TabIndex = 5;
            button1.Text = "cos";
            button1.UseVisualStyleBackColor = false;
            button1.Click += Operaciabt;
            // 
            // paDe
            // 
            paDe.BackColor = SystemColors.WindowFrame;
            paDe.Controls.Add(btHClear);
            paDe.Controls.Add(txtHistory);
            paDe.Dock = DockStyle.Bottom;
            paDe.Location = new Point(0, 782);
            paDe.Margin = new Padding(3, 4, 3, 4);
            paDe.Name = "paDe";
            paDe.Size = new Size(500, 5);
            paDe.TabIndex = 1;
            // 
            // btHClear
            // 
            btHClear.Dock = DockStyle.Bottom;
            btHClear.FlatAppearance.BorderColor = SystemColors.AppWorkspace;
            btHClear.FlatAppearance.MouseOverBackColor = Color.Black;
            btHClear.FlatStyle = FlatStyle.Flat;
            btHClear.ForeColor = SystemColors.Control;
            btHClear.Location = new Point(0, -44);
            btHClear.Margin = new Padding(0);
            btHClear.Name = "btHClear";
            btHClear.Size = new Size(500, 49);
            btHClear.TabIndex = 2;
            btHClear.Text = "Clear";
            btHClear.UseVisualStyleBackColor = true;
            btHClear.Click += btHClear_Click;
            // 
            // txtHistory
            // 
            txtHistory.BackColor = SystemColors.ControlDark;
            txtHistory.BorderStyle = BorderStyle.None;
            txtHistory.Dock = DockStyle.Fill;
            txtHistory.Font = new Font("Segoe UI", 14.25F, FontStyle.Bold, GraphicsUnit.Point, 238);
            txtHistory.Location = new Point(0, 0);
            txtHistory.Margin = new Padding(0);
            txtHistory.Name = "txtHistory";
            txtHistory.ScrollBars = RichTextBoxScrollBars.Horizontal;
            txtHistory.Size = new Size(500, 5);
            txtHistory.TabIndex = 3;
            txtHistory.Text = "";
            // 
            // disp2
            // 
            disp2.BackColor = SystemColors.ScrollBar;
            disp2.BorderStyle = BorderStyle.None;
            disp2.Dock = DockStyle.Top;
            disp2.Font = new Font("Segoe UI", 14.25F, FontStyle.Bold, GraphicsUnit.Point, 238);
            disp2.Location = new Point(0, 49);
            disp2.Margin = new Padding(0);
            disp2.Multiline = true;
            disp2.Name = "disp2";
            disp2.Size = new Size(500, 39);
            disp2.TabIndex = 2;
            disp2.Text = "0";
            disp2.TextAlign = HorizontalAlignment.Right;
            // 
            // disp1
            // 
            disp1.BackColor = SystemColors.ScrollBar;
            disp1.BorderStyle = BorderStyle.None;
            disp1.Dock = DockStyle.Top;
            disp1.Font = new Font("Segoe UI", 24F, FontStyle.Bold, GraphicsUnit.Point, 238);
            disp1.Location = new Point(0, 88);
            disp1.Margin = new Padding(0);
            disp1.Multiline = true;
            disp1.Name = "disp1";
            disp1.Size = new Size(500, 63);
            disp1.TabIndex = 3;
            disp1.Text = "0";
            disp1.TextAlign = HorizontalAlignment.Right;
            // 
            // btDEL
            // 
            btDEL.BackColor = Color.FromArgb(64, 64, 64);
            btDEL.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            btDEL.FlatAppearance.BorderSize = 0;
            btDEL.FlatAppearance.MouseOverBackColor = Color.Gray;
            btDEL.FlatStyle = FlatStyle.Flat;
            btDEL.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            btDEL.ForeColor = Color.White;
            btDEL.Location = new Point(378, 211);
            btDEL.Margin = new Padding(0);
            btDEL.Name = "btDEL";
            btDEL.Size = new Size(125, 91);
            btDEL.TabIndex = 4;
            btDEL.Text = "DEL";
            btDEL.UseVisualStyleBackColor = false;
            btDEL.Click += btDEL_clik;
            // 
            // btMS
            // 
            btMS.BackColor = Color.FromArgb(64, 64, 64);
            btMS.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            btMS.FlatAppearance.BorderSize = 0;
            btMS.FlatAppearance.MouseOverBackColor = Color.Gray;
            btMS.FlatStyle = FlatStyle.Flat;
            btMS.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            btMS.ForeColor = Color.White;
            btMS.Location = new Point(116, 151);
            btMS.Margin = new Padding(0);
            btMS.Name = "btMS";
            btMS.Size = new Size(69, 48);
            btMS.TabIndex = 4;
            btMS.Text = "MS";
            btMS.UseVisualStyleBackColor = false;
            btMS.Click += btMS_Click;
            // 
            // btMmin
            // 
            btMmin.BackColor = Color.FromArgb(64, 64, 64);
            btMmin.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            btMmin.FlatAppearance.BorderSize = 0;
            btMmin.FlatAppearance.MouseOverBackColor = Color.Gray;
            btMmin.FlatStyle = FlatStyle.Flat;
            btMmin.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            btMmin.ForeColor = Color.White;
            btMmin.Location = new Point(433, 151);
            btMmin.Margin = new Padding(0);
            btMmin.Name = "btMmin";
            btMmin.Size = new Size(69, 48);
            btMmin.TabIndex = 4;
            btMmin.Text = "M-";
            btMmin.UseVisualStyleBackColor = false;
            btMmin.Click += btMmin_Click;
            // 
            // btMplus
            // 
            btMplus.BackColor = Color.FromArgb(64, 64, 64);
            btMplus.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            btMplus.FlatAppearance.BorderSize = 0;
            btMplus.FlatAppearance.MouseOverBackColor = Color.Gray;
            btMplus.FlatStyle = FlatStyle.Flat;
            btMplus.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            btMplus.ForeColor = Color.White;
            btMplus.Location = new Point(325, 151);
            btMplus.Margin = new Padding(0);
            btMplus.Name = "btMplus";
            btMplus.Size = new Size(69, 48);
            btMplus.TabIndex = 4;
            btMplus.Text = "M+";
            btMplus.UseVisualStyleBackColor = false;
            btMplus.Click += btMplus_Click;
            // 
            // btMR
            // 
            btMR.BackColor = Color.FromArgb(64, 64, 64);
            btMR.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            btMR.FlatAppearance.BorderSize = 0;
            btMR.FlatAppearance.MouseOverBackColor = Color.Gray;
            btMR.FlatStyle = FlatStyle.Flat;
            btMR.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            btMR.ForeColor = Color.White;
            btMR.Location = new Point(0, 151);
            btMR.Margin = new Padding(0);
            btMR.Name = "btMR";
            btMR.Size = new Size(69, 48);
            btMR.TabIndex = 4;
            btMR.Text = "M";
            btMR.UseVisualStyleBackColor = false;
            btMR.Click += btMR_Click;
            // 
            // btMC
            // 
            btMC.BackColor = Color.FromArgb(64, 64, 64);
            btMC.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            btMC.FlatAppearance.BorderSize = 0;
            btMC.FlatAppearance.MouseOverBackColor = Color.Gray;
            btMC.FlatStyle = FlatStyle.Flat;
            btMC.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            btMC.ForeColor = Color.White;
            btMC.Location = new Point(215, 151);
            btMC.Margin = new Padding(0);
            btMC.Name = "btMC";
            btMC.Size = new Size(69, 48);
            btMC.TabIndex = 4;
            btMC.Text = "MC";
            btMC.UseVisualStyleBackColor = false;
            btMC.Click += btMC_Click;
            // 
            // btC
            // 
            btC.BackColor = Color.FromArgb(64, 64, 64);
            btC.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            btC.FlatAppearance.BorderSize = 0;
            btC.FlatAppearance.MouseOverBackColor = Color.Gray;
            btC.FlatStyle = FlatStyle.Flat;
            btC.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            btC.ForeColor = Color.White;
            btC.Location = new Point(252, 211);
            btC.Margin = new Padding(0);
            btC.Name = "btC";
            btC.Size = new Size(125, 91);
            btC.TabIndex = 4;
            btC.Text = "C";
            btC.UseVisualStyleBackColor = false;
            btC.Click += btC_Clik;
            // 
            // btCE
            // 
            btCE.BackColor = Color.FromArgb(64, 64, 64);
            btCE.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            btCE.FlatAppearance.BorderSize = 0;
            btCE.FlatAppearance.MouseOverBackColor = Color.Gray;
            btCE.FlatStyle = FlatStyle.Flat;
            btCE.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            btCE.ForeColor = Color.White;
            btCE.Location = new Point(126, 211);
            btCE.Margin = new Padding(0);
            btCE.Name = "btCE";
            btCE.Size = new Size(125, 91);
            btCE.TabIndex = 4;
            btCE.Text = "CE";
            btCE.UseVisualStyleBackColor = false;
            btCE.Click += btCE_Clik;
            // 
            // btPercent
            // 
            btPercent.BackColor = Color.FromArgb(64, 64, 64);
            btPercent.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            btPercent.FlatAppearance.BorderSize = 0;
            btPercent.FlatAppearance.MouseOverBackColor = Color.Gray;
            btPercent.FlatStyle = FlatStyle.Flat;
            btPercent.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            btPercent.ForeColor = Color.White;
            btPercent.Location = new Point(0, 211);
            btPercent.Margin = new Padding(0);
            btPercent.Name = "btPercent";
            btPercent.Size = new Size(125, 91);
            btPercent.TabIndex = 4;
            btPercent.Text = "%";
            btPercent.UseVisualStyleBackColor = false;
            btPercent.Click += Operaciabt;
            // 
            // btsqrt
            // 
            btsqrt.BackColor = Color.FromArgb(64, 64, 64);
            btsqrt.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            btsqrt.FlatAppearance.BorderSize = 0;
            btsqrt.FlatAppearance.MouseOverBackColor = Color.Gray;
            btsqrt.FlatStyle = FlatStyle.Flat;
            btsqrt.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            btsqrt.ForeColor = Color.White;
            btsqrt.Location = new Point(252, 303);
            btsqrt.Margin = new Padding(0);
            btsqrt.Name = "btsqrt";
            btsqrt.Size = new Size(125, 91);
            btsqrt.TabIndex = 4;
            btsqrt.Text = "√x";
            btsqrt.UseVisualStyleBackColor = false;
            btsqrt.Click += Operaciabt;
            // 
            // btpow
            // 
            btpow.BackColor = Color.FromArgb(64, 64, 64);
            btpow.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            btpow.FlatAppearance.BorderSize = 0;
            btpow.FlatAppearance.MouseOverBackColor = Color.Gray;
            btpow.FlatStyle = FlatStyle.Flat;
            btpow.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            btpow.ForeColor = Color.White;
            btpow.Location = new Point(126, 303);
            btpow.Margin = new Padding(0);
            btpow.Name = "btpow";
            btpow.Size = new Size(125, 91);
            btpow.TabIndex = 4;
            btpow.Text = "x^2";
            btpow.UseVisualStyleBackColor = false;
            btpow.Click += Operaciabt;
            // 
            // btdelx
            // 
            btdelx.BackColor = Color.FromArgb(64, 64, 64);
            btdelx.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            btdelx.FlatAppearance.BorderSize = 0;
            btdelx.FlatAppearance.MouseOverBackColor = Color.Gray;
            btdelx.FlatStyle = FlatStyle.Flat;
            btdelx.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            btdelx.ForeColor = Color.White;
            btdelx.Location = new Point(0, 303);
            btdelx.Margin = new Padding(0);
            btdelx.Name = "btdelx";
            btdelx.Size = new Size(125, 91);
            btdelx.TabIndex = 4;
            btdelx.Text = "1/x";
            btdelx.UseVisualStyleBackColor = false;
            btdelx.Click += Operaciabt;
            // 
            // btplusminus
            // 
            btplusminus.BackColor = Color.FromArgb(64, 64, 64);
            btplusminus.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            btplusminus.FlatAppearance.BorderSize = 0;
            btplusminus.FlatAppearance.MouseOverBackColor = Color.Gray;
            btplusminus.FlatStyle = FlatStyle.Flat;
            btplusminus.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            btplusminus.ForeColor = Color.White;
            btplusminus.Location = new Point(0, 671);
            btplusminus.Margin = new Padding(0);
            btplusminus.Name = "btplusminus";
            btplusminus.Size = new Size(125, 91);
            btplusminus.TabIndex = 4;
            btplusminus.Text = "±";
            btplusminus.UseVisualStyleBackColor = false;
            btplusminus.Click += Operaciabt;
            // 
            // bt9
            // 
            bt9.BackColor = Color.FromArgb(64, 64, 64);
            bt9.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            bt9.FlatAppearance.BorderSize = 0;
            bt9.FlatAppearance.MouseOverBackColor = Color.Gray;
            bt9.FlatStyle = FlatStyle.Flat;
            bt9.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            bt9.ForeColor = Color.White;
            bt9.Location = new Point(252, 395);
            bt9.Margin = new Padding(0);
            bt9.Name = "bt9";
            bt9.Size = new Size(125, 91);
            bt9.TabIndex = 4;
            bt9.Text = "9";
            bt9.UseVisualStyleBackColor = false;
            bt9.Click += Btn_clikNum;
            // 
            // bt8
            // 
            bt8.BackColor = Color.FromArgb(64, 64, 64);
            bt8.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            bt8.FlatAppearance.BorderSize = 0;
            bt8.FlatAppearance.MouseOverBackColor = Color.Gray;
            bt8.FlatStyle = FlatStyle.Flat;
            bt8.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            bt8.ForeColor = Color.White;
            bt8.Location = new Point(126, 395);
            bt8.Margin = new Padding(0);
            bt8.Name = "bt8";
            bt8.Size = new Size(125, 91);
            bt8.TabIndex = 4;
            bt8.Text = "8";
            bt8.UseVisualStyleBackColor = false;
            bt8.Click += Btn_clikNum;
            // 
            // bt7
            // 
            bt7.BackColor = Color.FromArgb(64, 64, 64);
            bt7.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            bt7.FlatAppearance.BorderSize = 0;
            bt7.FlatAppearance.MouseOverBackColor = Color.Gray;
            bt7.FlatStyle = FlatStyle.Flat;
            bt7.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            bt7.ForeColor = Color.White;
            bt7.Location = new Point(0, 395);
            bt7.Margin = new Padding(0);
            bt7.Name = "bt7";
            bt7.Size = new Size(125, 91);
            bt7.TabIndex = 4;
            bt7.Text = "7";
            bt7.UseVisualStyleBackColor = false;
            bt7.Click += Btn_clikNum;
            // 
            // bt6
            // 
            bt6.BackColor = Color.FromArgb(64, 64, 64);
            bt6.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            bt6.FlatAppearance.BorderSize = 0;
            bt6.FlatAppearance.MouseOverBackColor = Color.Gray;
            bt6.FlatStyle = FlatStyle.Flat;
            bt6.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            bt6.ForeColor = Color.White;
            bt6.Location = new Point(252, 487);
            bt6.Margin = new Padding(0);
            bt6.Name = "bt6";
            bt6.Size = new Size(125, 91);
            bt6.TabIndex = 4;
            bt6.Text = "6";
            bt6.UseVisualStyleBackColor = false;
            bt6.Click += Btn_clikNum;
            // 
            // bt5
            // 
            bt5.BackColor = Color.FromArgb(64, 64, 64);
            bt5.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            bt5.FlatAppearance.BorderSize = 0;
            bt5.FlatAppearance.MouseOverBackColor = Color.Gray;
            bt5.FlatStyle = FlatStyle.Flat;
            bt5.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            bt5.ForeColor = Color.White;
            bt5.Location = new Point(126, 487);
            bt5.Margin = new Padding(0);
            bt5.Name = "bt5";
            bt5.Size = new Size(125, 91);
            bt5.TabIndex = 4;
            bt5.Text = "5";
            bt5.UseVisualStyleBackColor = false;
            bt5.Click += Btn_clikNum;
            // 
            // bt4
            // 
            bt4.BackColor = Color.FromArgb(64, 64, 64);
            bt4.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            bt4.FlatAppearance.BorderSize = 0;
            bt4.FlatAppearance.MouseOverBackColor = Color.Gray;
            bt4.FlatStyle = FlatStyle.Flat;
            bt4.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            bt4.ForeColor = Color.White;
            bt4.Location = new Point(0, 487);
            bt4.Margin = new Padding(0);
            bt4.Name = "bt4";
            bt4.Size = new Size(125, 91);
            bt4.TabIndex = 4;
            bt4.Text = "4";
            bt4.UseVisualStyleBackColor = false;
            bt4.Click += Btn_clikNum;
            // 
            // bt3
            // 
            bt3.BackColor = Color.FromArgb(64, 64, 64);
            bt3.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            bt3.FlatAppearance.BorderSize = 0;
            bt3.FlatAppearance.MouseOverBackColor = Color.Gray;
            bt3.FlatStyle = FlatStyle.Flat;
            bt3.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            bt3.ForeColor = Color.White;
            bt3.Location = new Point(252, 579);
            bt3.Margin = new Padding(0);
            bt3.Name = "bt3";
            bt3.Size = new Size(125, 91);
            bt3.TabIndex = 4;
            bt3.Text = "3";
            bt3.UseVisualStyleBackColor = false;
            bt3.Click += Btn_clikNum;
            // 
            // bt2
            // 
            bt2.BackColor = Color.FromArgb(64, 64, 64);
            bt2.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            bt2.FlatAppearance.BorderSize = 0;
            bt2.FlatAppearance.MouseOverBackColor = Color.Gray;
            bt2.FlatStyle = FlatStyle.Flat;
            bt2.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            bt2.ForeColor = Color.White;
            bt2.Location = new Point(126, 579);
            bt2.Margin = new Padding(0);
            bt2.Name = "bt2";
            bt2.Size = new Size(125, 91);
            bt2.TabIndex = 4;
            bt2.Text = "2";
            bt2.UseVisualStyleBackColor = false;
            bt2.Click += Btn_clikNum;
            // 
            // bt1
            // 
            bt1.BackColor = Color.FromArgb(64, 64, 64);
            bt1.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            bt1.FlatAppearance.BorderSize = 0;
            bt1.FlatAppearance.MouseOverBackColor = Color.Gray;
            bt1.FlatStyle = FlatStyle.Flat;
            bt1.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            bt1.ForeColor = Color.White;
            bt1.Location = new Point(0, 579);
            bt1.Margin = new Padding(0);
            bt1.Name = "bt1";
            bt1.Size = new Size(125, 91);
            bt1.TabIndex = 4;
            bt1.Text = "1";
            bt1.UseVisualStyleBackColor = false;
            bt1.Click += Btn_clikNum;
            // 
            // bt0
            // 
            bt0.BackColor = Color.FromArgb(64, 64, 64);
            bt0.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            bt0.FlatAppearance.BorderSize = 0;
            bt0.FlatAppearance.MouseOverBackColor = Color.Gray;
            bt0.FlatStyle = FlatStyle.Flat;
            bt0.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            bt0.ForeColor = Color.White;
            bt0.Location = new Point(126, 671);
            bt0.Margin = new Padding(0);
            bt0.Name = "bt0";
            bt0.Size = new Size(125, 91);
            bt0.TabIndex = 4;
            bt0.Text = "0";
            bt0.UseVisualStyleBackColor = false;
            bt0.Click += Btn_clikNum;
            // 
            // btcoma
            // 
            btcoma.BackColor = Color.FromArgb(64, 64, 64);
            btcoma.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            btcoma.FlatAppearance.BorderSize = 0;
            btcoma.FlatAppearance.MouseOverBackColor = Color.Gray;
            btcoma.FlatStyle = FlatStyle.Flat;
            btcoma.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            btcoma.ForeColor = Color.White;
            btcoma.Location = new Point(252, 671);
            btcoma.Margin = new Padding(0);
            btcoma.Name = "btcoma";
            btcoma.Size = new Size(125, 91);
            btcoma.TabIndex = 4;
            btcoma.Text = ".";
            btcoma.UseVisualStyleBackColor = false;
            btcoma.Click += Btn_clikNum;
            // 
            // btdeleno
            // 
            btdeleno.BackColor = Color.FromArgb(64, 64, 64);
            btdeleno.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            btdeleno.FlatAppearance.BorderSize = 0;
            btdeleno.FlatAppearance.MouseOverBackColor = Color.Gray;
            btdeleno.FlatStyle = FlatStyle.Flat;
            btdeleno.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            btdeleno.ForeColor = Color.White;
            btdeleno.Location = new Point(378, 303);
            btdeleno.Margin = new Padding(0);
            btdeleno.Name = "btdeleno";
            btdeleno.Size = new Size(125, 91);
            btdeleno.TabIndex = 4;
            btdeleno.Text = "/";
            btdeleno.UseVisualStyleBackColor = false;
            btdeleno.Click += numOpera;
            // 
            // btkrat
            // 
            btkrat.BackColor = Color.FromArgb(64, 64, 64);
            btkrat.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            btkrat.FlatAppearance.BorderSize = 0;
            btkrat.FlatAppearance.MouseOverBackColor = Color.Gray;
            btkrat.FlatStyle = FlatStyle.Flat;
            btkrat.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            btkrat.ForeColor = Color.White;
            btkrat.Location = new Point(378, 395);
            btkrat.Margin = new Padding(0);
            btkrat.Name = "btkrat";
            btkrat.Size = new Size(125, 91);
            btkrat.TabIndex = 4;
            btkrat.Text = "*";
            btkrat.UseVisualStyleBackColor = false;
            btkrat.Click += numOpera;
            // 
            // btminus
            // 
            btminus.BackColor = Color.FromArgb(64, 64, 64);
            btminus.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            btminus.FlatAppearance.BorderSize = 0;
            btminus.FlatAppearance.MouseOverBackColor = Color.Gray;
            btminus.FlatStyle = FlatStyle.Flat;
            btminus.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            btminus.ForeColor = Color.White;
            btminus.Location = new Point(378, 487);
            btminus.Margin = new Padding(0);
            btminus.Name = "btminus";
            btminus.Size = new Size(125, 91);
            btminus.TabIndex = 4;
            btminus.Text = "-";
            btminus.UseVisualStyleBackColor = false;
            btminus.Click += numOpera;
            // 
            // btplus
            // 
            btplus.BackColor = Color.FromArgb(64, 64, 64);
            btplus.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            btplus.FlatAppearance.BorderSize = 0;
            btplus.FlatAppearance.MouseOverBackColor = Color.Gray;
            btplus.FlatStyle = FlatStyle.Flat;
            btplus.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            btplus.ForeColor = Color.White;
            btplus.Location = new Point(378, 579);
            btplus.Margin = new Padding(0);
            btplus.Name = "btplus";
            btplus.Size = new Size(125, 91);
            btplus.TabIndex = 4;
            btplus.Text = "+";
            btplus.UseVisualStyleBackColor = false;
            btplus.Click += numOpera;
            // 
            // btEqual
            // 
            btEqual.BackColor = Color.FromArgb(64, 64, 64);
            btEqual.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            btEqual.FlatAppearance.BorderSize = 0;
            btEqual.FlatAppearance.MouseOverBackColor = Color.Gray;
            btEqual.FlatStyle = FlatStyle.Flat;
            btEqual.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            btEqual.ForeColor = Color.White;
            btEqual.Location = new Point(378, 671);
            btEqual.Margin = new Padding(0);
            btEqual.Name = "btEqual";
            btEqual.Size = new Size(125, 91);
            btEqual.TabIndex = 4;
            btEqual.Text = "=";
            btEqual.UseVisualStyleBackColor = false;
            btEqual.Click += btEquals_clik;
            // 
            // Kalkulačka
            // 
            AutoScaleDimensions = new SizeF(8F, 20F);
            AutoScaleMode = AutoScaleMode.Font;
            AutoSize = true;
            BackColor = SystemColors.WindowFrame;
            ClientSize = new Size(500, 787);
            Controls.Add(panel1);
            Controls.Add(paDe);
            Controls.Add(bt1);
            Controls.Add(bt4);
            Controls.Add(bt7);
            Controls.Add(btdelx);
            Controls.Add(btPercent);
            Controls.Add(bt0);
            Controls.Add(bt2);
            Controls.Add(btcoma);
            Controls.Add(bt3);
            Controls.Add(bt5);
            Controls.Add(bt6);
            Controls.Add(bt8);
            Controls.Add(bt9);
            Controls.Add(btpow);
            Controls.Add(btsqrt);
            Controls.Add(btCE);
            Controls.Add(btplusminus);
            Controls.Add(btC);
            Controls.Add(btEqual);
            Controls.Add(btplus);
            Controls.Add(btminus);
            Controls.Add(btkrat);
            Controls.Add(btdeleno);
            Controls.Add(btDEL);
            Controls.Add(btMC);
            Controls.Add(btMR);
            Controls.Add(btMplus);
            Controls.Add(btMmin);
            Controls.Add(btMS);
            Controls.Add(disp1);
            Controls.Add(disp2);
            Controls.Add(paHi);
            ForeColor = SystemColors.ButtonFace;
            Margin = new Padding(3, 4, 3, 4);
            MinimumSize = new Size(493, 662);
            Name = "Kalkulačka";
            StartPosition = FormStartPosition.CenterScreen;
            Text = "Kalkulačka";
            Load += Kalkulačka_Load;
            paHi.ResumeLayout(false);
            panel1.ResumeLayout(false);
            paDe.ResumeLayout(false);
            ResumeLayout(false);
            PerformLayout();
        }

        #endregion
        private ImageList imageList1;
        private Panel paHi;
        private Button btHi;
        private Button btShift;
        private Panel paDe;
        private TextBox disp2;
        private TextBox disp1;
        private Button btHClear;
        private RichTextBox txtHistory;
        private Button btDEL;
        private Button btMS;
        private Button btMmin;
        private Button btMplus;
        private Button btMR;
        private Button btMC;
        private Button btC;
        private Button btCE;
        private Button btPercent;
        private Button btsqrt;
        private Button btpow;
        private Button btdelx;
        private Button btplusminus;
        private Button bt9;
        private Button bt8;
        private Button bt7;
        private Button bt6;
        private Button bt5;
        private Button bt4;
        private Button bt3;
        private Button bt2;
        private Button bt1;
        private Button bt0;
        private Button btcoma;
        private Button btdeleno;
        private Button btkrat;
        private Button btminus;
        private Button btplus;
        private Button btEqual;
        private Panel panel1;
        private Button button6;
        private Button button5;
        private Button button4;
        private Button button3;
        private Button button2;
        private Button button1;
        private Button pi;
        private Button button9;
        private Button button8;
        private Button powto;
    }
}
