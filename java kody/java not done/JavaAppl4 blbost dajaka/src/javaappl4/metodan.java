/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package javaappl4;

import java.util.Scanner;

/**
 *
 * @author student
 */
public class metodan {
    static double priem(){
        Scanner sc = new Scanner(System.in,"Windows-1250");
        System.out.println("zadaj pocet cisel: ");
         int n=sc.nextInt();
         double priem1 = 0;
         for(int i= 0;i<n;i++){
        System.out.println("zadavaj cisla: ");
        double cisl = sc.nextDouble();
        priem1= priem1 + cisl;
         }
    return priem1/n;
    }
    
    static void mocnin(double x, double n ){
        for( double i=1.0; i<=n;i++){
        double vys = Math.pow(x, i);
        System.out.println("mocnina "+i+".: "+vys);
        }
    }
            
}
