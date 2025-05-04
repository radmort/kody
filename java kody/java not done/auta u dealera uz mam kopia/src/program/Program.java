/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Main.java to edit this template
 */
package program;

import java.util.Scanner;

/**
 *
 * @author student
 */
public class Program {

    /**
     * @param args the command line arguments
     */
    public static void main(String[] args) {
        Scanner sc= new Scanner(System.in,"Windows-1250");
        
        Vozidlo automobil = new Vozidlo();
        
        System.out.println("zadaj pocet kolies");
        int kolesa = sc.nextInt();
        automobil.setPocetKolies(kolesa);
        
        System.out.println("zadaj rok vyroby");
        int rVyroby = sc.nextInt();
        automobil.setRokVyroby(rVyroby);
        
        System.out.println("pocet kolies" + automobil.getPocetKolies());
        System.out.println("rok vyroby " + automobil.getRokVyroby());
        
    }
    
}
