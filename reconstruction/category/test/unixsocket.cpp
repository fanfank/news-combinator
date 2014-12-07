#include <errno.h>
#include <fcntl.h>
#include <signal.h>
#include <stddef.h>
#include <stdio.h>
#include <sys/socket.h>
#include <sys/un.h>
#include <time.h>
#include <unistd.h>

#define BUFLEN  128

char socket_path[128] = "./unixsocket.socket";

vector<vector<string> > get_packets(const int *p_clfd, string last_buf, string target_type, int nr_packets) {

}

int read_packets(const int *p_clfd, map<string, vector<string> > *p_packets) {
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
    int nr_packets = atoi((*p_packets)["syn"].c_str());

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

string handle_packets() {

}

int send_packets() {

}

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

        int  n;
        char buf[BUFLEN];
        int  errno;

        map<string, vector<string> > packets;

        errno = read_packets(&clfd, &packets);
        if (errno != 0) {
            printf("read packets error\n");
            return -1;
        }

        string res = handle_packets(&packets["data"]);

        errno = send_packets(res);
        if (errno != 0) {
            printf("read packets error\n");
            return -1;
        }

        close(clfd);
    }

    return 0;
}
