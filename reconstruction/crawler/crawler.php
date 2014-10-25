<?php
require_once('phpfetcher.php'); //phpfetcher是Reetsee.Xu(即我)写的一个简单的PHP实现的爬虫框架
                                //可以参见https://github.com/fanfank/phpfetcher
require_once('reetsee.php');    //请参考： https://github.com/fanfank/reetsee_phplib

class news_crawler extends Phpfetcher_Crawler_Default {
    public function handlePage($page) {
        //print_r($page->getHyperLinks());
        $arrExtraInfo = $page->getExtraInfo(array('job_name'));
        $strJobName = $arrExtraInfo['job_name'];

        switch ($strJobName) {
            case 'tencent':
                $arrData = $this->parseTencentNews($page);
                break;
            case 'netease':
                $arrData = $this->parseNeteaseNews($page);
                break;
            case 'sina':
                $arrData = $this->parseSinaNews($page);
                break;
        }

        if (!$this->checkData($arrData)) {
            return TRUE;
        }

        $db = Reetsee_Db::initDb('reetsee_news', '127.0.0.1', 3306, 'root', '123abc', 'utf8');
        if (NULL === $db) {
            echo "get db error\n";
        }

        $arrSql = array(
            'table'  => 'news_abstract',
            'fields' => $arrData['news_abstract'],
        );
        $res = $db->insert($arrSql['table'], $arrSql['fields']);
        if (!$res) {
            echo 'Insert content error:' . $db->error . ' ' . $db->errno . "\n";
            return FALSE;
        }

        $intLastAbsId = $db->insert_id;
        $arrData['news_content']['abstract_id'] = $intLastAbsId;
        $arrSql = array(
            'table'  => 'news_content',   
            'fields' => $arrData['news_content'],
        );
        $res = $db->insert($arrSql['table'], $arrSql['fields']);
        if (!$res) {
            echo 'Insert content error:' . $db->error . ' ' . $db->errno . "\n";
            return FALSE;
        }

        $intLastCtId = $db->insert_id;
        $arrSql = array(
            'table'  => 'news_abstract',   
            'fields' => array(
                'content_id' => $intLastCtId,    
            ),
            'conds' => array(
                'id=' => $intLastAbsId,    
            ),

        );
        $res = $db->update($arrSql['table'], $arrSql['fields'], $arrSql['conds']);
        if (!$res) {
            echo 'Update abstract error:' . $db->error . ' ' . $db->errno . "\n";
            return FALSE;
        }
        
        return TRUE;
    }

    /**
     * @author xuruiqi
     * @desc 解析腾讯新闻
     */
    public function parseTencentNews($page) {
        $strUrl = $page->getUrl();
        $timestamp = intval(time());
        echo "Checking $strUrl ...\n";

        //$arrContent = array();
        $strContent = "";
        $objContent = $page->sel('//p');
        for ($i = 0; $i < count($objContent); ++$i) {
            //$arrContent[] = $objContent[$i]->plaintext;
            $strContent .= ($objContent[$i]->plaintext . "\n");
        }

        $matches = array();
        $intRes = preg_match('#(.*)/a/(\d{8})/(\d+)\.htm#', $strUrl, $matches);

        if (FALSE === $intRes || 0 === $intRes) {
            return array();
        }


        //获取评论id
        preg_match('#cmt_id = (.*);#', $page->getContent(), $matches_comment_id);

        $strTitle = trim($page->sel('//h1', 0)->plaintext);
        $arrOutput = array(
            'news_abstract' => array(
                'id'          => 0,
                'title'       => $strTitle,
                'icon_pic'    => '',
                'rate_points' => 0,
                'rate_counts' => 0,
                'quality'     => 0,
                'content_id'  => 0,
                'day_time'    => intval($matches[2]),
                'timestamp'   => $timestamp,
                'source_news_id'      => strval($matches[3]),
            ),
            'news_content' => array(
                'id'                  => 0, 
                'title'               => $strTitle,
                'source_name'         => 'tencent',
                'content'             => trim($strContent),
                'source_news_link'    => $strUrl,
                'source_comment_link' => "http://coral.qq.com/{$matches_comment_id[1]}",
                'source_news_id'      => strval($matches[3]),
                'source_comment_id'   => strval($matches_comment_id[1]),
                'abstract_id'         => 0,
                'timestamp'           => $timestamp,
                'ext'                 => '',
            ),
        );
        return $arrOutput;
    }

    /**
     * @author xuruiqi
     * @desc 解析网易新闻
     */
    public function parseNeteaseNews($page) {
        $strUrl = $page->getUrl();
        $timestamp = intval(time());
        echo "Checking $strUrl ...\n";

        //$arrContent = array();
        $strContent = "";
        $objContent = $page->sel('//p');
        for ($i = 0; $i < count($objContent); ++$i) {
            //$arrContent[] = $objContent[$i]->plaintext;
            $strContent .= ($objContent[$i]->plaintext . "\n");
        }

        $matches = array();
        $intRes = preg_match('#(http://news\.163\.com)/(\d{2})/(\d{4})/\d+/(\w+)\.html#', $strUrl, $matches);
        if (FALSE === $intRes || 0 === $intRes) {
            return array();
        }


        //获取boardId
        preg_match('#boardId = "(.*)"#', $page->getContent(), $matches_board_id);

        $strTitle = trim($page->sel('//h1[@id=\'h1title\']', 0)->plaintext);
        $arrOutput = array(
            'news_abstract' => array(
                'id'          => 0,
                'title'       => $strTitle,
                'icon_pic'    => '',
                'rate_points' => 0,
                'rate_counts' => 0,
                'quality'     => 0,
                'content_id'  => 0,
                'day_time'    => intval("20{$matches[2]}{$matches[3]}"),
                'timestamp'   => $timestamp,
                'source_news_id'      => strval($matches[4]),
            ),
            'news_content' => array(
                'id'                  => 0, 
                'title'               => $strTitle,
                'source_name'         => 'netease',
                'content'             => trim($strContent),
                'source_news_link'    => $strUrl,
                'source_comment_link' => "http://comment.news.163.com/{$matches_board_id[1]}/{$matches[4]}.html",
                'source_news_id'      => strval($matches[4]),
                'source_comment_id'   => strval($matches[4]),
                'abstract_id'         => 0,
                'timestamp'           => $timestamp,
                'ext'                 => serialize(array('board_id' => $matches_board_id[1])),
            ),
        );
        return $arrOutput;
    }

    /**
     * @author xuruiqi
     * @desc 解析新浪新闻
     */
    public function parseSinaNews($page) {
        $strUrl = $page->getUrl();
        $timestamp = intval(time());
        echo "Checking $strUrl ...\n";

        //获取新闻正文
        //$arrContent = array();
        $strContent = "";
        $objContent = $page->sel('//p');
        for ($i = 0; $i < count($objContent); ++$i) {
            //$arrContent[] = $objContent[$i]->plaintext;
            $strContent .= ($objContent[$i]->plaintext . "\n");
        }

        $matches = array();
        $intRes = preg_match('#(http://(?:\w+\.)*news\.sina\.com\.cn)/.*/(\d{4}-\d{2}-\d{2})/\d{4}(\d{8})\.(?:s)html#', $strUrl, $matches);
        if (FALSE === $intRes || 0 === $intRes) {
            return array();
        }


        //获取newsId
        preg_match('#comment_id:(\d-\d-\d+)#', $page->getContent(), $matches_news_id);

        //获取channelId
        preg_match('#comment_channel:(\w+);#', $page->getContent(), $matches_channel_id);

        $strTitle = trim($page->sel('//h1[@id=\'artibodyTitle\']', 0)->plaintext);
        $arrOutput = array(
            'news_abstract' => array(
                'id'          => 0,
                'title'       => $strTitle,
                'icon_pic'    => '',
                'rate_points' => 0,
                'rate_counts' => 0,
                'quality'     => 0,
                'content_id'  => 0,
                'day_time'    => intval(implode(explode('-', $matches[2]))),
                'timestamp'   => $timestamp,
                'source_news_id'      => $matches_news_id[1],
            ),
            'news_content' => array(
                'id'                  => 0, 
                'title'               => $strTitle,
                'source_name'         => 'sina',
                'content'             => trim($strContent),
                'source_news_link'    => $strUrl,
                'source_comment_link' => "http://comment5.news.sina.com.cn/comment/skin/default.html?channel={$matches_channel_id[1]}&newsid={$matches_news_id[1]}",
                'source_news_id'      => $matches_news_id[1],
                'source_comment_id'   => $matches_news_id[1],
                'abstract_id'         => 0,
                'timestamp'           => $timestamp,
                'ext'                 => serialize(array('channel_id' => $matches_channel_id[1])),
            ),
        );
        return $arrOutput;
    }

    public function checkData($arrData) {
        if (empty($arrData['news_abstract']['title']) || empty($arrData['news_content']['content'])) {
            return false;
        }
        return true;
    }
}

$crawler = new news_crawler();
$arrFetchJobs = array(
    'tencent' => array(
        'start_page' => 'http://news.qq.com',   
        'link_rules' => array(
            '#(.*)/a/(\d{8})/(\d+)\.htm$#'
        ),
        'max_depth' => 2, 
    ),
    'netease' => array(
        'start_page' => 'http://news.163.com', 
        'link_rules' => array(
            '#(http://news\.163\.com)/(\d{2})/(\d{4})/\d+/(\w+)\.html$#',
        ),
        'max_depth' => 2, 
    ),        
    'sina' => array(
        'start_page' => 'http://news.sina.com.cn',   
        'link_rules' => array(
            '#(http://(?:\w+\.)*news\.sina\.com\.cn)/.*/(\d{4}-\d{2}-\d{2})/\d{4}(\d{8})\.(?:s)html$#',
        ),
        'max_depth' => 2, 
    ),
);
$crawler->setFetchJobs($arrFetchJobs)->run();

echo "DONE\n";
?>
