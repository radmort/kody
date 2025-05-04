/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Main.java to edit this template
 */
package javacvikoprog2;

import java.util.Scanner;

/**
 *
 * @author student
 */
public class Javacvikoprog2 {

    /**
     * @param args the command line arguments
     */
    public static void main(String[] args) {
       /* System.out.println(Byte.MAX_VALUE);
        System.out.println(Byte.MIN_VALUE);
        System.out.println(Integer.MAX_VALUE);
        System.out.println(Integer.MIN_VALUE);
        System.out.println(Float.MAX_VALUE);
        System.out.println(Float.MIN_VALUE + "\n"); */
        
       /* double fg = Double.MAX_VALUE *2;
        System.out.println(Double.isNaN(5./0));
        System.out.print('\133'+"Program kon"+'\u010D'+'\u00ED'+" !"+'\135'+'\n');
        
        String s = "   Krokon osoh roch";
        s = s.trim();
        s = s.toUpperCase();
        System.out.println(s.startsWith("krok"));
        System.out.println(s.endsWith("hroch"));
        System.out.println(s.contains("nos"));
        System.out.println(s);
        
        String j = "jozo je pekny";
        System.out.println(j);
        j = j.replace("pekny","skaredy");
         System.out.println(j);   
         
         
       Scanner sc= new Scanner(System.in,"Windows-1250");
        System.out.println("zadaj prve cislo: ");
        int k = sc.nextInt();
        System.out.println("druhe cislo: ");
        int p = sc.nextInt();
        int l = k+p;
        String fo=String.format("Ked scitame %d a %d dostaneme %d .",k,p,l);
        System.out.println(fo);
        System.out.printf("Ked scitame %d a %d dostaneme %d .",k,p,l);*/
        
       Scanner sc= new Scanner(System.in,"Windows-1250");
       System.out.println("zadaj nazov tovaru,vyrobcu,pocet kusov, datum vyroby dobu trvanlivosti");
       String naz = sc.nextLine();
       String vyr = sc.nextLine();
       int kus = sc.nextInt();
       sc.nextLine();
       String datv = sc.nextLine();
       int trvan = sc.nextInt();
       String cele = String.format("nazov tovaru: %s, vyrobca: %s, pocet kusov: %d ,datum vyrob: %s, trvanlivost: %d.",naz,vyr,kus,datv,trvan);
        System.out.println(cele);
    }
    
}
