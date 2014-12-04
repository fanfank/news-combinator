<?php

$fp = fsockopen("unix://./unixsocket.socket", -1, $errno, $errstr, 0.1);
if (!$fp) {
    echo "$errstr ($errno)\n";
} else {
    /*
    for ($i = 1; $i <= 10; $i++) {
        echo "Start $i\n";
        $str = "$i marked\n";
        fwrite($fp, $str, strlen($str));
        while (!feof($fp)) {
            echo fread($fp, 128);
        }
        echo "Finished $i\n";
    }
     */
    $str = "reetsee\0";
    fwrite($fp, $str, strlen($str));
    echo "written something\n";
    //echo fread($fp, 128);
    while (!feof($fp)) {
        echo fread($fp, 128);
    }

    fclose($fp);
    echo "done\n";
}

