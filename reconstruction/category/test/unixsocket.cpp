#include <errno.h>
#include <stddef.h>
#include <stdio.h>
#include <sys/socket.h>
#include <sys/un.h>
#include <time.h>
#include <unistd.h>

char socket_path[128] = "./unixsocket.socket";

int main(void) {
    int fd, size;
    struct sockaddr_un un;
    un.sun_family = AF_UNIX;
    strcpy(un.sun_path, socket_path);
    if ((fd = socket(AF_UNIX, SOCK_STREAM, 0)) < 0) {
        printf("socket failed\n");
    }

    size = offsetof(struct sockaddr_un, sun_path) + strlen(un.sun_path);
    unlink(socket_path);
    if (bind(fd, (struct sockaddr *)&un, size) < 0) {
        printf("bind failed\n");
    }

    printf("UNIX domain socket bound\n");
    return 0;
}
