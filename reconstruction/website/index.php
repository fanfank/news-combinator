<?php
require_once('reetsee.php');

$intRange = intval($_GET['range']);
if (empty($intRange) || $intRange < 1) {
    $intRange = 2;
}

$db = Reetsee_Db::initDb('reetsee_news', '127.0.0.1', 3306, 'root', '123abc', 'utf8');
if (NULL === $db) {
    echo "get db error\n";
    exit(1);
}

//+--------------+------------------+------+-----+----------+----------------+
//| Field        | Type             | Null | Key | Default  | Extra          |
//+--------------+------------------+------+-----+----------+----------------+
//| id           | int(11) unsigned | NO   | PRI | NULL     | auto_increment |
//| title        | varchar(128)     | NO   |     |          |                |
//| source_names | varchar(1024)    | NO   |     | NULL     |                |
//| day_time     | int(11) unsigned | NO   | MUL | 19700101 |                |
//| preview_pic  | varchar(1024)    | NO   |     |          |                |
//| abstract_ids | varchar(1024)    | NO   |     |          |                |
//+--------------+------------------+------+-----+----------+----------------+
$intEarliestTs  = strtotime("-$intRange days");
$strEarliestDay = date("Ymd", $intEarliestTs);
$arrSql = array(
    'table'  => 'news_category',
    'fields' => array(
        'id', ' title', 'source_names', 'day_time', 'preview_pic', 'abstract_ids' ,  
    ),  
    'conds'  => array(
        'day_time>=' => $strEarliestDay, 
    ),
);

$res = $db->select($arrSql['table'], $arrSql['fields'], $arrSql['conds']);
if (!$res) {
    Reetsee_Log::error('Insert abstract error:' . $db->error . ' ' . $db->errno);
    include implode(PATH_SEPERATOR, array(DIR(__FILE__), 'html', 'reetsee_news_404.html'));
    //include_once("reetsee_news_404.html");
    exit(1);
}

$data = array();
foreach ($res as $entry) {
    $data[$entry['day_time']][] = $entry;
}
include implode(PATH_SEPERATOR, array(DIR(__FILE__), 'html', 'index.html'));
exit(0);
