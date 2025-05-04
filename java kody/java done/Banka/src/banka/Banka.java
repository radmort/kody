/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Main.java to edit this template
 */
package banka;

/**
 *
 * @author student
 */
public class Banka {

    /**
     * @param args the command line arguments
     */
    public static void main(String[] args) {
      Klient [] ucty = new Klient[10];
      for(int i=0;i<ucty.length;i++){
          ucty[i]= new Klient((int)(Math.random()*1000), i);
      }
      
    for(int i=0;i<ucty.length;i++){
          ucty[i].vypisUctu();
          ucty[i].vloz(2000);
          ucty[i].vypisUctu();
          ucty[i].vyber(1000);
          ucty[i].vypisUctu();
          ucty[i].vyber(500);
          ucty[i].vypisUctu();
          System.out.println();
      }
  
   
      int hotovostVBanke = 0;
      for(int i = 0;i<ucty.length;i++){
          hotovostVBanke += ucty[i].getHotovost();
      }
        System.out.println("Celkova hotovost: "+hotovostVBanke);
    }
    
}
