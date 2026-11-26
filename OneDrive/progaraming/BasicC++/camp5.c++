#include<iostream>
#include<string>

using namespace std;
int main(){
    string a;
    cin>>a;
    int ln = sizeof(a)/sizeof(a[0]);
    int count = 0;
    for(int i=0; i<ln; i++){
        if(a[i] == 'a' || a[i] == 'e' || a[i] == 'i' || a[i]== 'o'|| a[i]=='u'){
            count++;
        }
    }
    cout << count;
}