/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package banka;

/**
 *
 * @author student
 */
class BankovyUcet {
    
    private int hotovost;

     BankovyUcet(int vklad) {
        hotovost = vklad;
    }
     
     BankovyUcet(){
         hotovost = 0;
     }

    public int getHotovost() {
        return hotovost;
    }
    
    public void vloz(int vklad){
        hotovost += vklad;
    }
    
    public int vyber(int suma){
        if(hotovost >= suma){
            hotovost -= suma;
            return suma;
        } else {
            int zostatok = hotovost;
            hotovost = 0;
            return zostatok;
        }
    }
    
    public void vypisUctu(){
        System.out.println("Hotovost: "+ hotovost);
    }
    
}
