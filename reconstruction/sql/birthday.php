<?php
/**
 * @author xuruiqi
 * @date   20150420
 * @desc   生日活动策划
 */
require_once('reetsee.php');
function main() {
    $db = Reetsee_Db::initDb('reetsee_news', '127.0.0.1', 3306, 'root', '123abc', 'utf8');
    if (NULL === $db) {
        echo "get db error\n";
        return -1;
    }

    //插入文章摘要
    $arrSql = array(
        'table'  => 'news_abstract',
        'fields' => array(
            'title'          => '我国今日喜迎杨莹生日！',    
            'icon_pic'       => '',
            'rate_points'    => 10,
            'rate_counts'    => 0,
            'quality'        => 100,
            'content_id'     => 0,
            'source_news_id' => 1314520,
            'day_time'       => 20150421,
            'timestamp'      => 1429545601,
        ),
    );
    $res = $db->insert($arrSql['table'], $arrSql['fields']);
    if (!$res) {
        echo "Insert content error:" . $db->error . ' ' . $db->errno . "\n";
        return -2;
    }
    $intLastAbsId = $db->insert_id;

    //构造分类
    $arrSql = array(
        'table'  => 'news_category',   
        'fields' => array(
            'title'        => '我国今日喜迎杨莹生日！',
            'source_names' => 'reetsee',
            'day_time'     => 20150421,
            'preview_pic'  => '',
            'abstract_ids' => strval($intLastAbsId),
        ),
    );
    $res = $db->insert($arrSql['table'], $arrSql['fields']);
    if (!$res) {
        echo 'Insert category error:' . $db->error . ' ' . $db->errno . "\n";
        return -5;
    }
    $intLastCatId = $db->insert_id;

    //插入文章详细内容
    $strContent = '<p>    据悉，今天是中华人民共和国传统的杨莹破壳日。</p>' . 
        '<p>    杨莹生于广东省普宁市，是典型的潮汕好女人，同时其毫不利己、专门利人的精神使其在亲朋好友心中的形象格外高大。</p>' . 
        '<p>    本报了解到，自2012年11月25日起，杨莹已经低调地与一名不能透露姓名的徐瑞琦先生在一起，并且日前已经收到了这么先生的神秘小礼物，真是可喜可贺～！</p>' .
        '<p>    在这值得庆祝的日子里，我们吹水新闻全体上下也祝杨莹美女生日快乐～新的一岁身体健康，天天开心！</p>';
    $arrSql = array(
        'table'  => 'news_content',
        'fields' => array(
            'title'               => '我国今日喜迎杨莹生日！',    
            'source_name'         => 'reetsee',
            'content'             => $strContent,
            'source_news_link'    => 'http://news.reetsee.com/entry?category=' . $intLastCatId,
            'source_comment_link' => '',
            'source_news_id'      => strval($intLastAbsId),
            'source_comment_id'   => strval($intLastAbsId),
            'abstract_id'         => intval($intLastAbsId),
            'timestamp'           => 1429545601,
            'ext'                 => '',
        ),
    );
    $res = $db->insert($arrSql['table'], $arrSql['fields']);
    if (!$res) {
        echo 'Insert content error:' . $db->error . ' ' . $db->errno . "\n";
        return -3;
    }
    $intLastCtId = $db->insert_id;

    //更新新闻摘要与详细内容的关联
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
        return -4;
    }


    return 0;
}

main();
