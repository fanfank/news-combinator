#include<mysql.h>
#include<stdio.h>
#include<stdlib.h>
#include<string.h>
int main(int argc, char *argv[]) {
    MYSQL mysql;
    mysql_init(&mysql);
    //mysql_options(&mysql, MYSQL_READ_DEFAULT_GROUP, "reetsee_news");
    //mysql_options(&mysql, MYSQL_SET_CHARSET_NAME, "utf8");
    if (!mysql_real_connect(&mysql, "127.0.0.1", "root", "123abc", "reetsee_news", 3306, NULL, 0)) {
        fprintf(stderr, "Failed to connect to database: Error: %s\n", mysql_error(&mysql));
        exit(1);
    } else {
         //success
        fprintf(stdout, "Connected to MySQL DB.\n");
    }

    //set charset
    if (!mysql_set_character_set(&mysql, "utf8")) {
        printf("New client character set: %s\n", mysql_character_set_name(&mysql));
    }

    //execute sql
    char *query = "SELECT version()\0";
    if (mysql_real_query(&mysql, query, strlen(query))) {
        fprintf(stderr, "Execute sql error. Error: %s\n", mysql_error(&mysql));
        mysql_close(&mysql);
        exit(1);
    }

    //get results
    MYSQL_RES *my_res = mysql_store_result(&mysql);
    if (NULL == my_res) {
        fprintf(stderr, "Call mysql_store_result error. Error: %s\n", mysql_error(&mysql));
        mysql_close(&mysql);
        exit(1);
    }

    //fetch rows
    MYSQL_ROW row;
    MYSQL_FIELD *fields;
    fields = mysql_fetch_fields(my_res);
    unsigned int i;
    unsigned int num_fields = mysql_num_fields(my_res);
    while(row = mysql_fetch_row(my_res)) {
        for (i = 0; i < num_fields; ++i) {
            printf("%s: %s\n", fields[i].name, /*fields[i].size,*/ row[i]);
        }
    }

    //free memory
    mysql_free_result(my_res);
    mysql_close(&mysql);
    printf("Done.\n");
    exit(0);
}
