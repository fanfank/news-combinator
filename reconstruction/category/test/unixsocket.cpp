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

        //设置client fd为非阻塞模式
        int flags = fcntl(clfd, F_GETFL, 0);
        if (fcntl(fd, F_SETFL, flags & O_NONBLOCK) < 0) {
            printf("set clfd block flag failed\n");
            close(clfd);
            continue;
        }

        clientn++;
        printf("client %d comes.\n", clientn);

        int  n;
        char buf[BUFLEN];


        //读取数据
        while ((n = read(clfd, buf, 1)) > 0) {
            buf[n] = '\0';
            printf("%s", buf);
            fflush(stdout);
            //send(clfd, buf, n, 0);
        }
        printf("\n");
        printf("All received\n");
        fflush(stdout);

        if (n < 0) {
            printf("recv error\n");
        }

        n = snprintf(buf, BUFLEN - 1, "%d client", clientn);
        send(clfd, buf, n, 0);

        printf("client %d goes.\n", clientn);
        close(clfd);
    }

    return 0;
}
