#ifndef __DAEMONIZE_H
#define __DAEMONIZE_H
#include <syslog.h>
#include <fcntl.h>
#include <sys/resource.h>
#include <sys/types.h>
#include <sys/stat.h>

void daemonize(const char *cmd, const char *wpath = "/") { //refer to apue 3rd edition
    int              i, fd0, fd1, fd2;
    pid_t            pid;
    struct rlimit    rl;
    struct sigaction sa;

    //clear file creation mask.
    umask(0);

    //get maximum number of file descriptors
    if (getrlimit(RLIMIT_NOFILE, &rl) < 0) {
        printf("%s: can't get file limit", cmd);
    }

    //become a session leader to lose controlling TTY.
    if ((pid = fork()) < 0) {
        printf("%s: can't fork", cmd);
    } else if (0 != pid) { //parent
        exit(0);
    }
    setsid();

    //ensure future opens won't allocate controlling TTYs.
    sa.sa_handler = SIG_IGN;
    sigemptyset(&sa.sa_mask);
    sa.sa_flags = 0;
    if (sigaction(SIGHUP, &sa, NULL) < 0) {
        printf("%s: can't ignore SIGHUP", cmd);
    }
    if ((pid = fork()) < 0) {
        printf("%s: can't fork", cmd);
    } else if (0 != pid) {//parent -- session leader
        exit(0);
    }

    //change the current working directory to the root so
    //we won't prevent file systems from being unmounted
    if (chdir(wpath) < 0) {
        printf("%s: can't change directory to %s", cmd, wpath);
    }

    //close all open file descriptors
    if (RLIM_INFINITY == rl.rlim_max) {
        rl.rlim_max = 1024;
    }
    for (i = 0; i < rl.rlim_max; ++i) {
        close(i);
    }

    //attach file descriptors 0, 1, and 2 to /dev/null.
    fd0 = open("/dev/null", O_RDWR);
    fd1 = dup(0);
    fd2 = dup(0);

    //initialize the log file
    /*
    openlog(cmd, LOG_CONS, LOG_DAEMON);
    if (0 != fd0 || 1 != fd1 || 2 != fd2) {
        syslog(LOG_ERR, "unexpected file descriptors %d %d %d", fd0, fd1, fd2);
        exit(1);
    }
     */

    if (0 != fd0 || 1 != fd1 || 2 != fd2) {
        exit(1);
    }
}

#endif //__DAEMONIZE_H
