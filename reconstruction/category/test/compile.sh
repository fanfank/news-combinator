#!/bin/bash
if [ x$1 != x ]
then
    #...有参数
    echo "executing: gcc -c `$MYSQL_PATH/bin/mysql_config --cflags` $1"
    gcc -c `$MYSQL_PATH/bin/mysql_config --cflags` $1
    if [ $? -eq 0 ]
    then
        file_name=$1
        file_prefix=${file_name%.*}
        file_link=$file_prefix".o"
        echo "executing: gcc -o $file_prefix $file_link `$MYSQL_PATH/bin/mysql_config --libs`"
        gcc -o $file_prefix $file_link `$MYSQL_PATH/bin/mysql_config --libs`
        rm -f $file_link
    fi
    #echo "executing: g++ $1 `$MYSQL_PATH/bin/mysql_config --cflags --libs`"
    #g++ $1 `$MYSQL_PATH/bin/mysql_config --cflags --libs`
else
    #...没有参数
    echo "usage: sh compile.sh 文件名\n"
fi
