<?php
/*
 * @author xuruiqi
 * @date   20141106
 * @desc   默认action
 */
class indexAction {
    const MAX_RANGE = 50;
    public function process() {
        $intRange = @intval($_GET['range']);
        if (empty($intRange) || $intRange < 1 || self::MAX_RANGE < $intRange) {
            $intRange = 3;
        }
        
        $db = Reetsee_Db::initDb('reetsee_news', '127.0.0.1', 3306, 'root', '123abc', 'utf8');
        if (NULL === $db) {
            echo "get db error\n";
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
        $HTML_DIR       = implode(DIRECTORY_SEPARATOR, array(MODULE_PATH, 'html'));
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
            'appends' => " ORDER BY `day_time` DESC",
        );
        
        $res = $db->select($arrSql['table'], $arrSql['fields'], $arrSql['conds'], $arrSql['appends']);
        if (false === $res) {
            Reetsee_Log::error('Select abstract error:' . $db->error . ' ' . $db->errno);
            include implode(DIRECTORY_SEPARATOR, array($HTML_DIR, 'reetsee_news_404.html'));
            return -1;
        }
        
        $data = array();
        foreach ($res as $entry) {
            $entry['arr_uni_sources'] = array_unique(explode(',', $entry['source_names']));
            $data[$entry['day_time']][] = $entry;
        }
        foreach ($data as $day_time => &$news_entries) {
            usort($news_entries, function ($a, $b) {
                $bolRtsInA = in_array('reetsee', $a['arr_uni_sources']);
                $bolRtsInB = in_array('reetsee', $b['arr_uni_sources']);
                if ($bolRtsInA && !$bolRtsInB) {
                    return false;
                } else if (!$bolRtsInA && $bolRtsInB) {
                    return true;
                }
                return count($a['arr_uni_sources']) < count($b['arr_uni_sources']);
            });
        }
        unset($news_entries);
        include implode(DIRECTORY_SEPARATOR, array($HTML_DIR, 'index.html'));
        return 0;
    }
}
