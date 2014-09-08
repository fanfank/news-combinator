<?php
class Reetsee_Sql {
    const SQL_PART_SEL       = 1;
    const SQL_PART_FROM      = 2;
    const SQL_PART_WHERE_AND = 3;
    const SQL_PART_UPDATE    = 4;
    const SQL_PART_SET       = 5;
    
    public static function getSqlDelete($table, $conds, $arrExtra) {
        $arrSql   = array();
        $arrSql[] = self::_getSqlPart($table, self::SQL_PART_DEL);
        $arrSql[] = self::_getSqlPart($conds, self::SQL_PART_WHERE_AND);
        foreach ($arrSql as $sql_part) {
            if (FALSE === $sql_part) {
                return FALSE;
            }
        }
        return implode(' ', $arrSql);
    }

    public static function getSqlInsert($fields, $table, $dup, $arrExtra) {
        $arrSql   = array();
        $arrSql[] = self::_getSqlPart($table , self::SQL_PART_INSERT);
        $arrSql[] = self::_getSqlPart($fields, self::SQL_PART_VALUES);
        $arrSql[] = self::_getSqlPart($dup   , self::SQL_PART_DUP);
        foreach ($arrSql as $sql_part) {
            if (FALSE === $sql_part) {
                return FALSE;
            }
        }
        return implode(' ', $arrSql);
    }

    public static function getSqlSelect($fields, $table, $conds, $arrExtra) {
        $arrSql   = array();
        $arrSql[] = self::_getSqlPart($fields, self::SQL_PART_SEL);
        $arrSql[] = self::_getSqlPart($table , self::SQL_PART_FROM);
        $arrSql[] = self::_getSqlPart($conds , self::SQL_PART_WHERE_AND);
        foreach ($arrSql as $sql_part) {
            if (FALSE === $sql_part) {
                return FALSE;
            }
        }
        return implode(' ', $arrSql);
    }

    public static function getSqlUpdate($fields, $table, $conds, $arrExtra) {
        $arrSql   = array();
        $arrSql[] = self::_getSqlPart($table , self::SQL_PART_UPDATE);
        $arrSql[] = self::_getSqlPart($fields, self::SQL_PART_VALUES);
        $arrSql[] = self::_getSqlPart($conds , self::SQL_PART_WHERE_AND);
        foreach ($arrSql as $sql_part) {
            if (FALSE === $sql_part) {
                return FALSE;
            }
        }
        return implode(' ', $arrSql);
    }
}
