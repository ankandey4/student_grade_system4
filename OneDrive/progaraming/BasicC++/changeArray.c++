#include<iostream>
using namespace std;

void chgangeArry(int Arr[], int n ){
    for(int i = 0; i < n; i++){
        Arr[i] = 2 * Arr[i];
    }
    return;
}

int main(){
    int n;
    cout << "enter the the array size:";
    cin >> n;
    int arr[n];
    cout << "Enter the elements of array:" << endl;

       for(int i = 0; i < n; i++){
        cin >> arr[i];
       }
    chgangeArry(arr, n);
    cout << "the changed array is:";

        for(int i = 0; i < n; i++){
            cout << arr[i] << " ";
        }
}
