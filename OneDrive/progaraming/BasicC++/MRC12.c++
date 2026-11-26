#include <bits/stdc++.h>

using namespace std;

int main() {
    int t;
    cin >> t;
    while (t--) {
        int x1,y1,x2,y2; 
        int x3,y3,x4,y4; 
        cin >> x1 >> y1 >> x2 >> y2;
        cin >> x3 >> y3 >> x4 >> y4;

        int XLL = max(x1, x3);
        int YLL = max(y1, y3);
        int XUR = min(x2, x4);
        int YUR = min(y2, y4);

        if (XLL < XUR && YLL < YUR) {
            cout << XLL << " " << YLL << " " << XUR << " " << YUR << "\n";
        } else {
            cout << "No Overlap\n";
        }

        if (t) cout << "\n"; 
    }
    return 0;
}
