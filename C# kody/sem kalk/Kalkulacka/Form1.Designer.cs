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
            paDe = new Panel();
            txtHistory = new RichTextBox();
            btHClear = new Button();
            disp2 = new TextBox();
            disp1 = new TextBox();
            btM = new Button();
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
            paHi.Name = "paHi";
            paHi.Size = new Size(439, 37);
            paHi.TabIndex = 0;
            // 
            // btShift
            // 
            btShift.BackColor = Color.FromArgb(64, 64, 64);
            btShift.Dock = DockStyle.Left;
            btShift.FlatAppearance.BorderColor = Color.White;
            btShift.FlatAppearance.MouseOverBackColor = Color.Black;
            btShift.FlatStyle = FlatStyle.Flat;
            btShift.ForeColor = SystemColors.Control;
            btShift.Location = new Point(0, 0);
            btShift.Margin = new Padding(0);
            btShift.Name = "btShift";
            btShift.Size = new Size(55, 37);
            btShift.TabIndex = 1;
            btShift.Text = "Shift";
            btShift.UseVisualStyleBackColor = false;
            // 
            // btHi
            // 
            btHi.BackColor = Color.FromArgb(64, 64, 64);
            btHi.Dock = DockStyle.Right;
            btHi.FlatAppearance.BorderColor = SystemColors.AppWorkspace;
            btHi.FlatAppearance.MouseOverBackColor = Color.Black;
            btHi.FlatStyle = FlatStyle.Flat;
            btHi.ForeColor = SystemColors.Control;
            btHi.Location = new Point(384, 0);
            btHi.Margin = new Padding(0);
            btHi.Name = "btHi";
            btHi.Size = new Size(55, 37);
            btHi.TabIndex = 0;
            btHi.Text = "History";
            btHi.UseVisualStyleBackColor = false;
            btHi.Click += btHi_Click;
            // 
            // paDe
            // 
            paDe.BackColor = SystemColors.WindowFrame;
            paDe.Controls.Add(txtHistory);
            paDe.Controls.Add(btHClear);
            paDe.Dock = DockStyle.Bottom;
            paDe.Location = new Point(0, 580);
            paDe.Name = "paDe";
            paDe.Size = new Size(439, 10);
            paDe.TabIndex = 1;
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
            txtHistory.Size = new Size(439, 0);
            txtHistory.TabIndex = 3;
            txtHistory.Text = "";
            // 
            // btHClear
            // 
            btHClear.Dock = DockStyle.Bottom;
            btHClear.FlatAppearance.BorderColor = SystemColors.AppWorkspace;
            btHClear.FlatAppearance.MouseOverBackColor = Color.Black;
            btHClear.FlatStyle = FlatStyle.Flat;
            btHClear.ForeColor = SystemColors.Control;
            btHClear.Location = new Point(0, -27);
            btHClear.Margin = new Padding(0);
            btHClear.Name = "btHClear";
            btHClear.Size = new Size(439, 37);
            btHClear.TabIndex = 2;
            btHClear.Text = "Clear";
            btHClear.UseVisualStyleBackColor = true;
            btHClear.Click += btHClear_Click;
            // 
            // disp2
            // 
            disp2.BackColor = SystemColors.ScrollBar;
            disp2.BorderStyle = BorderStyle.None;
            disp2.Dock = DockStyle.Top;
            disp2.Font = new Font("Segoe UI", 14.25F, FontStyle.Bold, GraphicsUnit.Point, 238);
            disp2.Location = new Point(0, 37);
            disp2.Margin = new Padding(0);
            disp2.Multiline = true;
            disp2.Name = "disp2";
            disp2.Size = new Size(439, 29);
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
            disp1.Location = new Point(0, 66);
            disp1.Margin = new Padding(0);
            disp1.Multiline = true;
            disp1.Name = "disp1";
            disp1.Size = new Size(439, 47);
            disp1.TabIndex = 3;
            disp1.Text = "0";
            disp1.TextAlign = HorizontalAlignment.Right;
            // 
            // btM
            // 
            btM.BackColor = Color.FromArgb(64, 64, 64);
            btM.FlatAppearance.BorderColor = Color.FromArgb(0, 0, 0, 0);
            btM.FlatAppearance.BorderSize = 0;
            btM.FlatAppearance.MouseOverBackColor = Color.Gray;
            btM.FlatStyle = FlatStyle.Flat;
            btM.Font = new Font("Segoe UI", 12F, FontStyle.Bold);
            btM.ForeColor = Color.White;
            btM.Location = new Point(380, 113);
            btM.Margin = new Padding(0);
            btM.Name = "btM";
            btM.Size = new Size(60, 36);
            btM.TabIndex = 4;
            btM.Text = "M";
            btM.UseVisualStyleBackColor = false;
            btM.Click += button1_Click_1;
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
            btDEL.Location = new Point(330, 158);
            btDEL.Margin = new Padding(0);
            btDEL.Name = "btDEL";
            btDEL.Size = new Size(109, 68);
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
            btMS.Location = new Point(304, 113);
            btMS.Margin = new Padding(0);
            btMS.Name = "btMS";
            btMS.Size = new Size(60, 36);
            btMS.TabIndex = 4;
            btMS.Text = "MS";
            btMS.UseVisualStyleBackColor = false;
            btMS.Click += button1_Click_1;
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
            btMmin.Location = new Point(228, 113);
            btMmin.Margin = new Padding(0);
            btMmin.Name = "btMmin";
            btMmin.Size = new Size(60, 36);
            btMmin.TabIndex = 4;
            btMmin.Text = "M-";
            btMmin.UseVisualStyleBackColor = false;
            btMmin.Click += button1_Click_1;
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
            btMplus.Location = new Point(152, 113);
            btMplus.Margin = new Padding(0);
            btMplus.Name = "btMplus";
            btMplus.Size = new Size(60, 36);
            btMplus.TabIndex = 4;
            btMplus.Text = "M+";
            btMplus.UseVisualStyleBackColor = false;
            btMplus.Click += button1_Click_1;
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
            btMR.Location = new Point(76, 113);
            btMR.Margin = new Padding(0);
            btMR.Name = "btMR";
            btMR.Size = new Size(60, 36);
            btMR.TabIndex = 4;
            btMR.Text = "MR";
            btMR.UseVisualStyleBackColor = false;
            btMR.Click += button1_Click_1;
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
            btMC.Location = new Point(0, 113);
            btMC.Margin = new Padding(0);
            btMC.Name = "btMC";
            btMC.Size = new Size(60, 36);
            btMC.TabIndex = 4;
            btMC.Text = "MC";
            btMC.UseVisualStyleBackColor = false;
            btMC.Click += button1_Click_1;
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
            btC.Location = new Point(220, 158);
            btC.Margin = new Padding(0);
            btC.Name = "btC";
            btC.Size = new Size(109, 68);
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
            btCE.Location = new Point(110, 158);
            btCE.Margin = new Padding(0);
            btCE.Name = "btCE";
            btCE.Size = new Size(109, 68);
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
            btPercent.Location = new Point(0, 158);
            btPercent.Margin = new Padding(0);
            btPercent.Name = "btPercent";
            btPercent.Size = new Size(109, 68);
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
            btsqrt.Location = new Point(220, 227);
            btsqrt.Margin = new Padding(0);
            btsqrt.Name = "btsqrt";
            btsqrt.Size = new Size(109, 68);
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
            btpow.Location = new Point(110, 227);
            btpow.Margin = new Padding(0);
            btpow.Name = "btpow";
            btpow.Size = new Size(109, 68);
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
            btdelx.Location = new Point(0, 227);
            btdelx.Margin = new Padding(0);
            btdelx.Name = "btdelx";
            btdelx.Size = new Size(109, 68);
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
            btplusminus.Location = new Point(0, 503);
            btplusminus.Margin = new Padding(0);
            btplusminus.Name = "btplusminus";
            btplusminus.Size = new Size(109, 68);
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
            bt9.Location = new Point(220, 296);
            bt9.Margin = new Padding(0);
            bt9.Name = "bt9";
            bt9.Size = new Size(109, 68);
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
            bt8.Location = new Point(110, 296);
            bt8.Margin = new Padding(0);
            bt8.Name = "bt8";
            bt8.Size = new Size(109, 68);
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
            bt7.Location = new Point(0, 296);
            bt7.Margin = new Padding(0);
            bt7.Name = "bt7";
            bt7.Size = new Size(109, 68);
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
            bt6.Location = new Point(220, 365);
            bt6.Margin = new Padding(0);
            bt6.Name = "bt6";
            bt6.Size = new Size(109, 68);
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
            bt5.Location = new Point(110, 365);
            bt5.Margin = new Padding(0);
            bt5.Name = "bt5";
            bt5.Size = new Size(109, 68);
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
            bt4.Location = new Point(0, 365);
            bt4.Margin = new Padding(0);
            bt4.Name = "bt4";
            bt4.Size = new Size(109, 68);
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
            bt3.Location = new Point(220, 434);
            bt3.Margin = new Padding(0);
            bt3.Name = "bt3";
            bt3.Size = new Size(109, 68);
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
            bt2.Location = new Point(110, 434);
            bt2.Margin = new Padding(0);
            bt2.Name = "bt2";
            bt2.Size = new Size(109, 68);
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
            bt1.Location = new Point(0, 434);
            bt1.Margin = new Padding(0);
            bt1.Name = "bt1";
            bt1.Size = new Size(109, 68);
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
            bt0.Location = new Point(110, 503);
            bt0.Margin = new Padding(0);
            bt0.Name = "bt0";
            bt0.Size = new Size(109, 68);
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
            btcoma.Location = new Point(220, 503);
            btcoma.Margin = new Padding(0);
            btcoma.Name = "btcoma";
            btcoma.Size = new Size(109, 68);
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
            btdeleno.Location = new Point(330, 227);
            btdeleno.Margin = new Padding(0);
            btdeleno.Name = "btdeleno";
            btdeleno.Size = new Size(109, 68);
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
            btkrat.Location = new Point(330, 296);
            btkrat.Margin = new Padding(0);
            btkrat.Name = "btkrat";
            btkrat.Size = new Size(109, 68);
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
            btminus.Location = new Point(330, 365);
            btminus.Margin = new Padding(0);
            btminus.Name = "btminus";
            btminus.Size = new Size(109, 68);
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
            btplus.Location = new Point(330, 434);
            btplus.Margin = new Padding(0);
            btplus.Name = "btplus";
            btplus.Size = new Size(109, 68);
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
            btEqual.Location = new Point(330, 503);
            btEqual.Margin = new Padding(0);
            btEqual.Name = "btEqual";
            btEqual.Size = new Size(109, 68);
            btEqual.TabIndex = 4;
            btEqual.Text = "=";
            btEqual.UseVisualStyleBackColor = false;
            btEqual.Click += btEquals_clik;
            // 
            // Kalkulačka
            // 
            AutoScaleDimensions = new SizeF(7F, 15F);
            AutoScaleMode = AutoScaleMode.Font;
            BackColor = SystemColors.WindowFrame;
            ClientSize = new Size(439, 590);
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
            Controls.Add(btM);
            Controls.Add(disp1);
            Controls.Add(disp2);
            Controls.Add(paHi);
            ForeColor = SystemColors.ButtonFace;
            MinimumSize = new Size(434, 508);
            Name = "Kalkulačka";
            StartPosition = FormStartPosition.CenterScreen;
            Text = "Kalkulačka";
            paHi.ResumeLayout(false);
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
        private Button btM;
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
    }
}
