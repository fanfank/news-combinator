#include <algorithm>
#include <errno.h>
#include <fcntl.h>
#include <fstream>
#include <map>
#include <set>
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
#include "util/daemonize.hpp"
#include "util/protocol.hpp"
using namespace CppJieba;
const char * const dict_path       = "./dict/jieba.dict.utf8";
const char * const model_path      = "./dict/hmm_model.utf8";
const char * const idf_path        = "./dict/idf.utf8";
const char * const stop_words_path = "./dict/stop_words.utf8";
const char * const user_dict_path  = "./dict/user.dict.utf8";

#define KEYWORDNUM 10
//#define DEBUG

using namespace std;

char socket_path[128] = "./unixsocket.socket";

MixSegment       segment(dict_path, model_path);
KeywordExtractor extractor(dict_path, model_path, idf_path, stop_words_path);

string _itoa(int num);
//template<typename first, typename second>
bool   sort_weight(const pair<int, double> &a, const pair<int, double> &b);
bool   sort_index(const pair<int, double> &a, const pair<int, double> &b);
void   split_contents(vector<string> &sentences, string stopword);
double computeWeight(string sentence, const map<string, double> &word2weight);

string handle_packets(const vector<string> *p_data);

int main(int argc, char **argv) {
#ifndef DEBUG
    //make this a daemon process
    printf("Daemonizing...\n");
    daemonize("abstraction_service", "./");
#endif

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

#ifdef DEBUG
        printf("send contents:%s\n", res.c_str());
#endif

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

string handle_packets(const vector<string> *p_data) {
    string client_data = "";
    for (int i = 0, len = (*p_data).size(); i < len; ++i) {
        client_data += (*p_data)[i];
    }
#ifdef DEBUG
    printf("client_data:%s\n", client_data.c_str());
#endif

    //split content into sentences
    const size_t num_stopwords = 7;
    string stopwords[num_stopwords] = {".", "。", "!", "！", "?", "？", "\n"};
    vector<string> sentences;
    sentences.push_back(client_data);
    for (size_t i = 0; i < num_stopwords; ++i) {
        split_contents(sentences, stopwords[i]);
    }

#ifdef DEBUG
    printf("*** sentences ***\n");
    for (size_t i = 0; i < sentences.size(); ++i) {
        printf("%s\n", sentences[i].c_str());
    }
    printf("*** sentences ***\n");
#endif

    map<string, double> word2weight;
    vector<pair<string, double> > wordNweight;
    extractor.extract(client_data, wordNweight, KEYWORDNUM);
    for (size_t i = 0; i < wordNweight.size(); ++i) {
        word2weight[wordNweight[i].first] = wordNweight[i].second;
    }

    vector<pair<int, double> > indexNweight;
    for (int i = 0, len = sentences.size(); i < len; ++i) {
        pair<int, double> index_weight = make_pair(i, computeWeight(sentences[i], word2weight));
        indexNweight.push_back(index_weight);
    }

    sort(indexNweight.begin(), indexNweight.end(), sort_weight);
    size_t req_num = indexNweight.size() * 0.15;
    if (0 == req_num) {
        req_num = indexNweight.size();
    }
    sort(indexNweight.begin(), indexNweight.begin() + req_num, sort_index);

    string res = "";
    for (size_t i = 0; i < req_num; ++i) {
        res += sentences[indexNweight[i].first] + "|";
    }

    return res;
}

void split_contents(vector<string> &sentences, string stopword) {
    vector<string> res;
    size_t stopword_len = stopword.size();
    for (size_t i = 0, len = sentences.size(); i < len; ++i) {
        size_t j = 0;
        size_t sentence_len = sentences[i].size();
        while (j != std::string::npos && j < sentence_len) {
            size_t pos = sentences[i].find(stopword, j);
            if (pos != std::string::npos) {
#ifdef DEBUG
                printf("start pos [%u], end pos[%u], substring is [%s]\n", j, pos, sentences[i].substr(j, pos - j).c_str());
#endif
                res.push_back(sentences[i].substr(j, pos - j));
                pos += stopword_len;
            } else {
                res.push_back(sentences[i].substr(j));
#ifdef DEBUG
                printf("no stopword [%s] found after pos [%u] of sentence [%s]\n", stopword.c_str(), j, sentences[i].c_str());
#endif
            }
            j = pos;
        }
    }
#ifdef DEBUG
    printf("stopword:%s\n", stopword.c_str());
    printf("res size:%u\n", res.size());
#endif
    sentences = res;
}

double computeWeight(string sentence, const map<string, double> &word2weight) {
    vector<string> words;
    set<string> used_words;
    segment.cut(sentence, words);
    double weight = 0.0;
    for (size_t i = 0; i < words.size(); ++i) {
        if (used_words.find(words[i]) != used_words.end()) {
            map<string, double>::const_iterator p = word2weight.find(words[i]);
            if (p != word2weight.end()) {
                used_words.insert(p->first);
                weight += p->second;
            }
        }
    }
    return weight;
}

bool sort_weight(const pair<int, double> &a, const pair<int, double> &b) {
    return a.second > b.second;
}

bool sort_index(const pair<int, double> &a, const pair<int, double> &b) {
    return a.first < b.first;
}
