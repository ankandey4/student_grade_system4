#include<bits/stdc++.h>
using namespace std;

int main(){
    int n;
    cin >> n;

      while(n != 0){
        int a, b;
        cin >> a >> b;
        int m = min(a,b);
        int M = max(a,b);
        int B = max(M, 2*m);
        int area = B*B;
        cout << area << endl;
        n--;
      }
}