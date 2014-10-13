#include <mysql.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <iostream>
#include <fstream>
#include "MixSegment.hpp"
#include "KeywordExtractor.hpp"
using namespace CppJieba;
const char * const dict_path       = "./dict/jieba.dict.utf8";
const char * const model_path      = "./dict/hmm_model.utf8";
const char * const idf_path        = "./dict/idf.utf8";
const char * const stop_words_path = "./dict/stop_words.utf8";
const char * const user_dict_path  = "./dict/user.dict.utf8";

int last_mtime = -1;

int main(int argc, char *argv[]) {

    MYSQL     mysql;
    MYSQL_RES *my_res = NULL;
    vector<string> passages;

    MixSegment  segment(dict_path, model_path);
    KeywordExtractor extractor(dict_path, model_path, idf_path, stop_words_path);
    vector<pair<string, double> > wordweights;
    int tags_num = 10;

    if (!initLastMtime()) {
        fprintf(stderr, "Failed to get last mtime\n");
        exit(1);
    }

    if (!initMysql(&mysql)) {
        fprintf(stderr, "Failed to init mysql\n");
    }

    //execute sql
    char query[1000];
    fprintf(query, "SELECT `title`, `source_name`, `content`, `abstract_id` FROM `news_content` WHERE `timestamp` > %d\0", last_mtime);
    my_res = execSql(&mysql, query, strlen(query));
    if (NULL == my_res) {
        fprintf(stderr, "Call mysql_store_result error. Error: %s\n", mysql_error(&mysql));
        mysql_close(&mysql);
        exit(1);
    }

    //format sql res
    map<string, vector<string> > formatted_res;
    MYSQL_ROW row;
    MYSQL_FIELD *fields;
    fields = mysql_fetch_fields(my_res);
    unsigned int num_fields = mysql_num_fields(my_res);
    fprintf(stdout, "Got %u entries ...\n", num_fields);
    while(row = mysql_fetch_row(my_res)) {
        for (unsigned int i = 0; i < num_fields; ++i) {
            formatted_res[fields[i].name].push_back(row[i]);
        }
    }
    mysql_free_result(my_res);

    //start categorizing TODO

}
