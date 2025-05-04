/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package bicykel;

/**
 *
 * @author student
 */
public class HorskyBicykel extends Bicyke{
    private int prevodyVpredu;
    private int prevodyVzadu;

    public HorskyBicykel(String vyrobca, int cena, int prevodyVpredu, int prevodyVzadu) {
        super (vyrobca, cena, true);
        this.prevodyVpredu = prevodyVpredu;
        this.prevodyVzadu = prevodyVzadu;
    }
    
    public int getPocetRychlosti() {return prevodyVpredu * prevodyVzadu;}
    
    public void setPrevodyvzadu (int pocet) {prevodyVzadu = pocet;}
    
    public String toString ()
            
    {
        String kolo = super.toString();
        String pom = kolo.substring(0, kolo.indexOf(",prehazovacka:"));
        
        pom=pom.concat(", poèet rýchlosti (vrátane kríženia retaze ;-");
        pom = pom.concat(String.valueOf(this.getPocetRychlosti()));
        return pom;
    }
    
}
