#include<bits/stdc++.h>
using namespace std;

void prac_map(){
    map<int, int> m;
    int n; cin >> n;
    for(int i = 0; i < n; i++){
        int x; cin >> x;
        m[x]++;
    }
    for(auto it : m){
        cout << it.first << " " << it.second << endl;
    }
}

void prac_set(){
    set<int> s={40,20,10,20,1};
    //int n; cin >> n;
    // for(int i=0; i<n;i++){
    //     int x; cin >> x;
    //     s.insert(x);
    // }

    for(auto it : s){
        cout << it << " " << endl;
    }
    auto a = s.upper_bound(15);
    auto b = s.lower_bound(15);
    
    cout << *a << endl << *b << endl;
    if(s.find(20)!= s.end()) cout << "found" << endl;
}

void prac_vector(){
    vector<int> v;
    int n; cin >> n;
    for(int i=0; i<n;i++){
        int x; cin >> x;
        v.push_back(x);

    }
    // for(int i=0; i<n;i++){
    //     cout << v[i] << " " << endl;
    // }
    // for(auto it = v.begin(); it != v.end(); it++){
    //     cout << *it << " " << endl;
    // }
    sort(v.begin(), v.end());
    for(auto it : v){
        cout << it << " " << endl;
    }
    // v.insert(v.begin()+2, 100);
    // for(auto it : v){
    //     cout << it << " " << endl;
    // }
    int a = upper_bound(v.begin(), v.end(), 4)-v.begin();
    int  b = lower_bound(v.begin(), v.end(),4)-v.begin();
    
    cout << a << endl << b << endl;
    


}

int main(){
    //prac_vector();
    //prac_set();
    prac_map();
    
}