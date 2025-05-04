/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package banka;

/**
 *
 * @author student
 */
public class Klient {
    private BankovyUcet ucet;
    private int osobneCislo;
    
    Klient(int vklad, int cislo){
        ucet = new BankovyUcet(vklad);
        osobneCislo = cislo;
    }

    public int getOsobneCislo() {
        return osobneCislo;
    }
    
    public int getHotovost(){
        return ucet.getHotovost();
    }
    
    public int vloz(int vklad){
        ucet.vloz(vklad);
        return vklad;
    }
    
    public int vyber(int suma){
        return ucet.vyber(suma);
    }
    
    public void vypisUctu(){
        System.out.println("Klient: "+ osobneCislo );
        ucet.vypisUctu();
    }
}
