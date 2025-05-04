/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package cviko20_3_25_java;

/**
 *
 * @author student
 */
public class rekurzia {
    
    static double umocnenie (double x, int n)
    {
    if (n==1) return x;
    else return x*umocnenie(x,n-1);
    }


static long fib(long n){
if (n==1. || n==2. || n==3.){
return 1;
} 
else {
return fib(n - 1) + fib(n - 2) + fib(n - 3);
}
}

static double obsahtroju(int a, int b, int c){
    
    double z = (a+b+c)/2.0;
    double S = Math.sqrt((z*(z-a)*(z-b)*(z-c)));
    return S;
}

static double obsahtroju(double a, double b, double c){
    
    double z = (a+b+c)/2.0;
    double S = Math.sqrt((z*(z-a)*(z-b)*(z-c)));
    return S;
}


}