/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Main.java to edit this template
 */
package cviko5.mv;

/**
 *
 * @author student
 */


public class metody {
    
    static double mocnina(double x, int n){
    if (n==1){
    return x;
    }
        return 0;
    
}
    
    public static void main(String[] args) {
          Scanner sc = new Scanner(System.in);
       sc.useLocale(Locale.US);
       
       while (true) {
        System.out.print("zadajte zaklad mocniny (0-koniec): ");
       double x = sc.nextDouble();
       if (x==0.0)
           break;
       
       System.out.print("zadajte ,mocninu: ");
       int n = sc.nextInt();
       System.out.println(x+ "na" + n + " = " + mocnina (x,n));
      }
       }
}
