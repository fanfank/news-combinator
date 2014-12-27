#ifndef __PROTOCOL_H
#define __PROTOCOL_H
#include <fstream>
#include <map>
#include <stdlib.h>
#include <string>
#include <vector>

#define BUFLEN  128
#define PAYLOADLEN 4096

using namespace std;

extern string _itoa(int num);

char odd_check_byte(string data);

vector<vector<string> > get_packets(const int * const p_clfd, string last_buf, string target_type, int nr_packets);

int read_packets(const int * const p_clfd, map<string, vector<string> > *p_packets);
int send_packets(const int * const p_clfd, string data);

string         pack_syn(int num_packets);
vector<string> pack_data(string data, int payload_mx_len);
string         pack_fin();

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
        size_t buf_len = buf.size();

        size_t st = 0;
        size_t ed = 0;
        do {
            if (0 == status) {
                size_t st_old = st;

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

#endif //__PROTOCOL_H
