#include<bits/stdc++.h>
using namespace std;

int reverseDigits(int n){
    // int rev  = 0;
    // while (n > 0){
    //     rev = rev*10 + (n%10);
    //     n = n/10;
    // }
    string s = to_string(n);
    reverse(s.begin(),s.end());
    n = stoi(s);
    return n;
    
}

int main (){
    int n;
    cin >> n;

    cout << reverseDigits(n);
    return 0;
}