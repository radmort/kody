/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package bicykel;

/**
 *
 * @author student
 */
public class Bicyke {
    private String vyrobca;
    private int cena; 
    

    public Bicyke(String vyrobca, int cena, boolean maPrehadzovacku) {
        this.vyrobca = vyrobca;
        this.cena = cena;
        this.maPrehadzovacku = maPrehadzovacku;
    }
    
    public void setVyrobca(String vyrobca) {
        this.vyrobca = vyrobca;
    }

    public void setCena(int cena) {
        this.cena = cena;
    }

    public String getVyrobca() {
        return vyrobca;
    }

    public int getCena() {
        return cena;
    }
    
    public boolean isMaPrehadzovacku()
    {
    return maPrehadzovacku;
    }
    private boolean maPrehadzovacku;

   
    public String toString() {
        return "Bicyke{" + "vyrobca=" + vyrobca + ", cena=" + cena + ", maPrehadzovacku=" + maPrehadzovacku + '}';
    }
         
    
}
