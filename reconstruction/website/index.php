<?php
require_once('reetsee.php');

$intRange = $_GET['range'];
if (empty($intRange)) {
    $intRange = 5;
}

$dbm = new Reetsee_Db();
$dbm->initDb('reetsee_news', 'utf8', '127.0.0.1', '3306', 'root', '123abc');
$res = $dbm->select();
if (!$res) {
    $db = $dbm->getDb('reetsee_news');
    Reetsee_Log::error('Insert abstract error:' . $db->error . ' ' . $db->errno);
    include_once("reetsee_news_404.html");
    exit(0);
}

// TODO
