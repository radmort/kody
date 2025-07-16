
namespace CvPon14_4_25
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

        #region Windows Form Designer generated code

        /// <summary>
        /// Required method for Designer support - do not modify
        /// the contents of this method with the code editor.
        /// </summary>
        private void InitializeComponent()
        {
            this.components = new System.ComponentModel.Container();
            this.textBox1 = new System.Windows.Forms.TextBox();
            this.menuStrip1 = new System.Windows.Forms.MenuStrip();
            this.suborToolStripMenuItem = new System.Windows.Forms.ToolStripMenuItem();
            this.upravitToolStripMenuItem = new System.Windows.Forms.ToolStripMenuItem();
            this.contextMenuStrip1 = new System.Windows.Forms.ContextMenuStrip(this.components);
            this.zobrazitToolStripMenuItem = new System.Windows.Forms.ToolStripMenuItem();
            this.otvoritToolStripMenuItem = new System.Windows.Forms.ToolStripMenuItem();
            this.ulozitToolStripMenuItem = new System.Windows.Forms.ToolStripMenuItem();
            this.koniecToolStripMenuItem = new System.Windows.Forms.ToolStripMenuItem();
            this.zalomitTextToolStripMenuItem = new System.Windows.Forms.ToolStripMenuItem();
            this.openFileDialog1 = new System.Windows.Forms.OpenFileDialog();
            this.saveFileDialog1 = new System.Windows.Forms.SaveFileDialog();
            this.menuStrip1.SuspendLayout();
            this.SuspendLayout();
            // 
            // textBox1
            // 
            this.textBox1.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.textBox1.Location = new System.Drawing.Point(0, 27);
            this.textBox1.MinimumSize = new System.Drawing.Size(250, 150);
            this.textBox1.Multiline = true;
            this.textBox1.Name = "textBox1";
            this.textBox1.ScrollBars = System.Windows.Forms.ScrollBars.Both;
            this.textBox1.Size = new System.Drawing.Size(850, 411);
            this.textBox1.TabIndex = 0;
            // 
            // menuStrip1
            // 
            this.menuStrip1.Items.AddRange(new System.Windows.Forms.ToolStripItem[] {
            this.suborToolStripMenuItem,
            this.upravitToolStripMenuItem,
            this.zobrazitToolStripMenuItem});
            this.menuStrip1.Location = new System.Drawing.Point(0, 0);
            this.menuStrip1.Name = "menuStrip1";
            this.menuStrip1.Size = new System.Drawing.Size(850, 24);
            this.menuStrip1.TabIndex = 1;
            this.menuStrip1.Text = "menuStrip1";
            // 
            // suborToolStripMenuItem
            // 
            this.suborToolStripMenuItem.DropDownItems.AddRange(new System.Windows.Forms.ToolStripItem[] {
            this.otvoritToolStripMenuItem,
            this.ulozitToolStripMenuItem,
            this.koniecToolStripMenuItem});
            this.suborToolStripMenuItem.Name = "suborToolStripMenuItem";
            this.suborToolStripMenuItem.Size = new System.Drawing.Size(50, 20);
            this.suborToolStripMenuItem.Text = "Subor";
            // 
            // upravitToolStripMenuItem
            // 
            this.upravitToolStripMenuItem.Name = "upravitToolStripMenuItem";
            this.upravitToolStripMenuItem.Size = new System.Drawing.Size(57, 20);
            this.upravitToolStripMenuItem.Text = "Upravit";
            // 
            // contextMenuStrip1
            // 
            this.contextMenuStrip1.Name = "contextMenuStrip1";
            this.contextMenuStrip1.Size = new System.Drawing.Size(61, 4);
            // 
            // zobrazitToolStripMenuItem
            // 
            this.zobrazitToolStripMenuItem.DropDownItems.AddRange(new System.Windows.Forms.ToolStripItem[] {
            this.zalomitTextToolStripMenuItem});
            this.zobrazitToolStripMenuItem.Name = "zobrazitToolStripMenuItem";
            this.zobrazitToolStripMenuItem.Size = new System.Drawing.Size(62, 20);
            this.zobrazitToolStripMenuItem.Text = "Zobrazit";
            // 
            // otvoritToolStripMenuItem
            // 
            this.otvoritToolStripMenuItem.Name = "otvoritToolStripMenuItem";
            this.otvoritToolStripMenuItem.Size = new System.Drawing.Size(180, 22);
            this.otvoritToolStripMenuItem.Text = "Otvorit";
            this.otvoritToolStripMenuItem.Click += new System.EventHandler(this.otvoritToolStripMenuItem_Click);
            // 
            // ulozitToolStripMenuItem
            // 
            this.ulozitToolStripMenuItem.Name = "ulozitToolStripMenuItem";
            this.ulozitToolStripMenuItem.Size = new System.Drawing.Size(180, 22);
            this.ulozitToolStripMenuItem.Text = "Ulozit";
            this.ulozitToolStripMenuItem.Click += new System.EventHandler(this.ulozitToolStripMenuItem_Click);
            // 
            // koniecToolStripMenuItem
            // 
            this.koniecToolStripMenuItem.Name = "koniecToolStripMenuItem";
            this.koniecToolStripMenuItem.Size = new System.Drawing.Size(180, 22);
            this.koniecToolStripMenuItem.Text = "Koniec";
            this.koniecToolStripMenuItem.Click += new System.EventHandler(this.koniecToolStripMenuItem_Click);
            // 
            // zalomitTextToolStripMenuItem
            // 
            this.zalomitTextToolStripMenuItem.Name = "zalomitTextToolStripMenuItem";
            this.zalomitTextToolStripMenuItem.Size = new System.Drawing.Size(180, 22);
            this.zalomitTextToolStripMenuItem.Text = "Zalomit text";
            // 
            // openFileDialog1
            // 
            this.openFileDialog1.FileName = "openFileDialog1";
            this.openFileDialog1.Filter = "Vsetky Subory|*.*|Textove subory|*.txt|DDl subory|*.ddl";
            this.openFileDialog1.FilterIndex = 2;
            this.openFileDialog1.Title = "Otvorit subor";
            // 
            // saveFileDialog1
            // 
            this.saveFileDialog1.Filter = "Vsetky Subory|*.*|Textove subory|*.txt|DDl subory|*.ddl";
            this.saveFileDialog1.FilterIndex = 2;
            this.saveFileDialog1.Title = "Ulozit subor";
            this.saveFileDialog1.FileOk += new System.ComponentModel.CancelEventHandler(this.saveFileDialog1_FileOk);
            // 
            // Form1
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(6F, 13F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.ClientSize = new System.Drawing.Size(850, 434);
            this.Controls.Add(this.textBox1);
            this.Controls.Add(this.menuStrip1);
            this.MainMenuStrip = this.menuStrip1;
            this.MinimumSize = new System.Drawing.Size(250, 150);
            this.Name = "Form1";
            this.Text = "Notepad";
            this.FormClosing += new System.Windows.Forms.FormClosingEventHandler(this.Form1_FormClosing);
            this.menuStrip1.ResumeLayout(false);
            this.menuStrip1.PerformLayout();
            this.ResumeLayout(false);
            this.PerformLayout();

        }

        #endregion

        private System.Windows.Forms.TextBox textBox1;
        private System.Windows.Forms.MenuStrip menuStrip1;
        private System.Windows.Forms.ToolStripMenuItem suborToolStripMenuItem;
        private System.Windows.Forms.ToolStripMenuItem otvoritToolStripMenuItem;
        private System.Windows.Forms.ToolStripMenuItem ulozitToolStripMenuItem;
        private System.Windows.Forms.ToolStripMenuItem koniecToolStripMenuItem;
        private System.Windows.Forms.ToolStripMenuItem upravitToolStripMenuItem;
        private System.Windows.Forms.ToolStripMenuItem zobrazitToolStripMenuItem;
        private System.Windows.Forms.ToolStripMenuItem zalomitTextToolStripMenuItem;
        private System.Windows.Forms.ContextMenuStrip contextMenuStrip1;
        private System.Windows.Forms.OpenFileDialog openFileDialog1;
        private System.Windows.Forms.SaveFileDialog saveFileDialog1;
    }
}

