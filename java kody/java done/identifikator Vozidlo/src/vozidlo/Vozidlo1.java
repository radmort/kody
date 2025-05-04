/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package vozidlo;

/**
 *
 * @author student
 */
public class Vozidlo1 {
    private int PocetKolies;
    private int RokVyroby;
    private double najazdeneKM;

    public int getPocetKolies() {
        return PocetKolies;
    }

    public int getRokVyroby() {
        return RokVyroby;
    }

    public double getNajazdeneKM() {
        return najazdeneKM;
    }

    public void setPocetKolies(int p) {
        if(p>=1 && p<=20)
        this.PocetKolies = p;
    }

    public void setRokVyroby(int RokVyroby) {
        this.RokVyroby = RokVyroby;
    }

    public void setNajazdeneKM(double najazdeneKM) {
        this.najazdeneKM = najazdeneKM;
    }
}
