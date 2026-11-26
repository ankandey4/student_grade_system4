#include<bits/stdc++.h>
using namespace std;    

int binary1(){
    int n;
    cin >> n;

    int arr[n];
    for(int i = 0; i<n; i++){
        cin >> arr[i];
    }

    sort(arr, arr+n);

    int q; cin >> q;
    while (q--)
    {  
        int x; cin >> x;
        auto fl = binary_search(arr, arr+n, x);
           if(fl){
              cout << "YES" << endl;
           }
           else{
              cout << "NO" << endl;
           }
    }
    

}

int binary2(){
    int n;
    cin >> n;
    int arr[n];
    for(int i = 0; i<n; i++){
        cin >> arr[i];
    }
    sort(arr, arr+n);
    int q; cin >> q;
    while (q--)
    {  
        int x; cin >> x;
        int low = 0, hi = n-1;
        int fl = 0;
        while(low <= hi){
            int mid = (low + hi) / 2;
            if(arr[mid]==x){
                fl = 1;
                break;
            }
            else if(arr[mid]<x){
                low = mid+1;
            }
            else{
                hi = mid - 1;
            }
        }
        if(fl == 1){
            cout << "yes" << endl;
        }
        else cout << "no" << endl;
    }
}

int binary3(){
    // int n;
    // cin >> n;
    int arr[] = {2,3,4,10,40};
    // for(int i = 0; i<n; i++){
    //     cin >> arr[i];
    // }
    //sort(arr, arr+n);
    int q; cin >> q;
    while (q--)
    {  
        int x; cin >> x;
        int low = 0, hi = 4;
        int fl = 0;
        int ind = -1;
        while(low <= hi){
            int mid = (low + hi) / 2;
            if(arr[mid]==x){
                fl = 1;
                ind = mid;
                break;
            }
            else if(arr[mid]<x){
                low = mid+1;
            }
            else{
                hi = mid - 1;
            }
        }
        if(fl == 1){
            cout << "yes the index is " << ind << endl;
        }
        else cout << "no " << ind<< endl;
    }
}

int binary4(){
    // int n;
    // cin >> n;
    //int arr[] = {2,3,4,10,40};
    // for(int i = 0; i<n; i++){
    //     cin >> arr[i];
    // }
    //sort(arr, arr+n);
    int q; cin >> q;
    while (q--)
    {  
        int x; cin >> x;
        int low = 0, hi = 4;
        int fl = 0;
        int ind = -1;
        while(low <= hi){
            int mid = (low + hi) / 2;
            if(mid*mid==x){
                fl = 1;
                ind = mid;
                break;
            }
            else if(mid*mid<x){
                low = mid+1;
            }
            else{
                hi = mid - 1;
            }
        }
        if(fl == 1){
            cout << "yes the index is " << ind << endl;
        }
        else cout << "no " << ind<< endl;
    }
}


int binary5(){
    // int n;
    // cin >> n;
    // int arr[n];
    // for(int i = 0; i<n; i++){
    //     cin >> arr[i];
    // }
    //sort(arr, arr+n);
    int q; cin >> q;
    while (q--)
    {  
        double x; cin >> x;
        double low = 0, hi = x;
        int fl = 0;
        double ind;
        while(abs(hi-low) > 0.0000001){
            double mid = (low + hi) / 2.0;
            if(mid*mid*mid < x){
                low = mid;
            }
            else{
                hi = mid;
            }
        }
        cout << "yes " << low << endl;
        // if(fl == 1){
        //     cout << "yes " << ind << endl;
        // }
        // else cout << "no" << endl;
    }
}

int main(){
    binary4();
    return 0;
}