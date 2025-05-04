/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Main.java to edit this template
 */
package pkg10;

/**
 *
 * @author student
 */
public class Main {

    /**
     * @param args the command line arguments
     */
    public static void main(String[] args) {
        Bicykel fav = new Bicykel ("favorit", 200 , true);
        System.out.println(fav.toString());
        
        Horskybicykel aut = new Horskybicykel ("Author", 1999, 3 ,9);
        System.out.println(aut.toString());
    }
    
}
