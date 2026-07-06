namespace RectangleRotation
{
    partial class InformacieForm
    {
        private System.ComponentModel.IContainer components = null;

        protected override void Dispose(bool disposing)
        {
            if (disposing && (components != null))
                components.Dispose();
            base.Dispose(disposing);
        }

        #region Windows Form Designer generated code

        private void InitializeComponent()
        {
            this.lblMeno = new System.Windows.Forms.Label();
            this.lblPredmet = new System.Windows.Forms.Label();
            this.lblSkupina = new System.Windows.Forms.Label();
            this.lblRocnik = new System.Windows.Forms.Label();
            this.btnZatvoriť = new System.Windows.Forms.Button();
            this.SuspendLayout();
            // 
            // lblMeno
            // 
            this.lblMeno.AutoSize = true;
            this.lblMeno.Font = new System.Drawing.Font("Arial", 11F);
            this.lblMeno.Location = new System.Drawing.Point(15, 16);
            this.lblMeno.Margin = new System.Windows.Forms.Padding(2, 0, 2, 0);
            this.lblMeno.Name = "lblMeno";
            this.lblMeno.Size = new System.Drawing.Size(179, 17);
            this.lblMeno.TabIndex = 0;
            this.lblMeno.Text = "Meno:      Radoslav Šedivý";
            // 
            // lblPredmet
            // 
            this.lblPredmet.AutoSize = true;
            this.lblPredmet.Font = new System.Drawing.Font("Arial", 11F);
            this.lblPredmet.Location = new System.Drawing.Point(15, 45);
            this.lblPredmet.Margin = new System.Windows.Forms.Padding(2, 0, 2, 0);
            this.lblPredmet.Name = "lblPredmet";
            this.lblPredmet.Size = new System.Drawing.Size(134, 17);
            this.lblPredmet.TabIndex = 1;
            this.lblPredmet.Text = "Predmet:   PGČSO";
            // 
            // lblSkupina
            // 
            this.lblSkupina.AutoSize = true;
            this.lblSkupina.Font = new System.Drawing.Font("Arial", 11F);
            this.lblSkupina.Location = new System.Drawing.Point(15, 73);
            this.lblSkupina.Margin = new System.Windows.Forms.Padding(2, 0, 2, 0);
            this.lblSkupina.Name = "lblSkupina";
            this.lblSkupina.Size = new System.Drawing.Size(117, 17);
            this.lblSkupina.TabIndex = 2;
            this.lblSkupina.Text = "Skupina:   AiA-15";
            // 
            // lblRocnik
            // 
            this.lblRocnik.AutoSize = true;
            this.lblRocnik.Font = new System.Drawing.Font("Arial", 11F);
            this.lblRocnik.Location = new System.Drawing.Point(15, 102);
            this.lblRocnik.Margin = new System.Windows.Forms.Padding(2, 0, 2, 0);
            this.lblRocnik.Name = "lblRocnik";
            this.lblRocnik.Size = new System.Drawing.Size(105, 17);
            this.lblRocnik.TabIndex = 3;
            this.lblRocnik.Text = "Ročník:    3. bc";
            // 
            // btnZatvoriť
            // 
            this.btnZatvoriť.Location = new System.Drawing.Point(82, 134);
            this.btnZatvoriť.Margin = new System.Windows.Forms.Padding(2, 2, 2, 2);
            this.btnZatvoriť.Name = "btnZatvoriť";
            this.btnZatvoriť.Size = new System.Drawing.Size(60, 24);
            this.btnZatvoriť.TabIndex = 0;
            this.btnZatvoriť.Text = "Zatvoriť";
            this.btnZatvoriť.UseVisualStyleBackColor = true;
            this.btnZatvoriť.Click += new System.EventHandler(this.btnZatvoriť_Click);
            // 
            // InformacieForm
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(6F, 13F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.ClientSize = new System.Drawing.Size(225, 171);
            this.Controls.Add(this.lblMeno);
            this.Controls.Add(this.lblPredmet);
            this.Controls.Add(this.lblSkupina);
            this.Controls.Add(this.lblRocnik);
            this.Controls.Add(this.btnZatvoriť);
            this.FormBorderStyle = System.Windows.Forms.FormBorderStyle.FixedDialog;
            this.Margin = new System.Windows.Forms.Padding(2, 2, 2, 2);
            this.MaximizeBox = false;
            this.MinimizeBox = false;
            this.Name = "InformacieForm";
            this.StartPosition = System.Windows.Forms.FormStartPosition.CenterParent;
            this.Text = "Informácie";
            this.Load += new System.EventHandler(this.InformacieForm_Load);
            this.ResumeLayout(false);
            this.PerformLayout();

        }

        #endregion

        private System.Windows.Forms.Label lblMeno;
        private System.Windows.Forms.Label lblPredmet;
        private System.Windows.Forms.Label lblSkupina;
        private System.Windows.Forms.Label lblRocnik;
        private System.Windows.Forms.Button btnZatvoriť;
    }
}
