/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Main.java to edit this template
 */
package cviko3;

import static java.lang.Math.sqrt;
import java.util.Scanner;

/**
 *
 * @author student
 */
public class Cviko3 {

    /**
     * @param args the command line arguments
     */
    public static void main(String[] args) {
        // TODO code application logic here
        
        /*
        Scanner sc = new Scanner(System.in,"Windows-1250");
        System.out.println("zadaj cislo:");
        float a=sc.nextFloat();
        if (a>5)
        
        System.out.println("Dakujem");
        */
        
        /*
        Scanner sc = new Scanner(System.in,"Windows-1250");
        float a=sc.nextFloat(); 
        if (a>=0)
             System.out.println("je to v poriadku");
        else if (a<0)
        System.out.println("hodnota je zaporna" + abs);
    */
  /*
        Scanner sc = new Scanner(System.in,"Windows-1250");
        System.out.println("zadaj èíslo a:");
        float a = sc.nextFloat(); 
        System.out.println("zadaj èíslo b:");
        float b = sc.nextFloat(); 
         System.out.println("zadaj èíslo c:");
        float c = sc.nextFloat(); 
        
        float d=b*b-4*a*c;
        System.out.println("diskriminant: D=" +d);
        
        if (d>0){
        System.out.println("x1="+(-b+Math.sqrt(d))/(2*a));
        System.out.println("x2="+(-b-Math.sqrt(d))/(2*a));
                }        
        if (d==0){
        System.out.println("dvojnasobny koreò x="+(-b)/(2*a));
        
                }        
        if (d<0){
            d=-d;
        System.out.println("x1=" + (-b)/(2*a)+ "+" +(Math.sqrt(d))/(2*a)+"i");
        System.out.println("x2=" + (-b)/(2*a)+ "-" +(Math.sqrt(d))/(2*a)+"i");
                }        
        */
  /*
        Scanner sc = new Scanner(System.in,"Windows-1250");
        
        byte pocetBiele=0;
        byte pocetCervene=0;
        
        System.out.println ("dobry den, vitajte v krcme x.");
        System.out.println ("Date si vino?.");
        System.out.println ("odpovedajte ano alebo nie.");
        String objednavka = sc.nextLine();
        
        
        if ("ano".equals(objednavka))skok:{
            System.out.println("Biele?");
            String biele = sc.nextLine();
                if()
        
        
        System.out.println("date si aj cervene?");
        String b = sc.nextLine();
            if(b = n){
                System.out.println("zaplat ucet");
            }
            
        }
  */
            Scanner sc = new Scanner(System.in,"Windows-1250");
        System.out.println("zadajte postupne dve cisla.");
        int a = sc.nextInt();
        int b = sc.nextInt();
        int c = (a>b)?a:b;
        System.out.println("Vacsie z cisel: " + a + " a " + b + " je cislo: " + c);
              
        

    
}
}