<?php
define("MODULE_PATH", dirname(__FILE__));

$strActionPath = "";

$strPathInfo = $_SERVER['PATH_INFO'];
$arrTmpPathInfo = explode('?', $strPathInfo, 1);
$arrTmpPathInfo = array_filter(explode('/', $arrTmpPathInfo[0]));
array_shift($arrTmpPathInfo);

$arrPathInfo = array();
if (!empty($arrTmpPathInfo)) {
    foreach ($arrTmpPathInfo as $strPathComp) {
        $arrPathInfo[] = $strPathComp;
    }
} else {
    $arrPathInfo = array("index");
}

array_unshift($arrPathInfo, 'actions');
$strActionClassName = end($arrPathInfo) . 'Action';
$strActionPath = MODULE_PATH . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $arrPathInfo) . 'Action.php';

if (!file_exists($strActionPath)) {
    echo "Oooops...Nothing here";
} else {
    require_once($strActionPath);
    $objAction = new $strActionClassName;
    $objAction->process();
}
