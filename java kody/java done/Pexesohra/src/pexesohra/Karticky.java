package pexesohra;

import javax.swing.*;

public class Karticky extends JButton {
    private final ImageIcon icon;
    private boolean matched = false;

    public Karticky(ImageIcon icon) {
        this.icon = icon;
        setIcon(new ImageIcon("zdroje/icons/back.png")); // obrázok zadnej strany
    }

    public void zobrazIkonu() {
        setIcon(icon);
    }

    public void skryIkonu() {
        setIcon(new ImageIcon("zdroje/icons/back.png"));
    }

    public boolean jePary() {
        return matched;
    }

    public void nastavPary(boolean matched) {
        this.matched = matched;
    }

    public boolean maRovnakeIkony(Karticky druha) {
        return this.icon.toString().equals(druha.icon.toString());
    }
}
