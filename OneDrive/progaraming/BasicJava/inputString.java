package BasicJava;
import java.util.Scanner;

public class inputString {
    public static void main(String[] args){
        Scanner str = new Scanner(System.in);
        System.out.print("Enter your name: ");
        String name = str.nextLine();
        System.out.println("Hello " +name +". What the fuck are you doing here?");
        str.close();
    }

}
