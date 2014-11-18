<?php
/*
 * @author xuruiqi
 * @date   20141107
 * @desc   详细新闻内容相关action
 */
class entryAction {
    protected $HTML_DIR  = NULL;
    protected $strTplDst = NULL;
    protected $strErrDst = NULL;

    function __construct() {
        $this->HTML_DIR  = implode(DIRECTORY_SEPARATOR, array(MODULE_PATH, 'html'));
        $this->strTplDst = implode(DIRECTORY_SEPARATOR, array($this->HTML_DIR, 'entry.html'));
        $this->strErrDst = implode(DIRECTORY_SEPARATOR, array($this->HTML_DIR, 'reetsee_news_404.html'));
    }

    public function process() {

        $intCategory = intval($_GET['category']);

        $db = Reetsee_Db::initDb('reetsee_news', '127.0.0.1', 3306, 'root', '123abc', 'utf8');
        if (NULL === $db) {
            Reetsee_Log::error('get db error');
            return -1;
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
        //$HTML_DIR       = implode(DIRECTORY_SEPARATOR, array(MODULE_PATH, 'html'));
        $arrSql = array(
            'table'  => 'news_category',  
            'fields' => array(
                'id', 'title', 'source_names', 'day_time', 'preview_pic', 'abstract_ids',
            ),
            'conds' => array(
                'id=' => $intCategory,    
            ),
        );

        $res = $db->select($arrSql['table'], $arrSql['fields'], $arrSql['conds']);
        if (false === $res) {
            Reetsee_Log::error('Select abstract error:' . $db->error . ' ' . $db->errno);
            $this->display(array(), $strErrDst);
            //include implode(DIRECTORY_SEPARATOR, array($HTML_DIR, 'reetsee_news_404.html'));
            return -1;
        }

        $arrCategory = $res[0];

        $arrNews = array();
        if (!empty($arrCategory)) {
            //$arrAbstractIds = explode(',', $res[0]['abstract_ids']);
            $strAbstractIds = $arrCategory['abstract_ids'];
          //+---------------------+------------------+------+-----+-----------------------------+----------------+
          //| Field               | Type             | Null | Key | Default                     | Extra          |
          //+---------------------+------------------+------+-----+-----------------------------+----------------+
          //| id                  | int(11) unsigned | NO   | PRI | NULL                        | auto_increment |
          //| title               | varchar(128)     | NO   |     |                             |                |
          //| source_name         | varchar(32)      | NO   |     |                             |                |
          //| content             | text             | NO   |     | NULL                        |                |
          //| source_news_link    | varchar(1024)    | NO   |     | http://blog.reetsee.com/404 |                |
          //| source_comment_link | varchar(1024)    | NO   |     | http://blog.reetsee.com/404 |                |
          //| source_news_id      | varchar(64)      | NO   | UNI | NULL                        |                |
          //| source_comment_id   | varchar(64)      | NO   |     |                             |                |
          //| abstract_id         | int(11) unsigned | NO   | UNI | 0                           |                |
          //| timestamp           | int(11) unsigned | NO   |     | 0                           |                |
          //| ext                 | varchar(2048)    | YES  |     |                             |                |
          //+---------------------+------------------+------+-----+-----------------------------+----------------+
            $arrSql = array(
                'table'  => 'news_content',   
                'fields' => array(
                    'id', 'title', 'source_name', 'content', 'source_news_link', 'source_comment_link', 'source_news_id', 'source_comment_id', 'abstract_id', 'timestamp', 'ext',  
                ),
                'appends' => " WHERE `abstract_id` IN ($strAbstractIds)",
            );

            $res = $db->select($arrSql['table'], $arrSql['fields'], $arrSql['conds'], $arrSql['appends']);
            if (false === $res) {
                Reetsee_Log::error('Select abstract error:' . $db->error . ' ' . $db->errno);
                $this->display(array(), $strErrDst);
                //include implode(DIRECTORY_SEPARATOR, array($HTML_DIR, 'reetsee_news_404.html'));
                return -1;
            }

            $arrNews = $res;
        } 
        unset($res);

        //TODO 获取实时评论
        //$arrComments = $this->getComments($arrNews);

        $arrTpl = array(
            'category' => $arrCategory,
            'news'     => $arrNews,    
        );
        $this->display($arrTpl, $this->strTplDst);

        return 0;
    }

    public function display($arrTpl, $strDst) {
        include $strDst;
    }
}
