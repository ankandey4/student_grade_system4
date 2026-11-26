#include <bits/stdc++.h>
using namespace std;

int main() {
    int n, k;
    cin >> n >> k;

    vector<int> problems(n);
    for (int i = 0; i < n; i++) {
        cin >> problems[i];
    }

    int left = 0;
    while (left < n && problems[left] <= k) {
        left++;
    }

    int right = n - 1;
    while (right > left && problems[right] <= k) {
        right--;
    }

    cout << left + (n - 1 - right) << endl;

    return 0;
}