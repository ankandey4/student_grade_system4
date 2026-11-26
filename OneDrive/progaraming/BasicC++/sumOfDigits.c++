#include<bits/stdc++.h>
using namespace std;

int sumOfDigits(int n) {
    int sum = 0;
    while (n != 0) {
        int a = n % 10;
        sum += a;
        n /= 10;
    }
    return sum;
}

int main() {
    int n;
    cin >> n;
    cout << sumOfDigits(n);
    return 0;
}