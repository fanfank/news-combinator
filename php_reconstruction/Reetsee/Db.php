<?php
/*
 * @author xuruiqi
 * @date   20140908
 * @desc   A simple lib for DB manipulation
 */
class Reetsee_Db {
    protected $_arrCurrentConf   = array();
    protected $_arrDb            = array();
    protected $_objCurrentDb     = NULL;
    protected $_lastAffectedRows = 0;
    protected $_lastInsertId     = NULL;
    protected $_lastSql          = '';

    function __destruct() {
        foreach ($this->_arrDb as $mysqli) {
            $mysqli->close();
        }
    }

    /**
     * @author xuruiqi
     * @param
     *      string $table
     *      array  $values
     *      array  $arrExtra
     * @desc 数据库delete接口
     */
    public function delete($table, $conds, $arrExtra) {
        $sql = Reetsee_Db_Sql::getSqlDelete($table, $conds, $arrExtra);
        if (empty($sql)) {
            $this->log("Reetsee_Db_Sql::getSqlDelete Failed, table=[" . serialize($table) . "], conds=[" . serialize($conds) . "], arrExtra=[" . serialize($arrExtra) . "]");
            return FALSE;
        }
        return $this->query($sql);
    }

    public function getDb($strDb, $strCharset = 'utf8', $strHost = '127.0.0.1', $intPort = 3306, $strUser = 'root', $strPassword = '123abc') {
        //查询是否已经有连接
        if (isset($this->_arrDb[$strDb]) && $this->_arrDb[$strDb]->ping()) {
            return $this->_arrDb[$strDb];
        }

        $mysqli = new mysqli($strHost, $strUser, $strPassword, $strDb, $intPort);
        if ($mysqli->connect_errno) {
            return FALSE;
        }

        //设置字符集
        if (!$mysqli->set_charset($strCharset)) {
            $mysqli->close();
            return FALSE;
        } else {

        }

        $this->_arrDb[$strDb] = $mysqli;
        $this->_objCurrentDb = $this->_arrDb[$strDb];
        $this->_arrCurrentConf = array(
            'host'     => $strHost,
            'port'     => $intPort,
            'db_name'  => $strDb,    
            'charset'  => $strCharset,
            'user'     => '<forbidden>',
            'password' => '<forbidden>',
        );
        return $this->_arrDb[$strDb];
    }

    public function getLastId() {
        return $this->_lastInsertId;
    }

    public function initDb($strDb, $strCharset = 'utf8', $strHost = '127.0.0.1', $intPort = 3306, $strUser = 'root', $strPassword = '123abc') {
        $mysqli = new mysqli($strHost, $strUser, $strPassword, $strDb, $intPort);
        if ($mysqli->connect_errno) {
            return FALSE;
        }

        //设置字符集
        if (!$mysqli->set_charset($strCharset)) {
            $mysqli->close();
            return FALSE;
        } else {

        }

        $this->_arrDb[$strDb] = $mysqli;
        $this->_objCurrentDb = $this->_arrDb[$strDb];
        $this->_arrCurrentConf = array(
            'host'     => $strHost,
            'port'     => $intPort,
            'db_name'  => $strDb,    
            'charset'  => $strCharset,
            'user'     => '<forbidden>',
            'password' => '<forbidden>',
        );
        return TRUE;
    }

    /**
     * @author xuruiqi
     * @param
     *      array  $fields
     *      string $table
     *      array  $arrExtra
     * @desc 数据库insert接口
     */
    public function insert($fields, $table, $dup, $arrExtra) {
        $sql = Reetsee_Db_Sql::getSqlInsert($fields, $table, $dup, $arrExtra);
        if (empty($sql)) {
            $this->log("Reetsee_Db_Sql::getSqlInsert Failed, fields=[" . serialize($fields) . "], table=[" . serialize($table) . "], dup=[" . serialize($dup) . "], arrExtra=[" . serialize($arrExtra) . "]");
            return FALSE;
        }
        return $this->query($sql);
    }

    public function query($strSql, $mysqli = $this->_objCurrentDb, $resulttype = MYSQLI_ASSOC) {
        $strSql = strval($strSql);
        $this->_lastSql = $strSql;
        $mysqli_res = $mysqli->query($strSql);

        if (NULL === $mysqli_res || is_bool($mysqli_res)) {
            $arrOutput = (TRUE === $mysqli_res) ? TRUE : FALSE;
            if (!$arrOutput) {
                $this->log("sql execution failed. errno=" . $mysqli->errno . ", error=" . $mysqli->error . ", sql=$strSql, conf=[" . serialize($this->_arrCurrentConf) . "]");
            }
        } else {
            if (method_exists('mysqli_result', 'fetch_all')) {
                $arrOutput = $mysqli_res->fetch_all($resulttype);
            } else {
                for(;$row = $mysqli_res->fetch_array($resulttype);) {
                    $arrOutput[] = $row;
                }
            }
            $mysqli_res->free();
        }
        
        $this->_lastInsertId = $mysqli->insert_id;
        $this->_lastAffectedRows = $mysqli->affected_rows;
        return $arrOutput;
    }

    /**
     * @author xuruiqi
     * @param
     *      array  $fields 
     *      string $table
     *      array  $conds
     *      array  $arrExtra
     * @desc 数据库select接口
     */
    public function select($fields, $table, $conds, $arrExtra) {
        $sql = Reetsee_Db_Sql::getSqlSelect($fields, $table, $conds, $arrExtra);
        if (empty($sql)) {
            $this->log("Reetsee_Db_Sql::getSqlSelect Failed, fields=[" . serialize($fields) . "], table=[" . serialize($table) . "], conds=[" . serialize($conds) . "], arrExtra=[" . serialize($arrExtra) . "]");
            return FALSE;
        }
        return $this->query($sql);
    }

    /**
     * @author xuruiqi
     * @param
     *      array  $fields
     *      string $table
     *      array  $conds
     *      array  $arrExtra
     * @desc 数据库update接口
     */
    public function update($fields, $table, $conds, $arrExtra) {
        $sql = Reetsee_Db_Sql::getSqlUpdate($fields, $table, $conds, $arrExtra);
        if (empty($sql)) {
            $this->log("Reetsee_Db_Sql::getSqlUpdate Failed, fields=[" . serialize($fields) . "], table=[" . serialize($table) . "], conds=[" . serialize($conds) . "], arrExtra=[" . serialize($arrExtra) . "]");
            return FALSE;
        }
        return $this->query($sql);
    }
}
