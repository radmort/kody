/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package cviko6;

import java.util.Scanner;

/**
 *
 * @author student
 */
public class Metody   {
    
   static void binar (int n){ 
    int []cifry = new int[32];
    for (int i = 0; i<cifry.length; i++){
    cifry[i] = 0;
    }
    
    int j=0;
    while (n !=0){
    cifry [j] = (n%2);
    j++;
    n/=2;
    }
    
    for (int i = cifry.length-1; i>=0 ; i--){
    System.out.print(cifry[i]);
    }
    System.out.println("");
        
   
   }
   
   static void zapisHodnot(int[][] pole)
   {
for (int i=0;i<pole.length;i++){
  for (int j=0; j<pole.length;j++){
      pole[i][j] = (int)Math.round(10+Math.random()*89);
  }      
    }
        
    }
   
   
   static void vypisHodnot(int[][] pole)
   {
for (int i=0;i<pole.length;i++)
{
  for (int j=0; j<pole.length;j++)
  {
      System.out.print(pole[i][j]+"");
  }      
  System.out.println();
    }
       
    }
   
   
 
   
   
   
  
}


    
    
  