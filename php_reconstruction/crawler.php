<?php
require_once('phpfetcher.php');
class news_crawler extends Phpfetcher_Crawler_Default {
    public function handlePage($page) {
        //print_r($page->getHyperLinks());
    }
}

$crawler = new news_crawler();
$arrFetchJobs = array(
    'tencent' => array(
        'start_page' => 'http://news.qq.com',   
        'link_rules' => array(
            '/(.*)\/a\/(\d{8})\/(\d+)\.htm/',    
        ),
        'max_depth' => 4, 
    ),
    'netease' => array(
        'start_page' => 'http://news.163.com', 
        'link_rules' => array(
            '/(http://news\.163\.com)/(\d{2})/(\d{4})/\d+/(\w+)\.html/',   
        ),
        'max_depth' => 4, 
    ),        
    'sina' => array(
        'start_page' => 'http://news.sina.com.cn',   
        'link_rules' => array(
            '/(http://(?:\w+\.)*news\.sina\.com\.cn)/.*/(\d{4}-\d{2}-\d{2})/\d{4}(\d{8})\.(?:s)html/'    
        ),
        'max_depth' => 4, 
    ),
);
$crawler->setFetchJobs($arrFetchJobs)->run();

?>
