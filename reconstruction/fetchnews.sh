#!/bin/bash
cd $ENV_PATH/service
$PHP_PATH/bin/php crawler/crawler.php
cd category
./categorize
