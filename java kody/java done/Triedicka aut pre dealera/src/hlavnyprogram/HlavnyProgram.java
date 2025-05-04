/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Main.java to edit this template
 */
package hlavnyprogram;

import java.util.Locale;
import java.util.Scanner;

/**
 *
 * @author student
 */
public class HlavnyProgram {

    /**
     * @param args the command line arguments
     */
    public static void main(String[] args) {
       Scanner conIN = new Scanner(System.in);
       conIN.useLocale(Locale.US);
       
       Vozidlo automobil = new Vozidlo();
       
       System.out.println("zadaj pocet kolies:");
       int kolesa = conIN.nextInt();
       automobil.setPocetKolies(kolesa);
       System.out.println("Zadaj rok výroby");
        int rVyroby = conIN.nextInt();
               automobil.setRokVyroby(rVyroby);

         System.out.println("Zadaj najazdene km");
        double najazdene = conIN.nextDouble();
               automobil.setNajazdeneKM((int)najazdene);
           
        automobil.Vypis();
 
    }
    
}
