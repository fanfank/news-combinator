#include<mysql.h>
int main(int argc, char *argv[]) {
    MYSQL mysql;
    mysql_init(&mysql);
    //mysql_options(&mysql, MYSQL_READ_DEFAULT_GROUP, "reetsee_news");
    mysql_options(&mysql, MYSQL_SET_CHARSET_NAME, "utf8");
    if (!mysql_real_connect(&mysql, "127.0.0.1", "reetsee", "123abc", "reetsee_news", 3306, NULL, 0)) {
        fprintf(stderr, "Failed to connect to database: Error: %s\n", mysql_error(&mysql));
        exit(1);
    } else {
        /*
         * success
         */
    }

    mysql_close(&mysql);
}
