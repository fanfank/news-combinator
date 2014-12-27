#include <algorithm>
#include <fstream>
#include <iostream>
#include <math.h>
#include <mysql.h>
#include <set>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>
#include "MixSegment.hpp"
#include "KeywordExtractor.hpp"
#include "PosTagger.hpp"
using namespace CppJieba;
const char * const dict_path       = "./dict/jieba.dict.utf8";
const char * const model_path      = "./dict/hmm_model.utf8";
const char * const idf_path        = "./dict/idf.utf8";
const char * const stop_words_path = "./dict/stop_words.utf8";
const char * const user_dict_path  = "./dict/user.dict.utf8";

const double       SIMILARITY_BOUND  = 0.85;
const unsigned int MAX_SIMILAR_TEXTS = 5;
const unsigned int MAX_TAG_NUM       = 15;

int last_mtime = -1;

bool   computeTF(map<string, int> &target_tf, map<string, int> &example_tf, string text);
double computeSimilarity(map<string, int> &tf1, map<string, int> &tf2);
int    cstr2int(char *s, int len);
int    str2int(string s);

bool   initLastMtime();
bool   setLastMtime();

bool      initMysql(MYSQL *p_mysql);
MYSQL_RES *execSql(MYSQL *p_mysql, char *query, int query_len);

MixSegment       segment(dict_path, model_path);
KeywordExtractor extractor(dict_path, model_path, idf_path, stop_words_path);
PosTagger        tagger(dict_path, model_path, user_dict_path);

int main(int argc, char *argv[]) {

    MYSQL     mysql;
    MYSQL_RES *my_res = NULL;
    //vector<string> passages;
    //vector<string> words;

    vector<pair<string, double> > wordweights;
    //vector<pair<string, string> > wordtags;
    //vector<pair<string, double> > wordweights_compared;
    int tags_num = MAX_TAG_NUM;

    if (!initLastMtime()) {
        fprintf(stderr, "Failed to get last mtime\n");
        exit(1);
    }

    if (!initMysql(&mysql)) {
        fprintf(stderr, "Failed to init mysql\n");
    }

    //execute sql
    char query[1000];
    sprintf(query, "SELECT `title`, `source_name`, `content`, `abstract_id`, `timestamp`, `source_news_id` FROM `news_content` WHERE `timestamp` > %d\0", last_mtime);
    my_res = execSql(&mysql, query, strlen(query));
    if (NULL == my_res) {
        fprintf(stderr, "Call mysql_store_result error. Error: %s\n", mysql_error(&mysql));
        mysql_close(&mysql);
        exit(1);
    }

    //format sql res
    map<string, vector<string> > formatted_res;
    MYSQL_ROW   row;
    MYSQL_FIELD *fields;
    int         row_num = 0;
    fields = mysql_fetch_fields(my_res);
    unsigned int num_fields = mysql_num_fields(my_res);
    while(row = mysql_fetch_row(my_res)) {
        for (unsigned int i = 0; i < num_fields; ++i) {
            formatted_res[fields[i].name].push_back(row[i]);
        }
        ++row_num;
    }
    mysql_free_result(my_res);
    fprintf(stdout, "Got %u entries ...\n", row_num);

    //start categorizing
    set<int> set_handled_index;
    set<int> set_similar_index;
    map<string, int> example_tf;
    map<string, int> target_tf;
    for (int i = 0; i < row_num; ++i) {
        if (set_handled_index.find(i) != set_handled_index.end()) {
            continue;
        }
        fprintf(stdout, "computing %s:%s ...\n", formatted_res["source_name"][i].c_str(), formatted_res["source_news_id"][i].c_str());
        double avg_similarity = 0.0;
        set_handled_index.insert(i);

        wordweights.clear();
        example_tf.clear();
        set_similar_index.clear();

        //set_similar_index.insert(i);
        extractor.extract(formatted_res["content"][i], wordweights, tags_num);
        for (unsigned int j = 0; j < wordweights.size(); ++j) {
            example_tf[wordweights[j].first] = 0;
        }

        //compute term frequency
        computeTF(example_tf, example_tf, formatted_res["content"][i]);

        for (int j = i + 1; j < row_num; ++j) {
            target_tf.clear();
            computeTF(target_tf, example_tf, formatted_res["content"][j]);
            //compute cosin similarity
            double similarity = computeSimilarity(example_tf, target_tf);
            if (similarity >= SIMILARITY_BOUND) {
                fprintf(stdout, "Found similar passager from %s:%s, similarity:%lf\n", formatted_res["source_name"][j].c_str(), formatted_res["source_news_id"][j].c_str(), similarity);
                set_similar_index.insert(j);
            } else {
                //fprintf(stdout, "similarity:%lf\n", similarity);
            }
            avg_similarity += similarity;
        }
        avg_similarity /= row_num;

        //handle similar texts
        //| title        | varchar(128)     | NO   |     |          |                |
        //| source_names | varchar(1024)    | NO   |     | NULL     |                |
        //| day_time     | int(11) unsigned | NO   | MUL | 19700101 |                |
        //| preview_pic  | varchar(1024)    | NO   |     |          |                |
        //| abstract_ids | varchar(1024)    | NO   |     |          |                |
        time_t ts     = static_cast<time_t>(str2int(formatted_res["timestamp"][i]));
        struct tm tm  = *localtime(&ts);
        char   dt[16];
        strftime(dt, sizeof(dt), "%Y%m%d", &tm);

        string title        = formatted_res["title"][i];
        string source_names = formatted_res["source_name"][i];
        int    day_time     = cstr2int(dt, strlen(dt));
        string preview_pic  = "";
        string abstract_ids = formatted_res["abstract_id"][i];

        //noise checking
        if (set_similar_index.size() > MAX_SIMILAR_TEXTS) {
            fprintf(stdout, "Too much noise in this passage: %s\n", formatted_res["source_news_id"][i].c_str());
            continue;
        }

        for (set<int>::iterator p = set_similar_index.begin(); p != set_similar_index.end(); ++p) {
            string s = ",";
            source_names.append(s + formatted_res["source_name"][*p]);
            abstract_ids.append(s + formatted_res["abstract_id"][*p]);
            set_handled_index.insert(*p);
        }

        //insert new entries
        if (!set_similar_index.empty()) {
            sprintf(query, "INSERT INTO `news_category` (`title`, `source_names`, `day_time`, `preview_pic`, `abstract_ids`) VALUES ('%s','%s',%d,'%s','%s')\0", title.c_str(), source_names.c_str(), day_time, preview_pic.c_str(), abstract_ids.c_str());
            my_res = execSql(&mysql, query, strlen(query));
            fprintf(stdout, "Average similarity:%lf\n", avg_similarity);
        }
        /*
        if (NULL == my_res) {
            fprintf(stderr, "Call mysql_store_result error. Error: %s\n", mysql_error(&mysql));
            mysql_close(&mysql);
            exit(1);
        }
        */
    }
    mysql_close(&mysql);
    setLastMtime();
    fprintf(stdout, "Done\n");
    return 0;
}

bool computeTF(map<string, int> &target_tf, map<string, int> &example_tf, string text) {
    vector<string> words;
    segment.cut(text, words);
    for (unsigned int i = 0; i < words.size(); ++i) {
        if (example_tf.find(words[i]) != example_tf.end()) {
            target_tf[words[i]]++;
        }
    }
    return true;
}

double computeSimilarity(map<string, int> &tf1, map<string, int> &tf2) {
    double numerator  = 0.0;
    double tf1_square = 0.0;
    double tf2_square = 0.0;
    for (map<string, int>::iterator p = tf1.begin(); p != tf1.end(); ++p) {
        string key  = p->first;
        //cout<<key<<endl;
        numerator  += tf1[key] * tf2[key];
        tf1_square += tf1[key] * tf1[key];
        tf2_square += tf2[key] * tf2[key];
    }
    double denominator = sqrt(tf1_square * tf2_square);
    if (fabs(denominator) < 0.00001) {
        return 0.0;
    }
    return numerator / denominator;
}

int cstr2int(const char *s, int len) {
    if (0 >= len) {
        return 0;
    }

    int offset = 0;
    for (; offset < len && ' ' == s[offset]; ++offset);

    int res    = 0;
    bool minus = false;
    for (int i = 0; i + offset < len; ++i) {
        int index = i + offset;
        if ('-' == s[index]) {
            if (minus || i != 0) {
                res = 0;
                break;
            } else {
                minus = true;
            }
        } else if ('+' == s[index]) {
            if (i != 0) {
                res = 0;
                break;
            }
        } else if (s[index] > '9' || s[index] < '0'){
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

int cstr2int(char *s, int len) {
    const char *cs = static_cast<const char*>(s);
    return cstr2int(cs, len);
}

int str2int(string s) {
    return cstr2int(s.c_str(), s.size());
}

bool initLastMtime() {
    std::filebuf fb;
    if (fb.open("lastmtime", std::ios::in)) {
        std::istream is(&fb);
        char buffer[11];
        is.read(buffer, 10);
        fb.close();
        last_mtime = cstr2int(buffer, 10);
        fprintf(stdout, "init last mtime:%d\n", last_mtime);
    }
    return true;
}

bool setLastMtime() {
    time_t ts;
    time(&ts);
    char cstr_ts[20];
    sprintf(cstr_ts, "%ld", ts);

    std::filebuf fb;
    fb.open("lastmtime", std::ios::out);
    std::ostream os(&fb);
    os<<cstr_ts;
    fb.close();
    return true;
}

bool initMysql(MYSQL *p_mysql) {
    mysql_init(p_mysql);
    if (!mysql_real_connect(p_mysql, "127.0.0.1", "root", "123abc", "reetsee_news", 3306, NULL, 0)) {
        fprintf(stderr, "Failed to connect to database: Error: %s\n", mysql_error(p_mysql));
        return false;
    } else {
        fprintf(stdout, "Connected to MySQL DB.\n");
    }

    //set charset
    if (!mysql_set_character_set(p_mysql, "utf8")) {
        printf("New client character set: %s\n", mysql_character_set_name(p_mysql));
    }
    return true;
}

MYSQL_RES *execSql(MYSQL *p_mysql, char *query, int query_len) {
    if (mysql_real_query(p_mysql, query, query_len)) {
        fprintf(stderr, "Execute sql error. Error: %s\n", mysql_error(p_mysql));
        return NULL; 
    }

    MYSQL_RES *my_res = mysql_store_result(p_mysql);
    /*
    if (NULL == my_res) {
        fprintf(stderr, "Call mysql_store_result error. Error: %s\n", mysql_error(p_mysql));
        return NULL;
    }
    */
    return my_res;
}
