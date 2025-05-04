/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Main.java to edit this template
 */
package javacviko3_4_25;

/**
 *
 * @author student
 */
public class Javacviko3_4_25 {
    
    public int stranaA, stranaB, stranaC;
    
    public double obvod(){
        return stranaA + stranaB + stranaC;
    }
    
    public boolean pravyUhol (int a, int b, int c){
        if(a*a==((b*b) + (c*c)))
            return true;
        else
            return false;
    }
    
    public boolean jePravouhly(){
        if(pravyUhol(stranaA,stranaB,stranaC) == true)
        return true;
        if(pravyUhol(stranaB,stranaA,stranaC) == true)
        return true;
        if(pravyUhol(stranaC,stranaB,stranaA) == true)
        return true;
        else
        return false;
    }
    
    public boolean dobreStrany(int a, int b, int c){
        if(a<(b+c)){
            return true;
        }else return false;
    }
    
    Javacviko3_4_25(int stranaA, int stranaB, int stranaC){
       if(dobreStrany(stranaA,stranaB,stranaC) && dobreStrany(stranaB,stranaA,stranaC)&& dobreStrany(stranaC,stranaB,stranaA)) {
        this.stranaA = stranaA;
        this.stranaB = stranaB;
        this.stranaC = stranaC;}
       else{
                this.stranaA=0;
                this.stranaB=0;
                this.stranaC=0;}
       
       
    }
    
    public static void main(String[] args) {
        Javacviko3_4_25 t= new Javacviko3_4_25(6,8,10);
        System.out.println("Obvod je:" + t.obvod());
        if(t.jePravouhly()==true)
            System.out.println("Obvod je pravouhly ");
    }
    
}
