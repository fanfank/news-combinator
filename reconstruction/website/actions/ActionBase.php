<?php
/*
 * @author xuruiqi
 * @date   20141115
 * @desc   action基类
 */
class Actions_ActionBase {
    protected static function _retJson($arrData) {
        if (is_array($arrData)) {
            $data = json_encode($arrData);
        }

        header('Content-Type:text/json;charset=utf-8');
        echo $data;
    }
}
