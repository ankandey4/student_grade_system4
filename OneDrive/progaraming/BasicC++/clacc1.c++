#include<bits/stdc++.h>
using namespace std;

int nThTerm(int a, int b, int n){
    int d = b -a;
    int nThTerm = a + (n-1)*d;
    return nThTerm;
}

int main(){
   int a1,b1;
   a1=2;
   b1 = 3;
   int n = 4;
   cout << nThTerm(a1,b1,n);
    
}