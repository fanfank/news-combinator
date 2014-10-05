#include<mysql.h>
#include<stdio.h>
int main(int argc, char *argv[]) {
    //MYSQL mysql;
    //mysql_init(&mysql);
    printf("MySQL client version: %s\n", mysql_get_client_info());
    return 0;
}
