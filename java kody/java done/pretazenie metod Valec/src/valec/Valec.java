/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Main.java to edit this template
 */
package valec;

/**
 *
 * @author student
 */
public class Valec {
        public double polomer;
        public double vyska;
        
         public double objem(){
            double objem=Math.PI*Math.pow(polomer, 2)*vyska;
            return objem;
        }
         
         public Valec (double polomer, double vyska){
             this.polomer=polomer;
             this.vyska=vyska;
         }
         
          public Valec (double polomer){
             this.polomer=polomer;
             this.vyska=polomer;
         }
    /**
     * @param args the command line arguments
     */
    public static void main(String[] args) {
        
        Valec val1=new Valec(2,3);
        Valec val2=new Valec(1);
        System.out.println("objem valca je " + val1.objem());
        System.out.println("objem valca je " + val2.objem());
        
 
    }


    
}
