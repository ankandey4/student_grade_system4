package BasicJava;

//class To Class constructor/parameter pass
class car {
    String brand;
    int speed;
    
    void drive(){
        System.out.println(brand + " is driving at " + speed + " km/h");
    }
    void model(String m){
        System.out.println("the car model " + m);
    }
}

public class classToClassParamater {
    public static void main(String[] args){

        car myCar = new car(); // create a object which name mycar
        myCar.brand = "Toyota";
        myCar.speed = 120;
        myCar.drive();
        myCar.model("Corolla");
    }
}
