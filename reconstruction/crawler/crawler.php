<?php
require_once('vendor/autoload.php');
require_once('phpfetcher.php'); //phpfetcher是Reetsee.Xu(即我)写的一个简单的PHP实现的爬虫框架
                                //可以参见https://github.com/fanfank/phpfetcher
require_once('reetsee.php');    //请参考： https://github.com/fanfank/reetsee_phplib

// 使用外部存储来保存图片
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;

$strQnAccessKey = ''; // 替换成自己的access key
$strQnSecretKey = ''; // 替换成自己的secret key
$strQnBucket = '';    // 替换成自己的bucket
$strQnPicHost = '';   // 替换成图片URL的HOST

$objQnAuth  = new Auth($strQnAccessKey, $strQnSecretKey);
$objQnBkMgr = new BucketManager($objQnAuth);

class news_crawler extends Phpfetcher_Crawler_Default {

    /**
     * @author  xuruiqi
     * @desc    上传图片到云存储
     */
    protected function _uploadPicToCloud($strPicUrl) {
        if (gettype($strPicUrl) != "string" || strlen($strPicUrl) == 0) {
            return false;
        }

        global $objQnBkMgr;
        global $strQnBucket;

        list($ret, $err) = $objQnBkMgr->fetch($strPicUrl, $strQnBucket);

        if ($err != NULL) {
            echo "Upload picture $strPicUrl failed.\n";
            return false;
        }

        global $strQnPicHost;
        return array(
            "pic_key" => $ret['key'],
            "pic_url" => $strQnPicHost . "/" . $ret['key'],
        );
    }

    /**
     * @author  xuruiqi
     * @return
     *      array(
     *          "content": string,
     *          "pic_list": array(
     *              array(
     *                  'pic_key': 'xxx',
     *                  'pic_url': 'yyy',
     *              ),
     *              array(
     *                  'pic_key': 'zzz',
     *                  'pic_url': 'uuu',
     *              ),
     *              ...
     *          ),
     *      )
     * @desc    通过$page抽取页面中的文本与图片
     */
    protected function _extractContent($page) {
        $strContent = "";
        $arrPic = array();
        $objContent = $page->sel('//p');
        for ($i = 0; $i < count($objContent); ++$i) {

            // 获取图片URL并上传图片到云存储
            $strPicContent = "";
            $objPic = $objContent[$i]->find("img");
            for ($j = 0; $j < count($objPic); ++$j) {
                $strSrcAttr = $objPic[$j]->getAttribute('src');
                $strAltAttr = $objPic[$j]->getAttribute('alt');

                // 对于失败自动重试一次，因为发现图片上传有冷启动问题
                $arrPicInfo = false;
                for ($k = 0; $arrPicInfo == false && $k < 2; ++$k) {
                    $arrPicInfo = $this->_uploadPicToCloud($strSrcAttr);
                }

                if ($arrPicInfo == false) {
                    continue;
                }

                if (gettype($strAltAttr) != "string") {
                    $strAltAttr = "";
                }

                // 拼出图片的HTML标签
                $strPicContent = $strPicContent 
                        . "<div style='text-align:center;'>"
                        . "<img src='{$arrPicInfo['pic_url']}' alt='$strAltAttr'></img>"
                        . "</div>";

                $arrPic[] = $arrPicInfo;
            }

            // 拼出段落内容的HTML标签
            $strContent = $strContent
                    . "<p>" . $strPicContent . "</p>"
                    . "<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
                    . trim($objContent[$i]->plaintext . "\n")
                    . "</p>";
        }

        return array(
            "content"  => $strContent,
            "pic_list" => $arrPic,
        );
    }

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

        // 替换成自己的数据库信息
        $db = Reetsee_Db::initDb('reetsee_news', '127.0.0.1', 3306, 'root', '123abc', 'utf8');
        if (NULL === $db) {
            echo "get db error\n";
        }

        // 插入摘要
        $arrSql = array(
            'table'  => 'news_abstract',
            'fields' => $arrData['news_abstract'],
            'dup'    => array(
                'timestamp' => intval(time()) , 
            ),
        );
        $res = $db->insert($arrSql['table'], $arrSql['fields']);
        if (!$res) {
            echo 'Insert content error:' . $db->error . ' ' . $db->errno . "\n";
            return FALSE;
        }

        // 插入详情
        $intLastAbsId = $db->insert_id;
        $arrData['news_content']['abstract_id'] = $intLastAbsId;
        $arrSql = array(
            'table'  => 'news_content',   
            'fields' => $arrData['news_content'],
            'dup'    => array(
                'timestamp' => intval(time()) , 
            ),
        );
        $res = $db->insert($arrSql['table'], $arrSql['fields']);
        if (!$res) {
            echo 'Insert content error:' . $db->error . ' ' . $db->errno . "\n";
            return FALSE;
        }

        // 更新摘要表中对应的详情条目id
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

        // 插入文章关联的图片列表
        foreach ($arrData['pic_list'] as $arrPicInfo) {
            $arrSql = array(
                'table' => 'news_picture',
                'fields' => array(
                    'id' => 0,
                    'abstract_id' => $intLastAbsId,
                    'day_time' => $arrData['news_abstract']['day_time'],
                    'pic_key' => $arrPicInfo['pic_key'],
                    'pic_url' => $arrPicInfo['pic_url'],
                    'ext' => '',
                ),
            );
            $res = $db->insert($arrSql['table'], $arrSql['fields']);
            if (!$res) {
                echo "Insert into news_picture failed:" . $db->error . ' ' . $db->errno . "\n";
            }
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

        $arrContent = $this->_extractContent($page);
        $strContent = $arrContent["content"];
        //$strContent = "";
        //$objContent = $page->sel('//p');
        //for ($i = 0; $i < count($objContent); ++$i) {
        //    //$arrContent[] = $objContent[$i]->plaintext;
        //    $strContent .= "<p>" . ($objContent[$i]->plaintext . "\n") . "</p>";
        //}

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
            'pic_list' => $arrContent['pic_list'],
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

        $arrContent = $this->_extractContent($page);
        $strContent = $arrContent["content"];
        //$strContent = "";
        //$objContent = $page->sel('//p');
        //for ($i = 0; $i < count($objContent); ++$i) {
        //    //$arrContent[] = $objContent[$i]->plaintext;
        //    $strContent .= "<p>" . ($objContent[$i]->plaintext . "\n") . "</p>";
        //}

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
            'pic_list' => $arrContent['pic_list'],
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

        $arrContent = $this->_extractContent($page);
        $strContent = $arrContent["content"];
        //$strContent = "";
        //$objContent = $page->sel('//p');
        //for ($i = 0; $i < count($objContent); ++$i) {
        //    //$arrContent[] = $objContent[$i]->plaintext;
        //    $strContent .= "<p>" . ($objContent[$i]->plaintext . "\n") . "</p>";
        //}

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
            'pic_list' => $arrContent['pic_list'],
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

$intTimeSt = time();

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

$intTimeEd = time();
echo "Cost " . ($intTimeEd - $intTimeSt) . " seconds\n";
echo "DONE\n";
?>
