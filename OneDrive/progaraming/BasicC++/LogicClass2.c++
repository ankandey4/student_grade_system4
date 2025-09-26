#include <bits/stdc++.h>
using namespace std;

int main() {
    int N;
    while (cin >> N && N != 0) {
        int totalSquares = 0;
        for (int i = 1; i <= N; i++) {
            totalSquares += i * i;
        }
        cout << totalSquares << endl;
    }
    return 0;
}