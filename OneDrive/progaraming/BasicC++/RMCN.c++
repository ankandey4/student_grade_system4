#include <bits/stdc++.h>
using namespace std;

int main() {

    int T;
    cin >> T;
    while (T--) {
        long long N;
        cin >> N;
        long long total = 0;
        long long div = 5;
        while (N / div > 0) {
            total += N / div;
            div *= 5;
        }
        cout << total << "\n";
    }
    return 0;
}
