#include<iostream>
using namespace std;

  int main(){
    int n;
    cout << "Enter the size of array: "<< endl;
    cin >> n;
    int arr[n];
    cout << "Enter the element Of arrya:" << endl;
      for(int a = 0; a < n; a++){
        cin >> arr[a];
      }
      
      //int p = arr[0];
      int p = INT64_MAX;
        for(int b = 0; b < n; b++){
            if(p > arr[b]){
                p = arr[b];
            }
        }
        cout << "the smallest element of array is:";
        cout << p << endl;

        int q = arr[0];
        for(int b = 0; b < n; b++){
            if(q < arr[b]){
                q = arr[b];
            }
        }
        cout << "the largest element of the array is:";
        cout<< q;


  }