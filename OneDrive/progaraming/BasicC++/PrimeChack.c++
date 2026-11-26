#include<bits/stdc++.h>

using namespace std;

// bool prime(int n){
//     if(n <= 1) return false;
//     for(int i = 2; i < n; i++){
//         if(n % i == 0) return false;

//     }
//     return true;
//}

int prime(int n){
    int flag = 1;
    if(n <= 1) return 0;
    int sq = sqrt(n);
    for(int i = 2; i*i < n; i++){
        if(n % i == 0) return 0;

    }
    return 1;
}


int main(){
    int n;
    cin >> n;
    //prime(n) ? cout << "Prime" : cout << "Not prime";
    cout << (prime(n)? "Prime": "Not prime");
    return 0;
}