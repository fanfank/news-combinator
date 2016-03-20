<?php
/**********************************
 * @author  xuruiqi
 * @desc    批量删除5天前的新闻图片
 **********************************/

require_once('vendor/autoload.php');

require_once('reetsee.php');    //请参考： https://github.com/fanfank/reetsee_phplib

// 初始化外部存储管理对象
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;

$strQnAccessKey = ''; // 替换成自己的access key
$strQnSecretKey = ''; // 替换成自己的secret key
$strQnBucket = '';    // 替换成自己的bucket

$objQnAuth  = new Auth($strQnAccessKey, $strQnSecretKey);
$objQnBkMgr = new BucketManager($objQnAuth);

// 初始化到数据库的连接
$strDbName = 'reetsee_news';
$strHost   = '127.0.0.1';
$intPort   = 3306;
$strUser   = 'root';
$strPassword = '123abc';
$strCharset  = 'utf8';

$db = Reetsee_Db::initDb(
        $strDbName, $strHost, $intPort, $strUser, $strPassword, $strCharset);

if (NULL === $db) {
    echo "Get db error\n";
    return -1;
}

// 查找5天以前的所有图片
$intEarliestTs  = strtotime("-5 days");
$strEarliestDay = date("Ymd", $intEarliestTs);
$arrSql = array(
    'table' => 'news_picture',
    'fields' => ['id', 'pic_key'],
    'conds' => array(
        'day_time<=' => $strEarliestDay,
    ),
);

$res = $db->select($arrSql['table'], $arrSql['fields'], $arrSql['conds']);
if (false === $res) {
    echo 'Select from news_pictures table error: ' 
            . $db->error . ' ' . $db->errno . "\n";
}

// 从外部存储中逐条删除图片，以及数据库记录
// 因为此脚本造成的数据库压力小，不需要将操作批量化
foreach ($res as $entry) {
    $err = $objQnBkMgr->delete($strQnBucket, $entry['pic_key']);
    if ($err !== null && $err->code() != 612) {
        echo "Delete pic " . $entry['pic_key'] . " failed.\n";
        continue;
    } 

    // 删除外部存储成功，删除数据库记录
    $arrSql = array(
        'table' => 'news_picture',
        'conds' => array(
            'id=' => $entry['id'],
        ),
    );

    // 注意不能用res变量名，否则会修改循环体
    $ret = $db->delete($arrSql['table'], $arrSql['conds']);
    if (false === $ret) {
        echo "Delete pic " . $entry['pic_key'] . " from DB failed.\n";
    }
}

echo "Done\n";
