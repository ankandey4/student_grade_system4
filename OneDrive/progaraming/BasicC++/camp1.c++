#include <iostream>
using namespace std;

int main() {
    int a,b;
    int p = a;
    int q = b;
    cin >> a >> b;
    for(int i=0; i<2; i++){
        if(a==1) cout << "one";
        else if(a==2) cout << "two" << endl;
        else if(a==3) cout << "three" << endl;
        else if(a==4) cout << "four" << endl;
        else if(a==5) cout << "five" << endl;
        else if(a==6) cout << "six" << endl;
        else if(a==7) cout << "seven" << endl;
        else if(a==8) cout << "eight" << endl;
        else if (a>=9) cout << "nine" << endl;
        cout << (i % 2 ==0 ? "even" : "odd");
        cout << endl; 
        a = b;
    }
    

    return 0;
}