/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package hlavnyprogram;


/**
 *
 * @author student
 */
public class Vozidlo {
    private int pocetKolies;
    private int rokVyroby;
    private int najazdeneKM;

    public int getPocetKolies() {
        return pocetKolies;
    }

    public int getRokVyroby() {
        return rokVyroby;
    }

    public int getNajazdeneKM() {
        return najazdeneKM;
    }

    public void setPocetKolies(int pocetKolies) {
        
        if(pocetKolies>=1 && pocetKolies<=20)
            
        {
        this.pocetKolies = pocetKolies;
        }
    }

    public void setRokVyroby(int rokVyroby) {
     if(rokVyroby>=1950 && rokVyroby<=2012)
        
        this.rokVyroby = rokVyroby;
    }

    public void setNajazdeneKM(int najazdeneKM) {
        
        if(najazdeneKM<=50000 || najazdeneKM >=150000)
        {        this.najazdeneKM = najazdeneKM;
        }
        
    }
    
    public int zaradmi(){
     if(najazdeneKM == 0 || rokVyroby == 0 || pocetKolies == 0 )
     {
         return 0;
     } 
     else 
     {
         return 1;
     }
    }
    
            
    public void Vypis(){
        System.out.println("Vozidlo parametre:");
        if(zaradmi()==1 ){System.out.println("Vozidlo zaradene do predaja");}
        else { System.out.println("Vozidlo nezaradene do predaja"); }
        System.out.println("Pocet km: "+ najazdeneKM);
        System.out.println("Rok vyroby: "+ rokVyroby);
        System.out.println("Pocet kolies: "+ pocetKolies);
    }
 
    
    
}
