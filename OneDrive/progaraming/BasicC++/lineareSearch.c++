#include<iostream>
using namespace std;

void searchArr(int Arr[], int n, int m){
    int flag = 0;
    for(int i = 0; i < n; i ++){
        if (Arr[i] == m){
            cout << "Element found at index: "<< i << endl;
            flag = 1;
        }
    }
    if(flag == 0){
        cout << " Element not found in array"  << endl;
    }
}

int main(){
    int m;
    cout << "Enter the search element:";
    cin >> m;
    int n;
    cout << "Enter the size of array:";
    cin >> n;
    int arr[n];
    cout << "enter thr element of array:"<< endl;

      for(int i = 0; i<n; i++){
        cin >> arr[i];
      }
    
    searchArr(arr,n,m);
}