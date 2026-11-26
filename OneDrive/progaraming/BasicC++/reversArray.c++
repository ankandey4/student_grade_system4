#include<iostream>
#include<array> // only for
#include<algorithm>
using namespace std;

void revers1(int Arr[], int n){
     int st = 0;;
     int en = n - 1;
       while(st < en){
             int temp = Arr[st];
             Arr[st] = Arr[en];
             Arr[en] = temp;
               st++;
               en--;
       }
}

void revers2(int Arr[], int n){
   int st = 0;
   int en = n-1;
     for(int i = 0; i < n / 2; i++){
              swap(Arr[st], Arr[en]);
              st++;
              en--;
     }
}

void revers3(int Arr[], int n){
  int newarr[n];
  int j = 0;
  int k = n-1;
   for(int i = 0; i<n; i++){           
        newarr[i] = Arr[k];
         k--;
     }
   for(int i = 0; i<n; i++){
        Arr[i] = newarr[i];
   }
}


int main(){
    int n ;
    cout << "Enter the size of array:";
    cin >> n;
    int arr[n];
    cout << "Enter the element of array:" << endl;
       for(int i = 0; i < n; i++){
          cin >> arr[i];
       }
    cout << "Reversed array is: ";

    //revers1(arr, n);
    //revers2(arr, n);
    revers3(arr, n);

       for(int i = 0; i < n; i++){
          cout << arr[i] << " ";
       }
}