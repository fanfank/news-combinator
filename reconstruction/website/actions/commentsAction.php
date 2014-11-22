<?php
/*
 * @author xuruiqi
 * @date   20141108
 * @desc   获取评论信息
 */
class commentsAction extends Actions_ActionBase {
    public function process() {
        //获取外站评论
        $intPn       = Reetsee_Http::get('pn', 1);
        $intRn       = Reetsee_Http::get('rn', 10);
        $strNewsInfo = Reetsee_Http::get('news_info', '');

        if (0 !== strlen($strNewsInfo)) {
            $arrNewsInfo = json_decode($strNewsInfo, true);
        }

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
        switch ($arrInfo['entry']['source_name']) {
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

    /**
     * @author xuruiqi
     * @param
     *      str $strContent //请求到的异步数据
     *      arr $arrInfo :
     *          int 'pn'    //页数
     *          int 'rn'    //每页记录数
     *          arr 'entry' : //和当前条目有关的信息
     *              arr 0 :
     *                  str 'source_name'   //来源名称
     *                  ...                 //其它信息
     *              arr 1 :
     *                  ...
     *              ...
     *              arr n :
     *                  ...
     * @return
     *      array :
     *          array 0 :
     *              str 'source'  //来源名称
     *              str 'user'    //用户名
     *              str 'time'    //格式:%Y-%m-%d %H:%i:%s
     *              str 'content' //评论内容
     *          array 1 :
     *              ...
     *          ...
     *          array n :
     *              ...
     *
     * @desc 获取评论
     */
    public function getComments($strContent, $arrInfo) {
        $arrComments = array();
        $matches     = array();
        $data        = array();
        $arrPathDict = array();
        switch ($arrInfo['entry']['source_name']) {
            case 'tencent':
                $res  = preg_match('#^myHotcommentList\((.*)\)#', $strContent, $matches);

                $data = json_decode($matches[1], true);
                $data = $data['data']['commentid'];

                $arrPathDict = array(
                    'source'  => 'tencent',   
                    'user'    => array('userinfo', 'nick'),
                    'time'    => array('time'),
                    'content' => array('content'),
                );
                break;

            case 'netease':
                $res = preg_match('#^var \w+=({.*});$#', $strContent, $matches);

                $data = json_decode($matches[1], true);
                $data = $data['hostPosts'];

                $arrPathDict = array(
                    'source'  => 'netease',   
                    'user'    => array('1', 'n'),
                    'time'    => array('1', 't'),
                    'content' => array('1', 'b'),
                );
                break;
            case 'sina':
                $data = json_decode($strContent, true);
                $data = $data['result']['host_list'];

                $arrPathDict = array(
                    'source'  => 'sina',   
                    'user'    => 'nick',
                    'time'    => 'time',
                    'content' => 'content',
                );

                break;
        }

        $arrComments = $this->formatComments($data, $arrPathDict);
        return $arrComments;
    }

    public function sortComments($comment1, $comment2) {
        if ('reetsee' === $comment1['source'] && 'reetsee' !== $comment2['source']) {
            return true;
        } else if ('reetsee' !== $comment1['source'] && 'reetsee' === $comment2['source']) {
            return false;
        }
        return $comment1['time'] > $comment2['time'];
    }

    /**
     * @author xuruiqi
     * @param
     *      array $arrData :
     *      array $arrPathDict :
     * @return
     *      array :
     *          array 0 :
     *              str 'source'
     *              str 'user'
     *              str 'time'    //格式:%Y-%m-%d %H:%i:%s
     *              str 'content' //评论内容
     *          array 1 :
     *              ...
     *          ...
     *          array n :
     *              ...
     *
     * @desc 格式化不同的评论
     */
    public function formatComments($arrData, $arrPathDict) {
        $arrOutput = array();
        if (!is_array($arrData) || !is_array($arrPathDict)) {
            return $arrOutput;
        }

        foreach ($arrData as $comment) {
            $arrCm = array();
            foreach ($arrPathDict as $key => $path) {
                if (is_string($path)) {
                    $arrCm[$key] = $path;
                } else {
                    $value = &$comment;
                    foreach ($path as $field) {
                        if (isset($value[$field])) {
                            $value = &$value[$field];
                        } else {
                            $value = NULL;
                            break;
                        }
                    }

                    if ('time' === $key && strlen(strval($value)) <= 12) {
                        //时间戳转换为日期格式
                        $value = date("%Y-%m-%d %H:%M:%S", $value);
                    }
                    $arrCm[$key] = $value;
                }
            }
            $arrOutput[] = $arrCm;
        }

        return $arrOutput;
    }
}
