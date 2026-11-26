package conditional;

import java.util.Scanner;

public class TriangleOrNot {
    public static void main(String[] arge){
       Scanner input = new Scanner(System.in);
       System.out.print("Enter three line \n");
       int a = input.nextInt();
       int b = input.nextInt();
       int c = input.nextInt();

       if(a+b>c && b+c>a && c+a>b){
          System.out.println("This is tringle");
       }
       else{
        System.out.println("This is not tringle");
       }
       input.close();
         
    }
}
