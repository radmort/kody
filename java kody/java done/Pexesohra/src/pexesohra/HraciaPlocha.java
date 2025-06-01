package pexesohra;

import javax.swing.*;
import java.awt.*;
import java.awt.event.*;
import java.util.ArrayList;
import java.util.Collections;

public class HraciaPlocha extends JFrame {

    private JPanel panel; // Panel, kde sa zobrazujú kartičky
    private JLabel skoreLabel; // Zobrazenie počtu nájdených párov
    private JButton resetButton; // Tlačidlo na resetovanie hry

    private Karticky prva, druha; // Prvá a druhá vybraná kartička
    private Timer timer; // Timer na oneskorené skrytie kartičiek
    private int najdene = 0; // Počet nájdených párov
    private int R, C; // Počet riadkov a stĺpcov hracej plochy

    public HraciaPlocha() {
        nastavVelkost(); // Nastavenie veľkosti hracej plochy

        setTitle("Pexeso " + R + "x" + C);
        setDefaultCloseOperation(EXIT_ON_CLOSE);
        setSize(600, 600);
        setLayout(new BorderLayout());

        // Horný panel s výsledkom a tlačidlom na reset
        JPanel topPanel = new JPanel(new BorderLayout());
        skoreLabel = new JLabel("Nájdené páry: 0");
        topPanel.add(skoreLabel, BorderLayout.WEST);

        resetButton = new JButton("Hrať znova");
        resetButton.addActionListener(e -> resetujHru()); // Pridanie akcie na resetovanie hry
        topPanel.add(resetButton, BorderLayout.EAST);

        add(topPanel, BorderLayout.NORTH);

        // Panel s kartičkami
        panel = new JPanel();
        panel.setLayout(new GridLayout(R, C));
        add(panel, BorderLayout.CENTER);

        vytvorPexeso(); // Vytvorenie kartičiek

        setVisible(true);
    }
    // Nastavenie veľkosti hracej plochy podľa výberu používateľa
    private void nastavVelkost() {
        String[] moznosti = {"2x2", "4x4"};
        String vyber = (String) JOptionPane.showInputDialog(
                null,
                "Zvoľ veľkosť pexesa:",
                "Výber veľkosti",
                JOptionPane.QUESTION_MESSAGE,
                null,
                moznosti,
                moznosti[0]);

        if (vyber == null) System.exit(0); // Ak používateľ zruší výber, ukončí sa program

        if (vyber.equals("2x2")) {
            R = 2;
            C = 2;
        } else {
            R = 4;
            C = 4;
        }
    }
    // Vytvorenie a zamiešanie kartičiek
    private void vytvorPexeso() {
        java.util.List<ImageIcon> ikony = new ArrayList<>();
        int pocetParov = (R * C) / 2;

        // Načítanie ikon a vytvorenie párov
        for (int i = 1; i <= pocetParov; i++) {
            ImageIcon ikona = new ImageIcon("zdroje/icons/img" + i + ".png");
            ikony.add(ikona);
            ikony.add(ikona);
        }
        Collections.shuffle(ikony); // Zamiešanie ikon
        // Vytvorenie kartičiek a pridanie do panelu
        for (ImageIcon ikona : ikony) {
            Karticky karta = new Karticky(ikona);
            karta.addActionListener(e -> klikKarta(karta)); // Pridanie akcie na kliknutie
            panel.add(karta);
        }
    }
    // Spracovanie kliknutia na kartičku
    private void klikKarta(Karticky karta) {
        // Ignorovanie kliknutí na už nájdené páry alebo rovnakú kartičku
        if (karta.jePary() || karta == prva || karta == druha) return;
        karta.zobrazIkonu(); // Zobrazenie obrázku na kartičke
        if (prva == null) {
            prva = karta; // Nastavenie prvej vybranej kartičky
        } else if (druha == null) {
            druha = karta; // Nastavenie druhej vybranej kartičky

            if (prva.maRovnakeIkony(druha)) {
                // Ak sa obrázky zhodujú, označíme ich ako nájdený pár
                prva.nastavPary(true);
                druha.nastavPary(true);
                najdene++;
                skoreLabel.setText("Nájdené páry: " + najdene);
                prva = null;
                druha = null;
            } else {
                // Ak sa obrázky nezhodujú, skryjeme ich po 1 sekunde
                timer = new Timer(1000, e -> {
                    prva.skryIkonu();
                    druha.skryIkonu();
                    prva = null;
                    druha = null;
                });
                timer.setRepeats(false); // Timer sa spustí len raz
                timer.start();
            }
        }
    }
    // Resetovanie hry a výber novej veľkosti
    private void resetujHru() {
        najdene = 0;
        prva = null;
        druha = null;
        if (timer != null) {
            timer.stop(); // Zastavenie bežiaceho timeru
        }
        skoreLabel.setText("Nájdené páry: 0");
        panel.removeAll(); // Odstránenie všetkých kartičiek z panelu
        panel.setLayout(new GridLayout(R, C));
        vytvorPexeso(); // Vytvorenie nových kartičiek
        panel.revalidate(); // Aktualizácia rozloženia panelu
        panel.repaint(); // Prekreslenie panelu
    }
}
