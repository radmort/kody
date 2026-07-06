using System;
using System.Drawing;
using System.Drawing.Drawing2D;
using System.Windows.Forms;

namespace RectangleRotation
{

    public partial class Form1 : Form
    {
        PointF A, B, C, D; // vrcholy obdĺžnika
        PointF[] rect;  // aktuálny obdĺžnik (pred rotáciou)
        PointF[] originalRect;  // kópia pôvodného obdĺžnika — zostáva viditeľná ako prerušovaná čiara po rotácii
        PointF[] currentRect; // obdĺžnik počas animácie — interpolovaná poloha medzi pôvodnou a cieľovou
        PointF[] targetRect; // cieľová poloha obdĺžnika po dokončení rotácie
        Timer animationTimer = new Timer(); // časovač pre plynulú animáciu rotácie
        const int steps = 30; // počet krokov animácie — vyšší = plynulejší, nižší = rýchlejší
        int currentStep = 0; // aktuálny krok animácie
        double angle; // uhol rotácie v stupňoch zadaný používateľom
        PointF pivot;  // bod okolo ktorého sa obdĺžnik otáča
        bool isAnimating = false; // príznak či práve prebieha animácia

        // pevný svetový priestor 0–800 x 0–800, škáluje sa podľa veľkosti canvasu
        const float WorldSize = 800f; 
         
        const float GridStep = 40f;  // vzdialenosť medzi čiarami mriežky vo svetových jednotkách

        float scale = 1f; // aktuálna škála prepočtu svetových jednotiek na pixely


        // Konštruktor — inicializuje formulár a nastaví udalosti canvasu
        public Form1()
        {
            InitializeComponent();
            canvas.Paint += Canvas_Paint;
            canvas.Resize += (s, e) => canvas.Invalidate();
        }

        // Otvorí okno s informáciami o autorovi
        private void btnInformacie_Click(object sender, EventArgs e)
        {
            using (InformacieForm f = new InformacieForm())
                f.ShowDialog(this);
        }

        // Inicializácia po načítaní formulára — nastaví časovač animácie
        private void Form1_Load(object sender, EventArgs e)
        {
            animationTimer.Interval = 16; // ~60 FPS
            animationTimer.Tick += AnimationTimer_Tick;
        }

        // Vykreslí obdĺžnik na základe súradníc zadaných používateľom
        private void btnDraw_Click(object sender, EventArgs e)
        {
            float x1, y1, x2, y2;
            if (!ValidateInput(out x1, out y1, out x2, out y2)) return;

            // definícia vrcholov obdĺžnika zo súradníc bodu A a bodu C
            A = new PointF(x1, y1);
            B = new PointF(x2, y1);
            C = new PointF(x2, y2);
            D = new PointF(x1, y2);
            rect = new PointF[] { A, B, C, D };
            originalRect = null; // zresetuj pôvodný obdĺžnik pri novom kreslení

            UpdatePivot();
            currentRect = null;
            matrixBox.Text = "";
            canvas.Invalidate();
        }

        // Spustí animovanú rotáciu obdĺžnika o zadaný uhol okolo zvoleného pivotu
        private void btnRotate_Click(object sender, EventArgs e)
        {
            if (rect == null) return;
            if (!double.TryParse(txtAngle.Text, out angle))
            {
                MessageBox.Show("Uhol musí byť číslo v stupňoch.");
                return;
            }

            UpdatePivot();

            // zostrojenie transformačných matíc — riadkový zápis: P' = P · T1 · R · T2
            Matrix3x3 T1 = Matrix3x3.Translation(-pivot.X, -pivot.Y); // presunutie pivotu do počiatku
            Matrix3x3 R = Matrix3x3.Rotation(angle);                  // rotácia o zadaný uhol
            Matrix3x3 T2 = Matrix3x3.Translation(pivot.X, pivot.Y);   // presunutie späť na pôvodné miesto
            Matrix3x3 M = T1.Multiply(R).Multiply(T2);               // výsledná transformačná matica

            // aplikovanie výslednej matice na každý vrchol obdĺžnika
            targetRect = new PointF[4];
            for (int i = 0; i < rect.Length; i++)
                targetRect[i] = M.TransformPoint(rect[i]);

            // zobrazenie jednotlivých matíc v textboxe
            matrixBox.Text =
                $"T1 - presunutie do počiatku:\r\n{T1}\r\n" +
                $"R - rotácia {angle}°:\r\n{R}\r\n" +
                $"T2 - presunutie späť:\r\n{T2}\r\n" +
                $"M = T1 * R * T2:\r\n{M}";

            // uloženie pôvodnej polohy pred animáciou
            originalRect = (PointF[])rect.Clone();

            currentRect = (PointF[])rect.Clone();
            currentStep = 0;
            isAnimating = true;
            animationTimer.Start();
        }

        // Každý tik časovača posunie obdĺžnik o jeden krok smerom k cieľovej polohe
        private void AnimationTimer_Tick(object sender, EventArgs e)
        {
            currentStep++;

            // parameter interpolácie t: od 0.0 (začiatok) po 1.0 (koniec)
            float t = (float)currentStep / steps;

            // lineárna interpolácia každého vrcholu medzi pôvodnou a cieľovou polohou
            for (int i = 0; i < currentRect.Length; i++)
            {
                currentRect[i].X = rect[i].X + t * (targetRect[i].X - rect[i].X);
                currentRect[i].Y = rect[i].Y + t * (targetRect[i].Y - rect[i].Y);
            }

            canvas.Invalidate();

            // po poslednom kroku zastaví animáciu a nastaví obdĺžnik na cieľovú polohu
            if (currentStep >= steps)
            {
                animationTimer.Stop();
                rect = targetRect;
                isAnimating = false;
            }
        }

        // Vykreslí celý obsah canvasu — mriežku, osi, obdĺžniky a popisky
        private void Canvas_Paint(object sender, PaintEventArgs e)
        {
            Graphics g = e.Graphics;

            // výpočet škály podľa menšieho rozmeru canvasu — zaistí správne zobrazenie pri resize
            scale = Math.Min(canvas.Width, canvas.Height) / WorldSize;

            // zmena súradnicovej sústavy: bod (0,0) v ľavom dolnom rohu, Y smeruje nahor
            g.TranslateTransform(0, canvas.Height);
            g.ScaleTransform(scale, -scale);

            // vykreslenie mriežky a hlavných osí
            DrawGrid(g);
            g.DrawLine(new Pen(Color.Gray, 1.5f / scale), 0, 0, WorldSize, 0); // os X
            g.DrawLine(new Pen(Color.Gray, 1.5f / scale), 0, 0, 0, WorldSize); // os Y

            // pôvodný obdĺžnik pred rotáciou — prerušovaná modrá čiara s bodkami na vrcholoch
            if (originalRect != null)
            {
                Pen dashedBlue = new Pen(Color.Blue, 1.5f / scale);
                dashedBlue.DashStyle = DashStyle.Dash;
                g.DrawPolygon(dashedBlue, originalRect);
                DrawCornerDots(g, originalRect, Color.Blue);
            }

            // plný modrý obdĺžnik — zobrazí sa len pred prvou rotáciou
            if (rect != null && originalRect == null)
            {
                g.DrawPolygon(new Pen(Color.Blue, 2 / scale), rect);
                DrawLabels(g, new[] { A, B, C, D }, new[] { "A", "B", "C", "D" }, Brushes.Blue);
                g.FillEllipse(Brushes.Black, pivot.X - 5 / scale, pivot.Y - 5 / scale, 10 / scale, 10 / scale);
            }
            else if (rect != null && originalRect != null)
            {
                // po rotácii zobrazí len bod pivotu
                g.FillEllipse(Brushes.Black, pivot.X - 5 / scale, pivot.Y - 5 / scale, 10 / scale, 10 / scale);
            }

            // otočený obdĺžnik — červená plná čiara
            if (currentRect != null)
            {
                g.DrawPolygon(new Pen(Color.Red, 2 / scale), currentRect);
                DrawLabels(g, currentRect, new[] { "A'", "B'", "C'", "D'" }, Brushes.Red);
            }
        }

        // Vykreslí malé farebné bodky na vrcholoch obdĺžnika
        private void DrawCornerDots(Graphics g, PointF[] pts, Color color)
        {
            using (SolidBrush b = new SolidBrush(color))
            {
                float r = 4f / scale; // polomer bodky prispôsobený škále
                foreach (var p in pts)
                    g.FillEllipse(b, p.X - r, p.Y - r, r * 2, r * 2);
            }
        }

        // Vykreslí mriežku so svetlými čiarami a číslami na oboch osiach
        private void DrawGrid(Graphics g)
        {
            Pen gridPen = new Pen(Color.FromArgb(200, 220, 220), 0.5f / scale);
            Font lblFont = new Font("Arial", 7);
            Brush lblBrush = Brushes.DimGray;

            for (float v = 0; v <= WorldSize; v += GridStep)
            {
                g.DrawLine(gridPen, 0, v, WorldSize, v); // vodorovná čiara mriežky
                g.DrawLine(gridPen, v, 0, v, WorldSize); // zvislá čiara mriežky

                if (v == 0) continue; // počiatok nepopisujeme

                var saved = g.Transform.Clone();
                g.ResetTransform();

                // číslo na osi X (dole pri každej zvislej čiare)
                float screenX = v * scale;
                g.DrawString(((int)v).ToString(), lblFont, lblBrush, screenX - 8, canvas.Height - 15);

                // číslo na osi Y (vľavo pri každej vodorovnej čiare) — preskočí ak je príliš blízko vrchu
                float screenY = canvas.Height - v * scale;
                if (screenY > 15)
                    g.DrawString(((int)v).ToString(), lblFont, lblBrush, 2, screenY - 7);

                g.Transform = saved;
            }

            gridPen.Dispose();
            lblFont.Dispose();
        }

        // Vykreslí popisky vrcholov posunuté smerom von od stredu obdĺžnika
        private void DrawLabels(Graphics g, PointF[] pts, string[] labels, Brush brush)
        {
            // výpočet stredu obdĺžnika ako priemer súradníc všetkých vrcholov
            float cx = 0, cy = 0;
            foreach (var p in pts) { cx += p.X; cy += p.Y; }
            cx /= pts.Length;
            cy /= pts.Length;

            const float offset = 18f; // vzdialenosť popisku od vrcholu v pixeloch

            using (Font f = new Font("Arial", 11, FontStyle.Bold))
            {
                for (int i = 0; i < pts.Length; i++)
                {
                    var saved = g.Transform.Clone();
                    g.ResetTransform();

                    // prevod svetových súradníc vrcholu na súradnice obrazovky
                    float screenX = pts[i].X * scale;
                    float screenY = canvas.Height - pts[i].Y * scale;

                    // jednotkový vektor od stredu smerom k vrcholu
                    float dx = pts[i].X - cx;
                    float dy = pts[i].Y - cy;
                    float len = (float)Math.Sqrt(dx * dx + dy * dy);
                    if (len > 0) { dx /= len; dy /= len; }

                    // posunutie popisku von od stredu (dy prevrátené kvôli Y-flippu)
                    screenX += dx * offset;
                    screenY -= dy * offset;

                    g.DrawString(labels[i], f, brush, screenX - 6, screenY - 8);
                    g.Transform = saved;
                }
            }
        }

      
        // Nastaví bod pivotu podľa vybraného radio buttonu
        private void UpdatePivot()
        {
            if (rbA.Checked) pivot = A;
            else if (rbB.Checked) pivot = B;
            else if (rbC.Checked) pivot = C;
            else if (rbD.Checked) pivot = D;
        }

        // Obsluha zmeny vybraného pivotu — prekreslí canvas ak nie je animácia
        private void PivotChanged(object sender, EventArgs e)
        {
            if (isAnimating || rect == null) return;
            UpdatePivot();
            canvas.Invalidate();
        }

        private void menuStrip1_ItemClicked(object sender, ToolStripItemClickedEventArgs e) { }

        // Otvorí okno nápovedy
        private void napoveda_Click(object sender, EventArgs e)
        {
            using (NapovedaForm helpForm = new NapovedaForm())
                helpForm.ShowDialog(this);
        }

        private void canvas_Click(object sender, EventArgs e) { }

    
        // Overí vstupné súradnice — vracia false ak sú neplatné, záporné,
        // alebo ak x1 == x2 resp. y1 == y2 (degenerovaný obdĺžnik bez plochy)
        bool ValidateInput(out float x1, out float y1, out float x2, out float y2)
        {
            x1 = y1 = x2 = y2 = 0;

            // overenie či sú všetky hodnoty platné čísla
            if (!float.TryParse(txtX1.Text, out x1) || !float.TryParse(txtY1.Text, out y1) ||
                !float.TryParse(txtX2.Text, out x2) || !float.TryParse(txtY2.Text, out y2))
            {
                MessageBox.Show("Súradnice musia byť čísla.");
                return false;
            }

            // overenie či sú súradnice nezáporné
            if (x1 < 0 || y1 < 0 || x2 < 0 || y2 < 0)
            {
                MessageBox.Show("Súradnice nemôžu byť záporné.");
                return false;
            }

            // overenie či obdĺžnik nie je degenerovaný (nulová šírka alebo výška)
            if (x1 == x2)
            {
                MessageBox.Show("Súradnice x1 a x2 nemôžu byť rovnaké — štvoruholníka by nemal šírku.");
                return false;
            }
            if (y1 == y2)
            {
                MessageBox.Show("Súradnice y1 a y2 nemôžu byť rovnaké — štvoruholníka by nemal výšku.");
                return false;
            }

            return true;
        }
    }


    // 3x3 homogénna transformačná matica 
    //   P' = [x, y, 1] · M
    public class Matrix3x3
    {
        // interné uloženie hodnôt matice v dvojrozmernom poli
        private double[,] m;


        // Konštruktor — inicializuje maticu zo zadaného dvojrozmerného poľa
        public Matrix3x3(double[,] values) { m = values; }

        // indexer pre pohodlný prístup k prvkom matice cez [riadok, stĺpec]
        public double this[int row, int col]
        {
            get { return m[row, col]; }
            set { m[row, col] = value; }
        }

        // Vráti jednotkovú (identickú) maticu — transformácia nemení žiadny bod
        public static Matrix3x3 Identity()
        {
            return new Matrix3x3(new double[,]
            {
                { 1, 0, 0 },
                { 0, 1, 0 },
                { 0, 0, 1 }
            });
        }

        // Vráti maticu posunutia o vektor (tx, ty) — riadkový zápis:
        public static Matrix3x3 Translation(double tx, double ty)
        {
            return new Matrix3x3(new double[,]
            {
                {  1,  0, 0 },
                {  0,  1, 0 },
                { tx, ty, 1 }
            });
        }

        
        // Vráti maticu rotácie o zadaný uhol v stupňoch — riadkový zápis:
        public static Matrix3x3 Rotation(double angleDeg)
        {
            double rad = angleDeg * Math.PI / 180.0;
            double cos = Math.Cos(rad);
            double sin = Math.Sin(rad);
            return new Matrix3x3(new double[,]
            {
                {  cos, sin, 0 },
                { -sin, cos, 0 },
                {    0,   0, 1 }
            });
        }

        // Vráti súčin tejto matice s maticou other (this · other)
        public Matrix3x3 Multiply(Matrix3x3 other)
        {
            var result = new double[3, 3];
            for (int row = 0; row < 3; row++)
                for (int col = 0; col < 3; col++)
                    for (int k = 0; k < 3; k++)
                        result[row, col] += this[row, k] * other[k, col];
            return new Matrix3x3(result);
        }

        
        // Aplikuje maticu na bod p a vráti transformovaný bod
        // Výpočet: [x', y', 1] = [x, y, 1] · M
        public PointF TransformPoint(PointF p)
        {
            double x = p.X * this[0, 0] + p.Y * this[1, 0] + this[2, 0];
            double y = p.X * this[0, 1] + p.Y * this[1, 1] + this[2, 1];
            return new PointF((float)x, (float)y);
        }

        
        // Vráti textovú reprezentáciu matice pre zobrazenie v matrixBox
        public override string ToString()
        {
            string r = "";
            for (int row = 0; row < 3; row++)
            {
                r += "| ";
                for (int col = 0; col < 3; col++)
                    r += $"{this[row, col],7:F2} ";
                r += "|\r\n";
            }
            return r;
        }
    }
}