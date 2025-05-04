/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Main.java to edit this template
 */
package javaappl4;

import java.util.Scanner;

/**
 *
 * @author student
 */
public class JavaAppl4 {
    
    /**
     * @param args the command line arguments
     */
    public static void main(String[] args) {
      /* Scanner sc = new Scanner(System.in,"Windows-1250");
        System.out.println("chcete generovat cisla? ano,nie ");
       String povedanie= sc.nextLine();
       
       double c=0;
               
       if("ano".equals(povedanie)){
       for(;;){
           
           c =( Math.random())*100;
           System.out.println(c+" ");
           System.out.println("chcete pokracovat generovat cisla? ano,nie ");
           String podmienkapov = sc.nextLine();
           if("nie".equals(podmienkapov)) break;
        }
       } */
      
     /* Scanner sc = new Scanner(System.in,"Windows-1250");
        System.out.println("Program vypocita skalarny sucin dvoch bodov v rovine");
        System.out.println("zadajte x-ovu suradnicu vektora v");
        float vx=sc.nextFloat();
        System.out.println("zadajte y-ovu suradnicu vektora v");
        float vy=sc.nextFloat();
        System.out.println("zadajte x-ovu suradnicu vektora u");
        float ux=sc.nextFloat();
        System.out.println("zadajte y-ovu suradnicu vektora u");
        float uy=sc.nextFloat();
        System.out.println("skalarny sucin vektrov \b v a \b u je: "+ Metody.SkalarnySucin(vx,vy,ux,uy));*/
       /* System.out.println("priemer zadanych cisiel: "+metodan.priem()); */
       Scanner sc = new Scanner(System.in,"Windows-1250");
        System.out.println("zadaj realne cislo a cele cislo: ");
        double x = sc.nextDouble();
        double n = sc.nextDouble();
        metodan.mocnin(x,n);
    } 
}
