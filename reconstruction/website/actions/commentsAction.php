<?php
/*
 * @author xuruiqi
 * @date   20141108
 * @desc   获取评论信息
 */
class Actions_commentsAction {
    public function process() {
        //获取外站评论
        $intPn       = Reetsee_Http::get('Pn', 1);
        $intRn       = Reetsee_Http::get('Rn', 10);
        $arrNewsInfo = Reetsee_Http::get('news_info', array());

        $arrCurlConf = array(
            'CURLOPT_CONNECTTIMEOUT' => 10,
            'CURLOPT_MAXREDIRS'      => 10,
            'CURLOPT_RETURNTRANSFER' => 1,    
            'CURLOPT_TIMEOUT'        => 15,
            'CURLOPT_URL'            => NULL,
            'CURLOPT_USERAGENT'      => 'phpfetcher',
        );

        $arrComments = array();
        $objCurl = curl_init();
        foreach ($arrNewsInfo as $entry) {
            $arrInfo = array(
                'pn'    => $intPn,
                'rn'    => $intRn,    
                'entry' => $entry,
            );
            $this->setCurlConf($arrCurlConf, $arrInfo);
            curl_setopt_array($objCurl);

            $strContent = curl_exec($objCurl);
            if (FALSE != $strContent) {
                $arrComments = array_merge($arrComments, $this->getComments($strContent, $arrInfo));
            }
        }

        //获取本地评论 TODO
        ///

        usort($arrComments, array($this, 'sortComments'));
        $this->_retJson($arrComments);
    }

    public function setCurlConf(&$arrCurlConf, $arrInfo) {
        switch ($entry['source_name']) {
            case 'tencent':
                $strDomain = 'http://coral.qq.com';
                $strReq    = "/article/{$arrInfo['entry']['source_comment_id']}/hotcomment?reqnum={$arrInfo['rn']}&callback=myHotcommentList&_=" . intval(time()) . "444&ie=utf-8";
                break;

            case 'netease':
                $arrExt = unserialize($arrInfo['ext']);

                $strDomain = 'http://comment.news.163.com';
                $strReq    = "/data/{$arrExt['board_id']}/df/{$arrInfo['source_comment_id']}_1.html";

                break;
            case 'sina':
                $arrExt = unserialize($arrInfo['ext']);

                $strDomain = 'http://comment5.news.sina.com.cn';
                $strReq    = "/page/info?format=json&channel={$arrExt['channel_id']}&newsid={$arrInfo['source_news_id']}&group=" . intval($arrExt['group']) . "&compress=1&ie=utf-8&oe=utf-8&page={$arrExt['pn']}&page_size={$arrExt['rn']}&jsvar=requestId_444";

                break;
        }
        $strUrl = $strDomain . $strReq;
        $arrCurlConf['CURLOPT_URL'] = $strUrl;
    }
}
