#include<mysql.h>
#include<stdio.h>
#include<stdlib.h>
#include<string.h>
#include <iostream>
#include <fstream>
#include "MixSegment.hpp"
#include "KeywordExtractor.hpp"
#include "PosTagger.hpp"
using namespace CppJieba;
const char * const dict_path       = "../dict/jieba.dict.utf8";
const char * const model_path      = "../dict/hmm_model.utf8";
const char * const idf_path        = "../dict/idf.utf8";
const char * const stop_words_path = "../dict/stop_words.utf8";
const char * const user_dict_path  = "../dict/user.dict.utf8";

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
    char *query = "SELECT `content` FROM `news_content` LIMIT 1\0";
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

    //INIT cppjieba
    MixSegment       segment(dict_path, model_path);
    KeywordExtractor extractor(dict_path, model_path, idf_path, stop_words_path);
    PosTagger tagger(dict_path, model_path, user_dict_path);
    vector<pair<string, double> > wordweights;
    vector<pair<string, string> > wordtags;
    vector<string> words;
    size_t topN = 10;


    //fetch rows
    MYSQL_ROW row;
    MYSQL_FIELD *fields;
    fields = mysql_fetch_fields(my_res);
    unsigned int i;
    unsigned int num_fields = mysql_num_fields(my_res);
    while(row = mysql_fetch_row(my_res)) {
        for (i = 0; i < num_fields; ++i) {
            printf("%s: %s\n", fields[i].name, /*fields[i].size,*/ row[i]);
            string cppjieba_content = string(row[i]);

            //cppjieba segmentation
            words.clear();
            segment.cut(cppjieba_content, words);
            cout << words << endl;

            //cppjieba extractor
            wordweights.clear();
            extractor.extract(cppjieba_content, wordweights, topN);
            cout << wordweights << endl;

            //cppjieba tagger
            wordtags.clear();
            tagger.tag(cppjieba_content, wordtags);
            cout << wordtags << endl;
        }
    }

    //free memory
    mysql_free_result(my_res);
    mysql_close(&mysql);
    printf("Done.\n");
    exit(0);
}
