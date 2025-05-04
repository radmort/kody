/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package cviko62;

/**
 *
 * @author student
 */
public class valec {
    
   
    public double polomer;
    public double vyska;
    
  public double objem ()
          
    {
    double objem=Math.PI*Math.pow(polomer, 2)*vyska;
    return objem;
    }
    
    
  public double povrch () 
    
    {
       double povrch=Math.PI*Math.pow(polomer, 2)*2+0*Math.PI*polomer*vyska;
       return povrch;
    }
    
   
    
}
