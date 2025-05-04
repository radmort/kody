/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package cviko5;

import java.util.Locale;
import java.util.Scanner;
import java.util.Arrays;

/**
 *
 * @author student
 */
public class metody {
    
    static double mocnina(double x, int n){
    if (n==1){
    return x;
    }
    else { return x*mocnina(x,n-1);
    
    
    }
    
}

public static long fib(long n)
{
if (n == 1 || n == 2 || n == 3){
return 1;
}
else {
return fib(n-1) + fib(n-2) +fib(n-3);
}}


static double objem(int a,int b , int c)
        
{
double z = (a+b+c)/2;
double S=z*(z-a)*(z-b)*(z-c);
return Math.sqrt(S);
}

static double objem1(double d,double e , double f)
        
{
    
double z = (d+e+f)/2;
double S=z*(z-d)*(z-e)*(z-f);
return Math.sqrt(S);
}
    
    

static void vypisPola (int[] pole)
{
for (int i=0;i<pole.length; i++)
    {
        System.out.print(pole[i]+" ");
    }

System.out.println();
    
}

static void zapisPrvkov(int[] pole)
{
    Scanner cin=new Scanner(System.in)  ;
for (int i=0; i<pole.length;i++)
{
    pole[i]=cin.nextInt();
}

}

static void zoradeniePola(int[] pole)


{
System.out.println("pred zoradenim"+Arrays.toString(pole));
Arrays.sort(pole);
System.out.println("po zoradeni"+Arrays.toString(pole));
}











}