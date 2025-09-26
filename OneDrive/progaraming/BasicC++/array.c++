#include<iostream>
using namespace std;

 int main(){
    int n;
    cout << "Enter the size of array: "<< endl;
    cin >> n;
    int arr[n];
    cout << "Enter the element of array: " << endl;
      for (int i = 0; i<n; i++){
          cin >> arr[i];
      }
    cout << "the element of array are:" << endl;
      for(int p = 0; p < n; p++){
          cout << arr[p] << endl;
      } 
    cout <<"the size of array is:" << sizeof(arr) << endl;
    return 0; 
 }