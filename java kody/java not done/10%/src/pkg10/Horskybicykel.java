/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package pkg10;

/**
 *
 * @author student
 */
public class Horskybicykel extends Bicykel{
    private int prevodyVpredu;
    private int prevodyVzadu;
    
    Horskybicykel(String vyrobca, int cena , int prevodyVpredu, int prevodyVaadu){
        super(vyrobca, cena, true);
        this.prevodyVpredu = prevodyVpredu;
        this.prevodyVzadu = prevodyVzadu;
    }

    public int getPocetRychlosti() {
        return prevodyVpredu* prevodyVzadu;
    }

    public int getPrevodyVzadu(int pocet) {
        return prevodyVzadu = pocet;
    }
    
    @Override
    public String toString() {
        String kolo = super.toString();
        String pom = kolo.substring(0, kolo.indexOf(", prehadzovacka: "));
        
        pom = pom.concat(", pocet rychlosti vratane krizneia retaze: ");
        pom = pom.concat(String.valueOf(this.getPocetRychlosti()));
        return pom;
    }
    
}

