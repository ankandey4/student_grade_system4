#include <bits/stdc++.h>
using namespace std;

int main() {
    string n;
    cin >> n;

    int count = 0;

    
    if (n.size() == 1) {
        cout << 0 << endl;
        return 0;
    }
    long long sum = 0;
    for (char c : n)
        sum += c - '0';

    count = 1; 
    while (sum >= 10) {
        long long temp = 0;
        while (sum > 0) {
            temp += sum % 10;
            sum /= 10;
        }
        sum = temp;
        count++;
    }

    cout << count << endl;
    return 0;
}    