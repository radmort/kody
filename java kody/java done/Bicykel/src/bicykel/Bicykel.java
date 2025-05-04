/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Main.java to edit this template
 */
package bicykel;

/**
 *
 * @author student
 */
public class Bicykel {

    /**
     * @param args the command line arguments
     */
    public static void main(String[] args) {
        Bicyke fav = new Bicyke("favorit", 200, true);
       
        System.out.println("bežné bicykle");
        System.out.println(fav.toString());
        System.out.println("cena po vylepšení");
    
        
        System.out.println("\nHorské bicykle:");
        HorskyBicykel aut = new HorskyBicykel ("Author", 1999, 3,  9);
        System.out.println(aut.toString());
        System.out.println("Cena po vylepšení");
        
}
}