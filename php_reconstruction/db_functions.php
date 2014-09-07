<?php
class Reetsee_Db {
    protected $_arrDb = array();
    protected $_objCurrentDb = NULL;
    protected $_lastInsertId = NULL;
    protected $_lastAffectedRows = 0;

    function __destruct() {
        foreach ($this->_arrDb as $mysqli) {
            $mysqli->close();
        }
    }

    protected function _fetch_all($mysqli_res, $result_type = MYSQLI_NUM) {
        $arrOutput = array();
        if (method_exists('mysqli_result', 'fetch_all')) {
            $arrOutput = $mysqli_res->fetch_all($result_type);
        } else {
            for(;$row = $mysqli_res->fetch_array($result_type);) {
                $arrOutput[] = $row;
            }
        }
        return $arrOutput;
    }

    public function getDb($strDb, $strCharset = 'utf8', $strHost = '127.0.0.1', $intPort = 3600, $strUser = 'root', $strPassword = '123abc') {
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
        return $this->_arrDb[$strDb];
    }

    public function getLastId() {
        return $this->_lastInsertId;
    }

    public function initDb($strDb, $strCharset = 'utf8', $strHost = '127.0.0.1', $intPort = 3600, $strUser = 'root', $strPassword = '123abc') {
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
        return TRUE;
    }

    public function query($strSql, $mysqli = $this->_objCurrentDb) {
        $mysqli_res = $mysqli->query($strSql);
        $arrOutput = $this->_fetch_all($mysqli_res, MYSQLI_NUM);
        $this->_lastInsertId = $mysqli->insert_id;
        $this->_lastAffectedRows = $mysqli->affected_rows;
        return $arrOutput;
    }

}
