#include<bits/stdc++.h>
using namespace std;

int power(int a, int b){
    long int ans = 1;
    //if(b == 1) return 1;
    for(int i=1; ans<=b; i++){
        ans *= a;
    }
    if(b == ans) return 1;
    else return 0;
}
int main(){
    int a,b;
    cin >> a >> b;
    cout << (power(a,b)? "Yes": "No");
    return 0;
}