/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package cviko20_3_25_java;

import java.util.Arrays;
import java.util.Scanner;

/**
 *
 * @author student
 */
public class polia {
    
    static void vypisPola(int[] pole)
    {
        for(int i =0; i<pole.length;i++){
            System.out.print(pole[i]+" ");
        }
        System.out.println();
    }
    
    static void zapisPrvkov(int [] pole){
        Scanner sc = new Scanner(System.in);
        System.out.println("Zapiste pod seba "+ pole.length + " hodnot");
        for(int i = 0; i<pole.length;i++){
            pole[i]= sc.nextInt();
        }
    }
    
    static void zoradeniePola(int[] pole)
    {
        System.out.println("pred zoradenim: "+ Arrays.toString(pole));
        Arrays.sort(pole);
        System.out.println("po zoradeni: "+ Arrays.toString(pole));
    }
}
