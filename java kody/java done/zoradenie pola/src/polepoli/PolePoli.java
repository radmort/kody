/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Main.java to edit this template
 */
package polepoli;

import java.util.Scanner;

/**
 *
 * @author student
 */
public class PolePoli {

    /**
     * @param args the command line arguments
     */
    public static void main(String[] args) {
      Scanner sc = new Scanner(System.in,"Windows-1250");
      
       System.out.println("zadaj pocet prvkov pola: ");
        int g = sc.nextInt();
        int [] polecisel = new int[g];
        System.out.println("Pocet prvkov v poli: " + polecisel.length);
        polia.vypisPola(polecisel);
        polia.zapisPrvkov(polecisel);
        polia.vypisPola(polecisel);
        polia.zoradeniePola(polecisel);
    }
    
}
