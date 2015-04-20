<?php
/*
 * @author xuruiqi
 * @date   20141106
 * @desc   默认action
 */
class opinionsAction extends Actions_ActionBase {
    public function process() {
        $intTs = 1429545605 + rand(0, 999);
        $arrData = array(
            'errno'  => 0,
            'errmsg' => 'success',
            'data' => array(
                array(
                    'user_name' => 'Mr.Prince',
                    'time'      => 1429545601,
                    'content'   => "妹子，生日快乐",
                ),
                array(
                    'user_name' => '李厂长',
                    'time'      => $intTs++,
                    'content'   => "大家唱首生日歌呀～！",
                ),
                array(
                    'user_name' => 'Jack.马',
                    'time'      => $intTs++,
                    'content'   => "咳咳，准备好了",
                ),
                array(
                    'user_name' => '小马哥',
                    'time'      => $intTs++,
                    'content'   => "一、二、三，起！",
                ),
                array(
                    'user_name' => 'BMW',
                    'time'      => $intTs++,
                    'content'   => "祝你生日快乐～",
                ),
                array(
                    'user_name' => 'Benz',
                    'time'      => $intTs++,
                    'content'   => "祝你生日快乐～～",
                ),
                array(
                    'user_name' => 'Audi',
                    'time'      => $intTs++,
                    'content'   => "祝你生日快乐～～～",
                ),
                array(
                    'user_name' => 'BATNMGM3',
                    'time'      => $intTs++,
                    'content'   => "祝你生日快乐～～～！",
                ),
                array(
                    'user_name' => '东哥',
                    'time'      => $intTs++,
                    'content'   => "Happy Birthday to You~",
                ),
                array(
                    'user_name' => 'MilkTea妹妹',
                    'time'      => $intTs++,
                    'content'   => "Happy Birthday to You~~",
                ),
                array(
                    'user_name' => 'Luffy',
                    'time'      => $intTs++,
                    'content'   => "Happy Birthday to Yarin.Young~~~",
                ),
                array(
                    'user_name' => 'Nami',
                    'time'      => $intTs++,
                    'content'   => "Happy Birthday to You~~~!",
                ),
            ),
        );

        $this->_retJson($arrData);
        return 0;
    }
}
