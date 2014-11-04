#!/bin/bash

DEPLOY_PATH="$ENV_PATH/module/news"

OUTPUT_DIR="output"
PRODUCT="news"
OUTPUT_FILE="$PRODUCT.tar.gz"

mkdir -p $OUTPUT_DIR
rm -rf $OUTPUT_DIR/*
cp -rf html actions index.php $OUTPUT_DIR/

cd $OUTPUT_DIR
find ./ -name .git -exec rm -rf {} \;
tar zcvf $OUTPUT_FILE ./*

rm -rf html actions index.php

cp $OUTPUT_FILE $DEPLOY_PATH/
cd $DEPLOY_PATH
tar zxvf $OUTPUT_FILE
