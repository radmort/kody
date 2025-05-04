/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package pkg10;

/**
 *
 * @author student
 */
public class Bicykel {
    private String vyrobca;
    private int cena;
    private boolean maPrehadzovacku;
    
    Bicykel(String vyrobca, int cena, boolean maPrehadzovacku){
    this.vyrobca = vyrobca;
    this.cena = cena;
    this.maPrehadzovacku = maPrehadzovacku;
    }

    public String getVyrobca() {
        return vyrobca;
    }

    public int getCena() {
        return cena;
    }

    public boolean isMaPrehadzovacku() {
        return maPrehadzovacku;
    }
    
    public void setCena(int novaCena) { cena = novaCena;}
    public void setPrehadzovacka(boolean prehadzovacka) {maPrehadzovacku = prehadzovacka;}
    
    @Override
    public String toString(){
        return ("vyrobca/znacka" + vyrobca + " cena: " + cena + " prehadzovakca" + maPrehadzovacku);
    }
    
}
