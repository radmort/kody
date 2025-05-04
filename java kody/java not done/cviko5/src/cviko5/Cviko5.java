/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Main.java to edit this template
 */
package cviko5;

import static cviko5.metody.mocnina;
import java.util.Locale;
import java.util.Scanner;

/**
 *
 * @author student
 */
public class Cviko5 {

    
     
     
    public static void main(String[] args) {
        // TODO code application logic here
      
       
      
       Scanner sc = new Scanner(System.in);
       sc.useLocale(Locale.US);
       /**
       while (true) {
        System.out.print("zadajte zaklad mocniny (0-koniec): ");
       double x = sc.nextDouble();
       if (x==0.0)
           break;
       
       System.out.print("zadajte ,mocninu: ");
       int n = sc.nextInt();
       System.out.println(x+ "na" + n + " = " + mocnina (x,n));
      }
    
        System.out.println("Ak budete zadavat realne cisla stlaè 1 inak 2");
        int m=sc.nextInt();
        if (m==1)
            
        {
        int a=sc.nextInt();
        int b=sc.nextInt();
        int c=sc.nextInt();
        
            System.out.println(metody.objem(a,b,c));
        }
         if (m==2)
            
        
        {
        double d=sc.nextDouble();
        double e=sc.nextDouble();
        double f=sc.nextDouble();
        
                   System.out.println(metody.objem1(d,e,f));
        }
        
   */
   
   
       
       
       
       /*
       Scanner sc = new Scanner(System.in, "Windows-1250");
       sc.useLocale(Locale.US);
       System.out.print("Vypocitam obsah trojuholnika. ");
       
        while (true) {
        System.out.print("zadajte prvu stranu trojuholnika: ");
       double a = sc.nextDouble();
       if (a==0.0)
           break;
        }
        
       while (true) {
        System.out.print("zadajte druhu stranu trojuholnika: ");
       double b = sc.nextDouble();
       if (b==0.0)
           break;
        }
       while (true) {
        System.out.print("zadajte prvu stranu trojuholnika: ");
       double c = sc.nextDouble();
       if (c==0.0)
           break;
        }
      
      System.out.println("obsah trojuholnika je " +metody.obsah(a, b, c));
      break;
       
       */
       
       
       
       /*
       Scanner sc = new Scanner(System.in, "Windows-1250");
       System.out.print("cele (1) alebo realne cisla (2)  ");
       switch (sc.nextInt()){
           case 1:
               System.out.print("zadajte postupne velkosti stran. ");
               int a =sc.ne
       
       
       */
       

       
       
       
       
       Scanner conIN;
       conIN = new Scanner(System.in,"Windows-1250");
       System.out.println("zadajte pocet prvkov pola.");
       int n = conIN.nextInt();
       
       int [] poleCisel;
       poleCisel = new int[n];
       
       System.out.println("Pocet prvkov v poli: " + poleCisel.length);
       
      
       
       
       metody.vypisPola(poleCisel);
       metody.zapisPrvkov(poleCisel);
       metody.zoradeniePola(poleCisel);
    
       
    }
}
