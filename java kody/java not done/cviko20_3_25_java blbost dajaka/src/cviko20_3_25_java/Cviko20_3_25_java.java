/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Main.java to edit this template
 */
package cviko20_3_25_java;

import java.util.Scanner;

/**
 *
 * @author student
 */
public class Cviko20_3_25_java {

    /**
     * @param args the command line arguments
     */
    public static void main(String[] args) {
       
        Scanner sc = new Scanner(System.in,"Windows-1250");
        final int MAX = 36;
      long begin,end,f;
      
      for (long n = 1; n <= MAX; n +=2){
          begin = System.currentTimeMillis();
          
         f = rekurzia.fib(n);
         end = System.currentTimeMillis();
          System.out.println("fib(" +n+ ")= " + f + " v case: " + (end-begin));
      } 
      
        System.out.println("Rozhodni o nacitani cele cisla = 1 alebo realne cisla = 2 ? ");
        int n = sc.nextInt();
        if(n==1){
            System.out.println("Zadaj 3 cele cisla pod seba: ");
            int a = sc.nextInt();
            int b = sc.nextInt();
            int c = sc.nextInt();
            System.out.println("Obsah trojuholnika je: "+ rekurzia.obsahtroju(a, b, c));
        }
        if(n==2){
            System.out.println("Zadaj 3 realne cisla pod seba: ");
            double a = sc.nextDouble();
            double b = sc.nextDouble();
            double c = sc.nextDouble();
            System.out.println("Obsah trojuholnika je: "+ rekurzia.obsahtroju(a, b, c));
        } 
      
    }
    
      
}

