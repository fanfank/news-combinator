#include <fstream>
#include <time.h>
int last_mtime;
int cstr2int(char *s, int len) {
    if (0 >= len) {
        return 0;
    }

    int offset = 0;
    for (; offset < len && ' ' == s[offset]; ++offset);

    int res    = 0;
    bool minus = false;
    for (int i = 0; i + offset < len; ++i) {
        int index = i + offset;
        if ('-' == s[index]) {
            if (minus || i != 0) {
                res = 0;
                break;
            } else {
                minus = true;
            }
        } else if ('+' == s[index]) {
            if (i != 0) {
                res = 0;
                break;
            }
        } else if (s[index] > '9' || s[index] < '0'){
            break;
        }

        //no overflow check
        res = res * 10 + s[i] - '0';
    }

    if (minus) {
        res *= -1;
    }
    return res;
}
bool initLastMtime() {
    std::filebuf fb;
    if (fb.open("lastmtime", std::ios::in)) {
        std::istream is(&fb);
        char buffer[11];
        is.read(buffer, 10);
        fb.close();
        last_mtime = cstr2int(buffer, 10);
    }
    return true;
}
bool setLastMtime() {
    time_t ts;
    time(&ts);
    char cstr_ts[20];
    sprintf(cstr_ts, "%ld", ts);

    std::filebuf fb;
    fb.open("lastmtime", std::ios::out);
    std::ostream os(&fb);
    os<<cstr_ts;
    fb.close();
    return true;
}
int main() {
    //setLastMtime();
    initLastMtime();
    fprintf(stdout, "%d\n", last_mtime);
}
