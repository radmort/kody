/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Main.java to edit this template
 */
package vozidlo;

import java.util.Locale;
import java.util.Scanner;

/**
 *
 * @author student
 */
public class Vozidlo {

    
  
    /**
     * @param args the command line arguments
     */
    public static void main(String[] args) {
       Scanner sc = new Scanner(System.in);
       sc.useLocale(Locale.US);
       
       Vozidlo1 automobil = new Vozidlo1();
       
        System.out.println("Zadaj pocet kolies. ");
        int kolesa = sc.nextInt();
        automobil.setPocetKolies(kolesa);
        System.out.println("Zadaj rok vyroby. ");
        int rVyroby = sc.nextInt();
        automobil.setRokVyroby(rVyroby);
        
        
        System.out.println("Pocet kolies: "+ automobil.getPocetKolies());
        System.out.println("Rok vyroby: "+ automobil.getRokVyroby());
        
    }
    
}
