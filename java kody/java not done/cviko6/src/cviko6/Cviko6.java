/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Main.java to edit this template
 */
package cviko6;

import java.util.Scanner;

/**
 *
 * @author student
 */
public class Cviko6 {
    
    public double polomer;
    public double vyska;
    
    public double objem()
    {
    double objem=Math.PI*Math.pow(polomer, 2)*vyska;
    return objem;
    }
    
    public double povrch()
            
    {
    double povrch=Math.PI*Math.pow(polomer, 2)*2+2*Math.PI*polomer*vyska;
    return povrch; 
    
    }
    public Cviko6 (double polomer, double vyska)
    {
    this.polomer=polomer;
    this.vyska=vyska;
    
    }
    
     public Cviko6 (double polomer)
    {
    this.polomer=polomer;
    this.vyska=polomer;
    
    }
            

    public static void main(String[] args) {
        Cviko6 val1=new Cviko6 (2,3);
       // val1.polomer=2;
        //val1.vyska=3;
        
        Cviko6 val2=new Cviko6(1,10);
        //val2.polomer=1;
        //val2.vyska=10;
        
       Cviko6 val3=new Cviko6(1);

        System.out.println(val1.objem());
        System.out.println(val2.objem());
        System.out.println(val1.povrch());
        System.out.println(val2.povrch());
        System.out.println(val3.objem());
        System.out.println(val3.povrch());


      
      
}

}
        
        

        
        
    
    

