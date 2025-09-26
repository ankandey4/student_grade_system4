#include<iostream>
using namespace std;

int main(){
    int a[5];
    for(int i = 0; i<5; i++){
        cin >> a[i];
    }
    int max = a[0];
    int smax = a[0];

    /*for(int j=0; j<5; j++){
        if(a[j] > max){
            max = a[j];
        }
    }

    for(int k=0; k<5; k++){
        if(a[k] > smax && max > a[k]){
            smax = a[k];
        }
    }*/
   for(int j = 0; j<5; j++){
       if(a[j]> max){
          smax = max;
          max = a[j];
       }
   }

    cout << smax;
}
