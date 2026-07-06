using System;

namespace RectangleRotation
{
    partial class Form1
    {
        /// <summary>
        /// Required designer variable.
        /// </summary>
        private System.ComponentModel.IContainer components = null;

        /// <summary>
        /// Clean up any resources being used.
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
        private void nápoveďToolStripMenuItem_Click(object sender, EventArgs e)
        {
            using (NapovedaForm helpForm = new NapovedaForm())
                helpForm.ShowDialog(this);
        }

        private void infoToolStripMenuItem_Click(object sender, EventArgs e)
        {
            using (InformacieForm f = new InformacieForm())
                f.ShowDialog(this);
        }
        #region Windows Form Designer generated code

        /// <summary>
        /// Required method for Designer support - do not modify
        /// the contents of this method with the code editor.
        /// </summary>
        private void InitializeComponent()
        {
            this.canvas = new System.Windows.Forms.PictureBox();
            this.btnDraw = new System.Windows.Forms.Button();
            this.btnRotate = new System.Windows.Forms.Button();
            this.x1 = new System.Windows.Forms.Label();
            this.y1 = new System.Windows.Forms.Label();
            this.x2 = new System.Windows.Forms.Label();
            this.y2 = new System.Windows.Forms.Label();
            this.txtX1 = new System.Windows.Forms.TextBox();
            this.txtY1 = new System.Windows.Forms.TextBox();
            this.txtX2 = new System.Windows.Forms.TextBox();
            this.txtY2 = new System.Windows.Forms.TextBox();
            this.txtAngle = new System.Windows.Forms.TextBox();
            this.Angle = new System.Windows.Forms.Label();
            this.groupBox1 = new System.Windows.Forms.GroupBox();
            this.matrixBox = new System.Windows.Forms.TextBox();
            this.label7 = new System.Windows.Forms.Label();
            this.label6 = new System.Windows.Forms.Label();
            this.label5 = new System.Windows.Forms.Label();
            this.label4 = new System.Windows.Forms.Label();
            this.label3 = new System.Windows.Forms.Label();
            this.rbD = new System.Windows.Forms.RadioButton();
            this.rbC = new System.Windows.Forms.RadioButton();
            this.rbB = new System.Windows.Forms.RadioButton();
            this.rbA = new System.Windows.Forms.RadioButton();
            this.label2 = new System.Windows.Forms.Label();
            this.label1 = new System.Windows.Forms.Label();
            this.menuStrip1 = new System.Windows.Forms.MenuStrip();
            this.Napoved = new System.Windows.Forms.ToolStripMenuItem();
            this.Info = new System.Windows.Forms.ToolStripMenuItem();
            ((System.ComponentModel.ISupportInitialize)(this.canvas)).BeginInit();
            this.groupBox1.SuspendLayout();
            this.menuStrip1.SuspendLayout();
            this.SuspendLayout();
            // 
            // canvas
            // 
            this.canvas.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.canvas.BackColor = System.Drawing.Color.White;
            this.canvas.Location = new System.Drawing.Point(0, 0);
            this.canvas.Name = "canvas";
            this.canvas.Size = new System.Drawing.Size(794, 587);
            this.canvas.TabIndex = 0;
            this.canvas.TabStop = false;
            this.canvas.Click += new System.EventHandler(this.canvas_Click);
            // 
            // btnDraw
            // 
            this.btnDraw.Location = new System.Drawing.Point(16, 191);
            this.btnDraw.Name = "btnDraw";
            this.btnDraw.Size = new System.Drawing.Size(77, 29);
            this.btnDraw.TabIndex = 1;
            this.btnDraw.Text = "Vykresliť";
            this.btnDraw.UseVisualStyleBackColor = true;
            this.btnDraw.Click += new System.EventHandler(this.btnDraw_Click);
            // 
            // btnRotate
            // 
            this.btnRotate.Location = new System.Drawing.Point(16, 345);
            this.btnRotate.Name = "btnRotate";
            this.btnRotate.Size = new System.Drawing.Size(77, 29);
            this.btnRotate.TabIndex = 2;
            this.btnRotate.Text = "Otočiť";
            this.btnRotate.UseVisualStyleBackColor = true;
            this.btnRotate.Click += new System.EventHandler(this.btnRotate_Click);
            // 
            // x1
            // 
            this.x1.AutoSize = true;
            this.x1.Location = new System.Drawing.Point(15, 68);
            this.x1.Name = "x1";
            this.x1.Size = new System.Drawing.Size(18, 13);
            this.x1.TabIndex = 3;
            this.x1.Text = "x1";
            // 
            // y1
            // 
            this.y1.AutoSize = true;
            this.y1.Location = new System.Drawing.Point(15, 94);
            this.y1.Name = "y1";
            this.y1.Size = new System.Drawing.Size(18, 13);
            this.y1.TabIndex = 4;
            this.y1.Text = "y1";
            // 
            // x2
            // 
            this.x2.AutoSize = true;
            this.x2.Location = new System.Drawing.Point(15, 139);
            this.x2.Name = "x2";
            this.x2.Size = new System.Drawing.Size(18, 13);
            this.x2.TabIndex = 5;
            this.x2.Text = "x2";
            // 
            // y2
            // 
            this.y2.AutoSize = true;
            this.y2.Location = new System.Drawing.Point(15, 169);
            this.y2.Name = "y2";
            this.y2.Size = new System.Drawing.Size(18, 13);
            this.y2.TabIndex = 6;
            this.y2.Text = "y2";
            // 
            // txtX1
            // 
            this.txtX1.Location = new System.Drawing.Point(39, 65);
            this.txtX1.Name = "txtX1";
            this.txtX1.Size = new System.Drawing.Size(100, 20);
            this.txtX1.TabIndex = 7;
            // 
            // txtY1
            // 
            this.txtY1.Location = new System.Drawing.Point(39, 91);
            this.txtY1.Name = "txtY1";
            this.txtY1.Size = new System.Drawing.Size(100, 20);
            this.txtY1.TabIndex = 8;
            // 
            // txtX2
            // 
            this.txtX2.Location = new System.Drawing.Point(39, 136);
            this.txtX2.Name = "txtX2";
            this.txtX2.Size = new System.Drawing.Size(100, 20);
            this.txtX2.TabIndex = 9;
            // 
            // txtY2
            // 
            this.txtY2.Location = new System.Drawing.Point(39, 162);
            this.txtY2.Name = "txtY2";
            this.txtY2.Size = new System.Drawing.Size(100, 20);
            this.txtY2.TabIndex = 10;
            // 
            // txtAngle
            // 
            this.txtAngle.Location = new System.Drawing.Point(61, 307);
            this.txtAngle.Name = "txtAngle";
            this.txtAngle.Size = new System.Drawing.Size(100, 20);
            this.txtAngle.TabIndex = 11;
            // 
            // Angle
            // 
            this.Angle.AutoSize = true;
            this.Angle.Location = new System.Drawing.Point(15, 310);
            this.Angle.Name = "Angle";
            this.Angle.Size = new System.Drawing.Size(40, 13);
            this.Angle.TabIndex = 12;
            this.Angle.Text = "uhol [°]";
            // 
            // groupBox1
            // 
            this.groupBox1.BackColor = System.Drawing.SystemColors.InactiveCaption;
            this.groupBox1.Controls.Add(this.matrixBox);
            this.groupBox1.Controls.Add(this.label7);
            this.groupBox1.Controls.Add(this.label6);
            this.groupBox1.Controls.Add(this.label5);
            this.groupBox1.Controls.Add(this.label4);
            this.groupBox1.Controls.Add(this.label3);
            this.groupBox1.Controls.Add(this.rbD);
            this.groupBox1.Controls.Add(this.rbC);
            this.groupBox1.Controls.Add(this.rbB);
            this.groupBox1.Controls.Add(this.rbA);
            this.groupBox1.Controls.Add(this.label2);
            this.groupBox1.Controls.Add(this.label1);
            this.groupBox1.Controls.Add(this.txtX1);
            this.groupBox1.Controls.Add(this.btnRotate);
            this.groupBox1.Controls.Add(this.txtAngle);
            this.groupBox1.Controls.Add(this.Angle);
            this.groupBox1.Controls.Add(this.x1);
            this.groupBox1.Controls.Add(this.txtY1);
            this.groupBox1.Controls.Add(this.txtY2);
            this.groupBox1.Controls.Add(this.btnDraw);
            this.groupBox1.Controls.Add(this.y1);
            this.groupBox1.Controls.Add(this.y2);
            this.groupBox1.Controls.Add(this.txtX2);
            this.groupBox1.Controls.Add(this.x2);
            this.groupBox1.Controls.Add(this.menuStrip1);
            this.groupBox1.Dock = System.Windows.Forms.DockStyle.Right;
            this.groupBox1.Location = new System.Drawing.Point(794, 0);
            this.groupBox1.Name = "groupBox1";
            this.groupBox1.Size = new System.Drawing.Size(217, 587);
            this.groupBox1.TabIndex = 13;
            this.groupBox1.TabStop = false;
            // 
            // matrixBox
            // 
            this.matrixBox.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left)));
            this.matrixBox.Location = new System.Drawing.Point(4, 380);
            this.matrixBox.Multiline = true;
            this.matrixBox.Name = "matrixBox";
            this.matrixBox.ScrollBars = System.Windows.Forms.ScrollBars.Vertical;
            this.matrixBox.Size = new System.Drawing.Size(205, 179);
            this.matrixBox.TabIndex = 26;
            // 
            // label7
            // 
            this.label7.AutoSize = true;
            this.label7.Location = new System.Drawing.Point(140, 165);
            this.label7.Name = "label7";
            this.label7.Size = new System.Drawing.Size(70, 13);
            this.label7.TabIndex = 25;
            this.label7.Text = "od: 0 do: 750";
            // 
            // label6
            // 
            this.label6.AutoSize = true;
            this.label6.Location = new System.Drawing.Point(140, 139);
            this.label6.Name = "label6";
            this.label6.Size = new System.Drawing.Size(70, 13);
            this.label6.TabIndex = 24;
            this.label6.Text = "od: 0 do: 750";
            // 
            // label5
            // 
            this.label5.AutoSize = true;
            this.label5.Location = new System.Drawing.Point(140, 94);
            this.label5.Name = "label5";
            this.label5.Size = new System.Drawing.Size(70, 13);
            this.label5.TabIndex = 23;
            this.label5.Text = "od: 0 do: 750";
            // 
            // label4
            // 
            this.label4.AutoSize = true;
            this.label4.Location = new System.Drawing.Point(140, 68);
            this.label4.Name = "label4";
            this.label4.Size = new System.Drawing.Size(70, 13);
            this.label4.TabIndex = 22;
            this.label4.Text = "od: 0 do: 750";
            // 
            // label3
            // 
            this.label3.AutoSize = true;
            this.label3.Location = new System.Drawing.Point(15, 233);
            this.label3.Name = "label3";
            this.label3.Size = new System.Drawing.Size(58, 13);
            this.label3.TabIndex = 19;
            this.label3.Text = "Vyber bod:";
            // 
            // rbD
            // 
            this.rbD.AutoSize = true;
            this.rbD.Location = new System.Drawing.Point(15, 252);
            this.rbD.Name = "rbD";
            this.rbD.Size = new System.Drawing.Size(33, 17);
            this.rbD.TabIndex = 18;
            this.rbD.Text = "D";
            this.rbD.UseVisualStyleBackColor = true;
            this.rbD.CheckedChanged += new System.EventHandler(this.PivotChanged);
            // 
            // rbC
            // 
            this.rbC.AutoSize = true;
            this.rbC.Location = new System.Drawing.Point(54, 252);
            this.rbC.Name = "rbC";
            this.rbC.Size = new System.Drawing.Size(32, 17);
            this.rbC.TabIndex = 17;
            this.rbC.Text = "C";
            this.rbC.UseVisualStyleBackColor = true;
            this.rbC.CheckedChanged += new System.EventHandler(this.PivotChanged);
            // 
            // rbB
            // 
            this.rbB.AutoSize = true;
            this.rbB.Location = new System.Drawing.Point(54, 275);
            this.rbB.Name = "rbB";
            this.rbB.Size = new System.Drawing.Size(32, 17);
            this.rbB.TabIndex = 16;
            this.rbB.Text = "B";
            this.rbB.UseVisualStyleBackColor = true;
            this.rbB.CheckedChanged += new System.EventHandler(this.PivotChanged);
            // 
            // rbA
            // 
            this.rbA.AutoSize = true;
            this.rbA.Checked = true;
            this.rbA.Location = new System.Drawing.Point(15, 275);
            this.rbA.Name = "rbA";
            this.rbA.Size = new System.Drawing.Size(32, 17);
            this.rbA.TabIndex = 15;
            this.rbA.TabStop = true;
            this.rbA.Text = "A";
            this.rbA.UseVisualStyleBackColor = true;
            this.rbA.CheckedChanged += new System.EventHandler(this.PivotChanged);
            // 
            // label2
            // 
            this.label2.AutoSize = true;
            this.label2.Location = new System.Drawing.Point(47, 120);
            this.label2.Name = "label2";
            this.label2.Size = new System.Drawing.Size(92, 13);
            this.label2.TabIndex = 14;
            this.label2.Text = "Parametre bodu C";
            // 
            // label1
            // 
            this.label1.AutoSize = true;
            this.label1.Location = new System.Drawing.Point(47, 49);
            this.label1.Name = "label1";
            this.label1.Size = new System.Drawing.Size(92, 13);
            this.label1.TabIndex = 13;
            this.label1.Text = "Parametre bodu A";
            // 
            // menuStrip1
            // 
            this.menuStrip1.Items.AddRange(new System.Windows.Forms.ToolStripItem[] {
            this.Napoved,
            this.Info});
            this.menuStrip1.Location = new System.Drawing.Point(3, 16);
            this.menuStrip1.Name = "menuStrip1";
            this.menuStrip1.Size = new System.Drawing.Size(211, 24);
            this.menuStrip1.TabIndex = 27;
            this.menuStrip1.Text = "menu";
            // 
            // Napoved
            // 
            this.Napoved.Name = "Napoved";
            this.Napoved.Size = new System.Drawing.Size(68, 20);
            this.Napoved.Text = "Nápoveď";
            this.Napoved.Click += new System.EventHandler(this.napoveda_Click);
            // 
            // Info
            // 
            this.Info.Name = "Info";
            this.Info.Size = new System.Drawing.Size(40, 20);
            this.Info.Text = "Info";
            this.Info.Click += new System.EventHandler(this.btnInformacie_Click);
            // 
            // Form1
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(6F, 13F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.ClientSize = new System.Drawing.Size(1011, 587);
            this.Controls.Add(this.groupBox1);
            this.Controls.Add(this.canvas);
            this.Name = "Form1";
            this.Text = "Form1";
            this.Load += new System.EventHandler(this.Form1_Load);
            ((System.ComponentModel.ISupportInitialize)(this.canvas)).EndInit();
            this.groupBox1.ResumeLayout(false);
            this.groupBox1.PerformLayout();
            this.menuStrip1.ResumeLayout(false);
            this.menuStrip1.PerformLayout();
            this.ResumeLayout(false);

        }

        #endregion

        private System.Windows.Forms.PictureBox canvas;
        private System.Windows.Forms.Button btnDraw;
        private System.Windows.Forms.Button btnRotate;
        private System.Windows.Forms.Label x1;
        private System.Windows.Forms.Label y1;
        private System.Windows.Forms.Label x2;
        private System.Windows.Forms.Label y2;
        private System.Windows.Forms.TextBox txtX1;
        private System.Windows.Forms.TextBox txtY1;
        private System.Windows.Forms.TextBox txtX2;
        private System.Windows.Forms.TextBox txtY2;
        private System.Windows.Forms.TextBox txtAngle;
        private System.Windows.Forms.Label Angle;
        private System.Windows.Forms.GroupBox groupBox1;
        private System.Windows.Forms.Label label1;
        private System.Windows.Forms.Label label2;
        private System.Windows.Forms.RadioButton rbD;
        private System.Windows.Forms.RadioButton rbC;
        private System.Windows.Forms.RadioButton rbB;
        private System.Windows.Forms.RadioButton rbA;
        private System.Windows.Forms.Label label3;
        private System.Windows.Forms.Label label5;
        private System.Windows.Forms.Label label4;
        private System.Windows.Forms.Label label7;
        private System.Windows.Forms.Label label6;
        private System.Windows.Forms.TextBox matrixBox;
        private System.Windows.Forms.MenuStrip menuStrip1;
        private System.Windows.Forms.ToolStripMenuItem Napoved;
        private System.Windows.Forms.ToolStripMenuItem Info;
    }

}

