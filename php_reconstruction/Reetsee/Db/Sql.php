<?php
/*
 * @author xuruiqi
 * @date   20140908
 * @desc   A simple lib for sql assembling
 */
class Reetsee_Db_Sql {
    const SQL_PART_SEL       = 1;
    const SQL_PART_FROM      = 2;
    const SQL_PART_WHERE_AND = 3;
    const SQL_PART_UPDATE    = 4;
    const SQL_PART_VALUES    = 5; 
    const SQL_PART_DUP       = 6;
    const SQL_PART_INSERT    = 7;
    const SQL_PART_INSERT_VALUES = 8;

    public static function getSqlDelete($table, $conds, $arrExtra = NULL) {
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

    public static function getSqlInsert($fields, $table, $dup = NULL, $arrExtra = NULL) {
        $arrSql   = array();
        $arrSql[] = self::_getSqlPart($table , self::SQL_PART_INSERT);
        $arrSql[] = self::_getSqlPart($fields, self::SQL_PART_INSERT_VALUES);
        $arrSql[] = self::_getSqlPart($dup   , self::SQL_PART_DUP);
        foreach ($arrSql as $sql_part) {
            if (FALSE === $sql_part) {
                return FALSE;
            }
        }
        return implode(' ', $arrSql);
    }

    protected static function _getSqlPart($tuples, $tuples_type) {
        if (empty($tuples)) {
            return '';
        }

        $sql = '';
        switch ($tuples_type) {
            case self::SQL_PART_SEL: //SELECT 模板
                if (!is_array($tuples) || empty($tuples)) {
                    $sql = FALSE;
                    break;
                }

                $sql .= "SELECT ";
                $arrFields = array();
                foreach ($tuples as $field) {
                    $field = mysql_escape_string($field);
                    $arrFields[] = $field;
                }
                $sql .= implode(', ', $arrFields);
                break;

            case self::SQL_PART_FROM: //FROM 模板
                if (!is_string($tuples) || 0 === strlen($tuples)) {
                    $sql = FALSE;
                    break;
                }

                $tuples = mysql_escape_string($tuples);
                $sql .= "FROM `$tuples`";
                break;

            case self::SQL_PART_WHERE_AND: //WHERE AND 模板
                if (!is_array($tuples) || empty($tuples)) {
                    $sql = FALSE;
                    break;
                }

                $sql .= 'WHERE ';
                $arrAndConds = array();
                foreach ($tuples as $field => $value) {
                    $field = mysql_escape_string($field);
                    $value = mysql_escape_string($value);
                    $arrAndConds[] = "`$field`='$value'";
                }
                $sql .= implode(' AND ', $arrAndConds);
                break;

            case self::SQL_PART_UPDATE: //UPDATE 模板
                if (!is_string($tuples) || 0 === strlen($tuples)) {
                    $sql = FALSE;
                    break;
                }

                $tuples = mysql_escape_string($tuples);
                $sql .= "UPDATE `$tuples` SET";
                break;

            case self::SQL_PART_VALUES: //KEY=VALUE 模板
                if (!is_array($tuples) || empty($tuples)) {
                    $sql = FALSE;
                    break;
                }

                $sql .= "";
                $arrKeyValues = array();
                foreach ($tuples as $field => $value) {
                    $field = mysql_escape_string($field);
                    $value = mysql_escape_string($value);
                    $arrKeyValues[] = "`$field`='$value'";
                }
                $sql .= implode(', ', $arrKeyValues);
                break;

            case self::SQL_PART_DUP: //ON DUPLICATE 模板
                if (empty($tuples)) {
                    $sql = '';
                }
                $values_sql = self::_getSqlPart($tuples, self::SQL_PART_VALUES);
                if (FALSE === $values_sql) {
                    $sql = FALSE;
                    break;
                }

                $sql = "ON DUPLICATE KEY UPDATE $values_sql";
                break;

            case self::SQL_PART_INSERT: //INSERT 模板
                if (!is_string($tuples) || empty($tuples)) {
                    return FALSE;
                }
                $sql .= "INSERT INTO $tuples";
                break;

            case self::SQL_PART_INSERT_VALUES: //INSERT VALUES 模板
                if (!is_array($tuples) || empty($tuples)) {
                    return FALSE;
                }

                $sql .= "";
                $arrFields = array();
                $arrValues = array();
                foreach ($tuples as $field => $value) {
                    $field = mysql_escape_string($field);
                    $value = mysql_escape_string($value);
                    $arrFields[] = "`$field`";
                    $arrValues[] = "'$value'";
                }
                $sql .= '(' . implode(',', $arrFields) . ') VALUES (' . implode(',', $arrValues) . ')';

                break;

            default:
                $sql = FALSE;
                break;

        }
        return $sql;
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
