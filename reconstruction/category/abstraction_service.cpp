#include <algorithm>
#include <errno.h>
#include <fcntl.h>
#include <fstream>
#include <map>
#include <signal.h>
#include <stddef.h>
#include <stdio.h>
#include <stdlib.h>
#include <string>
#include <sys/socket.h>
#include <sys/un.h>
#include <time.h>
#include <vector>
#include <unistd.h>
//#include "MixSegment.hpp"
#include "KeywordExtractor.hpp"
//#include "PosTagger.hpp"
using namespace CppJieba;
const char * const dict_path       = "./dict/jieba.dict.utf8";
const char * const model_path      = "./dict/hmm_model.utf8";
const char * const idf_path        = "./dict/idf.utf8";
const char * const stop_words_path = "./dict/stop_words.utf8";
const char * const user_dict_path  = "./dict/user.dict.utf8";

#define BUFLEN  128
#define PAYLOADLEN 32
#define KEYWORDNUM 10

using namespace std;

char socket_path[128] = "./unixsocket.socket";

MixSegment       segment(dict_path, model_path);
KeywordExtractor extractor(dict_path, model_path, idf_path, stop_words_path);

string _itoa(int num);
char   odd_check_byte(string data);
void   split_contents(vector<string> &sentences, string stopword);
double computeWeight(string sentence, const map<string, double> &word2weight);

vector<vector<string> > get_packets(const int * const p_clfd, string last_buf, string target_type, int nr_packets);
string handle_packets(const vector<string> *p_data);
int    read_packets(const int * const p_clfd, map<string, vector<string> > *p_packets);
int    send_packets(const int * const p_clfd, string data);

string         pack_syn(int num_packets);
vector<string> pack_data(string data, int payload_mx_len);
string         pack_fin();

int main(void) {
    int fd, size;
    struct sockaddr_un un;
    un.sun_family = AF_UNIX;
    strcpy(un.sun_path, socket_path);
    if ((fd = socket(AF_UNIX, SOCK_STREAM, 0)) < 0) {
        printf("socket failed\n");
        return -1;
    }

    //设置套接字为阻塞模式
    int flags = fcntl(fd, F_GETFL, 0);
    if (fcntl(fd, F_SETFL, flags & ~O_NONBLOCK) < 0) {
        printf("set fd block flag failed\n");
        return -1;
    }

    //绑定地址
    size = offsetof(struct sockaddr_un, sun_path) + strlen(un.sun_path);
    unlink(socket_path);
    if (bind(fd, (struct sockaddr *)&un, size) < 0) {
        printf("bind failed\n");
        return -1;
    }

    printf("UNIX domain socket bound\n");

    //监听
    if (listen(fd, 5) < 0) {
        printf("listen failed\n");
        close(fd);
        return -1;
    }

    printf("Waiting for clients\n");

    //如果服务端在send时客户端关闭了，那么服务端会因为send的一个feature而挂掉
    //要将send发出的信号设置为忽略状态，否则默认的信号处理函数会crash这个程序
    //参见http://www.gnu.org/savannah-checkouts/gnu/libc/manual/html_node/Sending-Data.html
    signal(SIGPIPE, SIG_IGN);

    //循环处理
    int clientn = 0;
    while(true) {
        int clfd;

        if ((clfd = accept(fd, NULL, NULL)) < 0) {
            printf("accept error\n");
            return -2;
        }

        clientn++;
        printf("client %d comes.\n", clientn);

        //int n = 0;
        //char buf[BUFLEN];
        //while ((n = read(clfd, buf, BUFLEN-1)) > 0) {
            //buf[n] = '\0';
            //printf("%s", buf);
            //fflush(stdout);
            //send(clfd, buf, n, 0);
        //}
        //printf("\n");
        //exit(-1);

        int  errno;

        map<string, vector<string> > packets;

        errno = read_packets(&clfd, &packets);
        if (errno != 0) {
            printf("read packets error, closing clfd\n");
            close(clfd);
            continue;
        }
#ifdef DEBUG
        for (map<string, vector<string> >::iterator p = packets.begin(); p != packets.end(); p++) {
            for (int i = 0, len = (p->second).size(); i < len; ++i) {
                printf("read %s packet:%s\n", (p->first).c_str(), (p->second)[i].c_str());
            }
        }
#endif

        string res = handle_packets(&packets["data"]);

        errno = send_packets(&clfd, res);
        if (errno != 0) {
            printf("read packets error\n");
            return -1;
        }
        //sleep(10);

        close(clfd);
    }

    return 0;
}

string _itoa(int num) {
    char buf[BUFLEN];
    sprintf(buf, "%d", num);
    return string(buf);
}

char odd_check_byte(string data) {
    static const int bits[8] = {1, 2, 4, 8, 16, 32, 64, 128};

    int cnt = 0;
    for (int i = 0, len = data.size(); i < len; ++i) {
        for (int j = 0; j < 8; ++j) {
            if (bits[j] & data[i]) {
                cnt += 1;
            }
        }
    }
    
    char oc_byte;

    if (cnt % 2) {
        oc_byte = '0';
    } else {
        oc_byte = '1';
    }

#ifdef DEBUG
    printf("oc byte for packet [%s] is [%c], cnt is [%d]\n", data.c_str(), oc_byte, cnt);
#endif

    return oc_byte;
}

vector<vector<string> > get_packets(const int * const p_clfd, string last_buf, string target_type, int nr_packets) {
    vector<vector<string> > packets;
    packets.push_back(vector<string>());
    packets.push_back(vector<string>());

    int status       = 0;
    int payload_len  = 0;
    int payload_read = 0;
    string type      = "";
    string seperator = "$";
    string payload   = "";

    int n = 1;

    while (n > 0 && nr_packets > 0) {
        string buf  = last_buf;
        int buf_len = buf.size();

        int st = 0;
        int ed = 0;
        do {
            if (0 == status) {
                int st_old = st;

                //read for type
                ed = st + 4;
                if (ed > buf_len) {
                    if (st_old >= buf.size()) {
                        last_buf = "";
                    } else {
                        last_buf = buf.substr(st_old);
                    }
                    break;
                }
                type = buf.substr(st, ed - st);
                if (target_type != type) {
                    printf("read %s type error, get [%s] instead\n", target_type.c_str(), type.c_str());
                    return vector<vector<string> >();
                }

                //read for payload_len
                st = ed;
                for (; ed < buf_len && seperator[0] != buf[ed]; ++ed) {}
                if (ed >= buf_len || seperator[0] != buf[ed]) {
                    if (st_old >= buf.size()) {
                        last_buf = "";
                    } else {
                        last_buf = buf.substr(st_old);
                    }
                    break;
                }
                payload_len = atoi(buf.substr(st, ed - st).c_str());

                payload_read = 0;
                payload      = "";
                status       = 1;
                st           = ed + 1;
            }

            //read payload
            ed = st + payload_len - payload_read;
            if (ed - st < 0) {
                printf("compute data ed error\n");
                return vector<vector<string> >();
            }

            if (ed > buf_len) {
                ed = buf_len;
            }

            payload      += buf.substr(st, ed - st);
            payload_read += ed - st;

            if (payload_read < payload_len) {
                last_buf = "";
                break;
            }

            //read odd check byte
            st = ed;
            ed = st + 1;
            if (ed > buf_len) {
                last_buf = "";
                break;
            }

            char oc_byte = (buf.substr(st, ed - st))[0];
            string packet = type + string(_itoa(payload_len)) + seperator + payload;
            if (oc_byte != odd_check_byte(packet)) {
                printf("odd check byte not match\n");
#ifdef DEBUG
                printf("packet:%s\n", packet.c_str());
                printf("DEBUG:%c vs %c\n", oc_byte, odd_check_byte(packet));
#endif
                return vector<vector<string> >();
            }

            packets[1].push_back(payload);
            st     = ed;
            status = 0;
            if (0 >= --nr_packets) {
                last_buf = buf.substr(st);
                break;
            }
        } while (0 == status && nr_packets > 0);

        if (nr_packets > 0) {
            char tmp_buf[BUFLEN];
            n = read(*p_clfd, tmp_buf, BUFLEN - 1);
            if (n < 0) {
                printf("recv error\n");
            }
            if (n == 0) {
                break;
            }
            
            tmp_buf[n] = '\0';
            last_buf = last_buf + string(tmp_buf);
        }
    }

    if (nr_packets > 0) {
        printf("receive data packet error, some are missed\n");
        return vector<vector<string> >();
    }

    packets[0].push_back(last_buf);
    return packets;
}

int read_packets(const int * const p_clfd, map<string, vector<string> > *p_packets) {
    (*p_packets)["syn"]  = vector<string>();
    (*p_packets)["data"] = vector<string>();
    (*p_packets)["fin"]  = vector<string>();

    vector<vector<string> > res; //res[0] means last_buf
                                 //res[1] means data

    //get SYN packets
    res = get_packets(p_clfd, string(""), string("0000"), 1);
    if (res.size() < 2 || res[0].empty() || res[1].empty()) {
        printf("read for SYN packets error\n");
        return -1;
    }
    (*p_packets)["syn"] = res[1];
    int nr_packets = atoi((*p_packets)["syn"][0].c_str());

    //get DATA packets
    res = get_packets(p_clfd, res[0][0], string("0002"), nr_packets);
    if (res.size() < 2 || res[0].empty() || res[1].empty()) {
        printf("read for DATA packets error\n");
        return -1;
    }
    (*p_packets)["data"] = res[1];

    //get FIN packets
    res = get_packets(p_clfd, res[0][0], string("0001"), 1);
    if (res.size() < 2 || res[0].empty() || res[1].empty()) {
        printf("read for FIN packets error\n");
        return -1;
    }
    (*p_packets)["fin"] = res[1];
    
    return 0;
}

string handle_packets(const vector<string> *p_data) {
    string client_data = "";
    for (int i = 0, len = (*p_data).size(); i < len; ++i) {
        client_data += (*p_data)[i];
    }

    const int num_stopwords = 7;
    string stopwords[num_stopwords] = {".", "。", "!", "！", "?", "？", "\n"};
    vector<string> sentences;
    sentences.push_back(client_data);
    for (int i = 0; i < num_stopwords; ++i) {
        split_contents(sentences, stopwords[i]);
    }
    //TODO

    //extractor, wordweights

    map<string, double> word2weight;
    vector<pair<string, double> > wordweights;
    extractor.extract(client_data, wordweights, KEYWORDNUM);
    for (unsigned int i = 0; i < wordweights.size(); ++i) {
        word2weight[wordweights[i].first] = wordweights[i].second;
    }

    vector<pair<int, double> > indexAndweight;
    for (int j = 0, len = sentences.size(); j < len; ++j) {
        //TODO computeWeight
        pari<int, double> index_weight = make_pair(j, computeWeight(sentences[i], word2weight));
        indexAndweight.push_back(index_weight);
    }

    sort(indexAndweight.begin(), indexAndweight.end(), sort_weight);
    int req_num = indexAndweight.size() * 0.15;
    if (req_num == 0) {
        req_num = indexAndweight.empty() ? 0 : 1;
    }
    sort(indexAndweight.begin(), indexAndweight.begin() + req_num, sort_index);

    string res = "";
    for (int i = 0; i < req_num; ++i) {
        res += sentences[indexAndweight[i].first] + "|";
    }

    return res;
}

string pack_syn(int num_packets) {
    string payload   = string(_itoa(num_packets));
    string type      = "0000";
    int payload_len  = payload.size();
    string seperator = "$";

    string packet = type + string(_itoa(payload_len)) + seperator + payload;

    char oc_byte = odd_check_byte(packet);
    packet.append(1, oc_byte);

    return packet;
}

vector<string> pack_data(string data, int payload_mx_len) {
    vector<string> packets;

    for (int i = 0, len = data.size(); i < len; i += payload_mx_len) {
        string payload   = data.substr(i, payload_mx_len);
        string type      = "0002";
        int payload_len  = payload.size();
        string seperator = "$";

        string packet = type + string(_itoa(payload_len)) + seperator + payload;

        char oc_byte = odd_check_byte(packet);
        packet.append(1, oc_byte);

        packets.push_back(packet);
    }

    return packets;
}

string pack_fin() {
    string payload   = "";
    string type      = "0001";
    int payload_len  = payload.size();
    string seperator = "$";

    string packet = type + string(_itoa(payload_len)) + seperator + payload;

    char oc_byte  = odd_check_byte(packet);
    packet.append(1, oc_byte);

    return packet;
}

int send_packets(const int * const p_clfd, string data) {
    vector<string> data_packets = pack_data(data, PAYLOADLEN);
    string           syn_packet = pack_syn(data_packets.size());
    string           fin_packet = pack_fin();

#ifdef DEBUG
    printf("send syn packet:%s\n", syn_packet.c_str());
    for (int i = 0, len = data_packets.size(); i < len; ++i) {
        printf("send data packet:%s\n", data_packets[i].c_str());
    }
    printf("send fin packet:%s\n", fin_packet.c_str());
#endif
    send(*p_clfd, syn_packet.c_str(), syn_packet.size(), 0);
    for (int i = 0, len = data_packets.size(); i < len; ++i) {
        send(*p_clfd, data_packets[i].c_str(), data_packets[i].size(), 0);
    }
    send(*p_clfd, fin_packet.c_str(), fin_packet.size(), 0);
    return 0;
}

void split_contents(vector<string> &sentences, string stopword) {
    vector<string> res;
    int stopword_len = stopword.size();
    for (int i = 0, len = sentences.size(); i < len; ++i) {
        int j = 0;
        int sentence_len = sentences[i].size();
        while (j != npos && j < sentence_len) {
            int pos = sentences[i].find(stopword, j);
            if (pos != npos) {
                res.push_back(sentences[i].substr(j, pos - j));
                pos += stopword_len;
            }
            j = pos;
        }
    }
    sentences = res;
}

double computeWeight(string sentence, const map<string, double> &word2weight) {
    vector<string> words;
    segment.cut(sentence, words);
//TODO

}
