#!/bin/bash

DEPLOY_PATH="$ENV_PATH/module/news"

OUTPUT_DIR="output"
PRODUCT="news"
OUTPUT_FILE="$PRODUCT.tar.gz"
TARGET_FILES=" html actions static index.php "

mkdir -p $OUTPUT_DIR
rm -rf $OUTPUT_DIR/*
cp -rf $TARGET_FILES $OUTPUT_DIR/

cd $OUTPUT_DIR
find ./ -name .git -exec rm -rf {} \;
tar zcvf $OUTPUT_FILE ./*

rm -rf $TARGET_FILES

cp $OUTPUT_FILE $DEPLOY_PATH/
cd $DEPLOY_PATH
tar zxvf $OUTPUT_FILE
rm -rf $OUTPUT_FILE
