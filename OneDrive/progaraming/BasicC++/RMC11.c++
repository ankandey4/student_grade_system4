#include <bits/stdc++.h>
using namespace std;

int main() {
    double x1,y1,x2,y2,x3,y3,x4,y4;
    while (cin >> x1 >> y1 >> x2 >> y2 >> x3 >> y3 >> x4 >> y4) {
        double sx,sy,ux,uy,vx,vy;

        if (x1==x3 && y1==y3) { sx=x1; sy=y1; ux=x2; uy=y2; vx=x4; vy=y4; }
        else if (x1==x4 && y1==y4) { sx=x1; sy=y1; ux=x2; uy=y2; vx=x3; vy=y3; }
        else if (x2==x3 && y2==y3) { sx=x2; sy=y2; ux=x1; uy=y1; vx=x4; vy=y4; }
        else { sx=x2; sy=y2; ux=x1; uy=y1; vx=x3; vy=y3; }

        double wx = ux + vx - sx;
        double wy = uy + vy - sy;

        cout << fixed << setprecision(3) << wx << " " << wy << "\n";
    }
}
