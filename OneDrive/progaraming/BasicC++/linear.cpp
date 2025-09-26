#include<bits/stdc++.h>
using namespace std;

int main(){
    int n;
    cin >> n;

    int arr[n];
    for(int i = 0; i<n; i++){
        cin >> arr[i];
    }

    int q; cin >> q;
    while (q--)
    {  
        int x; cin >> x;
        int fl = 0;
           for(int i = 0; i<n; i++){
              if(arr[i] == x){
                 fl = 1;
               }
           }
           if(fl == 1){
              cout << "YES" << endl;
           }
           else{
              cout << "NO" << endl;
           }
    }
    

}