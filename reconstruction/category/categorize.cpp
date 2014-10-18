#include <fstream>
#include <iostream>
#include <mysql.h>
#include <set>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>
#include "MixSegment.hpp"
#include "KeywordExtractor.hpp"
using namespace CppJieba;
const char * const dict_path       = "./dict/jieba.dict.utf8";
const char * const model_path      = "./dict/hmm_model.utf8";
const char * const idf_path        = "./dict/idf.utf8";
const char * const stop_words_path = "./dict/stop_words.utf8";
const char * const user_dict_path  = "./dict/user.dict.utf8";

const SIMILARITY_BOUND = 0.85;

int last_mtime = -1;

bool   computeTF(map<string, int> &target_tf, map<string, int> &example_tf, string text);
double computeSimilarity(map<string, int> &tf1, map<string, int> &tf2);
int    cstr2int(char *s, int len);

int main(int argc, char *argv[]) {

    MYSQL     mysql;
    MYSQL_RES *my_res = NULL;
    //vector<string> passages;
    vector<string> words;

    MixSegment  segment(dict_path, model_path);
    KeywordExtractor extractor(dict_path, model_path, idf_path, stop_words_path);
    PosTagger tagger(dict_path, model_path, user_dict_path);
    vector<pair<string, double> > wordweights;
    vector<pair<string, string> > wordtags;
    //vector<pair<string, double> > wordweights_compared;
    int tags_num = 11;
    last_time = getLastTime();

    if (!initLastMtime()) {
        fprintf(stderr, "Failed to get last mtime\n");
        exit(1);
    }

    if (!initMysql(&mysql)) {
        fprintf(stderr, "Failed to init mysql\n");
    }

    //execute sql
    char query[1000];
    fprintf(query, "SELECT `title`, `source_name`, `content`, `abstract_id`, `timestamp`, `source_news_id` FROM `news_content` WHERE `timestamp` > %d\0", last_mtime);
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
    int row_num = 0;
    fields = mysql_fetch_fields(my_res);
    unsigned int num_fields = mysql_num_fields(my_res);
    fprintf(stdout, "Got %u entries ...\n", num_fields);
    while(row = mysql_fetch_row(my_res)) {
        for (unsigned int i = 0; i < num_fields; ++i) {
            formatted_res[fields[i].name].push_back(row[i]);
        }
        ++row_num;
    }
    mysql_free_result(my_res);

    //start categorizing
    set<int> set_handled_index;
    set<int> set_similar_index;
    map<string, int> example_tf;
    map<string, int> target_tf;
    for (int i = 0; i < row_num; ++i) {
        if (set_handled_index.find(i)) {
            continue;
        }
        set_handled_index.insert(i);

        example_tf.clear();
        wordweights.clear();
        set_similar_index.clear();

        //set_similar_index.insert(i);
        extractor.extract(formatted_res["content"][i], wordweights, tags_num);
        for (int j = 0; j < wordtags.size(); ++j) {
            example_tf[wordweight[j].first] = 0;
        }

        // TODO computeTF
        computeTF(example_tf, example_tf, formatted_res["content"][i]);

        for (int j = i + 1; j < row_num; ++j) {
            target_tf.clear();
            computeTF(target_tf, example_tf, formatted_res["content"][j]);
            // TODO computeSimilarity
            double similarity = computeSimilarity(target_tf, example_tf);
            if (similarity >= SIMILARITY_BOUND) {
                fprintf(stdout, "Found similar passager from %s:%s, similarity:%lf\n", formatted_res["source_name"][j], formatted_res["source_news_id"], similarity);
                set_similar_index.insert(j);
            }
        }

        //handle similar texts
        //| title        | varchar(128)     | NO   |     |          |                |
        //| source_names | varchar(1024)    | NO   |     | NULL     |                |
        //| day_time     | int(11) unsigned | NO   | MUL | 19700101 |                |
        //| preview_pic  | varchar(1024)    | NO   |     |          |                |
        //| abstract_ids | varchar(1024)    | NO   |     |          |                |
        time_t ts     = static_cast<time_t> formatted_res["timestamp"];
        struct tm tm  = *localtime(&ts);
        char   dt[16];
        strftime(dt, sizeof(dt), "%Y%m%d", &tm);

        string title        = formatted_res["title"][i];
        string source_names = formatted_res["source_name"][i];
        // TODO str2int
        int    day_time     = cstr2int(dt, strlen(dt));
        string preview_pic  = "";
        string abstract_ids = formatted_res["abstract_id"][i];

        for (set<int>::iterator p = set_similar_index.begin(); p != set_similar_index.end(); ++p) {
            string s = ",";
            source_names.append(s + formatted_res["source_name"][*p]);
            abstract_ids.append(s + formatted_res["abstract_id"][*p]);
            set_handled_index.insert(*p);
        }

        //insert new entries
        fprintf(query, "INSERT INTO `news_category` (`title`, `source_names`, `day_time`, `preview_pic`, `abstract_ids`) VALUES ('%s','%s',%d,'%s','%s')\0", title.c_str(), source_names.c_str(), day_time, preview_pic.c_str(), abstract_ids.c_str());
        if (NULL == my_res) {
            fprintf(stderr, "Call mysql_store_result error. Error: %s\n", mysql_error(&mysql));
            mysql_close(&mysql);
            exit(1);
        }
    }
    mysql_close(&mysql);
    fprintf(stdout, "Done\n");
    return 0;
}

bool computeTF(map<string, int> &target_tf, map<string, int> &example_tf, string text) {

}

double computeSimilarity(map<string, int> &tf1, map<string, int> &tf2) {

}

int cstr2int(char *s, int len) {
    if (0 >= len) {
        return 0;
    }

    int offset = 0;
    for (; offset < len && ' ' == s[offset]; ++offset);

    int res    = 0;
    bool minus = false;
    for (int i = 0; i < len; ++i) {
        if ('-' == s[i]) {
            if (minus || i != 0) {
                res = 0;
                break;
            } else {
                minus = true;
            }
        } else if ('+' == s[i]) {
            if (i != 0) {
                res = 0;
                break;
            }
        } else {
            break;
        }

        //no overflow check
        res = res * 10 + s[i] - '0';
    }

    if (minus) {
        res *= -1;
    }
    return res;
}
