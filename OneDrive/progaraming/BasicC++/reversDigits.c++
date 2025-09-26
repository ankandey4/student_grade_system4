#include <bits/stdc++.h>

using namespace std;

int reverseDigits(int n) {
    int revNum = 0;
    while (n > 0) {
        int a = n % 10;
        revNum = revNum * 10 + a;
        n = n / 10;
    }
    return revNum;
}

int main() {
    int n;
    cin >> n;
    cout << reverseDigits(n);
    return 0;
}