<?php
function odd_check_byte($data) {
    static $bits = array(1, 2, 4, 8, 16, 32, 64, 128);

    $cnt = 0;
    for ($i = 0, $len = strlen($data); $i < $len; ++$i) {
        foreach ($bits as $bit) {
            if ($bit & ord($data[$i])) {
                $cnt += 1;
            }
        }
    }

    $odd_check_byte = '';

    if ($cnt % 2) {
        $odd_check_byte = chr(1);
    } else {
        $odd_check_byte = chr(0);
    }

    return $odd_check_byte;
}

function pack_syn($num_packets) {
    $payload     = strval($num_packets);
    $type        = "0000";
    $payload_len = strlen($payload);
    $seperator   = '$';

    $packet = $type . $payload_len . $seperator . $payload;
    $packet .= odd_check_byte($packet);

    return $packet;
}

function pack_fin() {
    $payload     = "";
    $type        = "0001";
    $payload_len = strlen($payload);
    $seperator   = '$';

    $packet = $type . $payload_len . $seperator . $payload;
    $packet .= odd_check_byte($packet);

    return $packet;
}

function pack_data($data, $payload_mx_len = 4096) {
    if (is_array($data)) {
        $data = serialize($data);
    }

    $arrDataPackets = array();
    for ($i = 0, $len = strlen($data); $i < $len; $i += $payload_mx_len) {
        $payload = substr($data, $i, $payload_mx_len);
        $type        = "0001";
        $payload_len = strlen($payload);
        $seperator   = '$';

        $packet = $type . $payload_len . $seperator . $payload;
        $packet .= odd_check_byte($packet);

        $arrDataPackets[] = $packet;
    }

    return $arrDataPackets;
}

/**
 * @author xuruiqi
 * @param
 *      handler &$fp
 *      str     $last_buf
 *      str     $target_type
 *      int     $nr_packets
 * @return
 *      array $arrPackets:
 *          str 'last_buf'
 *          arr 'data':
 *              str 0 //payload for the 1st packet
 *              str 1
 *              ...
 *              str n
 * @desc read packets for a special type for a specific number
 */
function get_packets(&$fp, $last_buf = "", $target_type = "0000", $nr_packets = 1) {
    $status       = 0;  //0 means finished reading the last packet
                        //1 means continue reading the packet's payload
    $type         = "";
    $seperator    = '$';
    $payload_len  = 0;
    $payload_read = 0;
    $payload      = "";

    while (!feof($fp) && $nr_packets > 0) {
        $buf     = $last_buf;
        $buf_len = strlen($buf);

        $st = 0;
        $ed = 0;
        do {
            if (0 === $status) {
                $st_old = $st;

                //read for type
                $ed = $st + 4;
                if ($ed > $buf_len) {
                    $last_buf = strval(substr($buf, $st_old));
                    break;
                }
                $type = substr($buf, $st, $ed - $st);
                if ($target_type !== $type) {
                    echo "read $target_type type error\n";
                    return false;
                }

                //read for payload_len
                $st = $ed;
                for (; $ed < $buf_len && $seperator !== $buf[$ed]; $ed++) {}
                if ($ed >= $buf_len || $seperator !== $buf[$ed]) {
                    $last_buf = strval(substr($buf, $st_old));
                    break;
                }
                $payload_len = substr($buf, $st, $ed - $st);

                $payload_read = 0;
                $payload      = "";
                $status       = 1;
                $st           = $ed + 1;
            }

            //read payload
            $ed = $st + $payload_len - strlen($payload_read);
            if ($ed - $st < 0) {
                echo "compute data ed error\n";
                return false;
            }

            if ($ed > $buf_len) {
                $ed = $buf_len;
            }

            $payload      .= substr($buf, $st, $ed - $st);
            $payload_read += $ed - $st;

            if ($payload_read < $payload_len) {
                $last_buf = "";
                break;
            }

            //read odd check byte
            $st = $ed;
            $ed = $st + 1;
            if ($ed >= $buf_len) {
                $last_buf = "";
                break;
            }

            $odd_check_byte = substr($buf, $st, $ed - $st);
            $packet = $type . $payload_len . $seperator . $payload;
            if ($odd_check_byte !== odd_byte_check($packet)) {
                echo "odd_check_byte not match\n";
                return false;
            }

            $arrPacket['data'][] = $payload;
            $st = $ed;
            $status = 0;
            $nr_packet--;
        } while (0 === $status)

        if ($nr_packet > 0) {
            $last_buf .= fread($fp, 128);
        }
    }

    if ($nr_packet > 0) {
        echo "receive data packet error, some are missed\n";
        return false;
    }

    $arrPackets['last_buf'] = $last_buf;

    return $arrPackets;
}

function read_packets(&$fp) {
    $arrPackets = array(
        'syn'  => array(),
        'data' => array(),
        'fin'  => array(),
    );

    //get SYN packets
    $arrRes = get_packets($fp, "", "0000", 1);
    if (false === $arrRes) {
        echo "get SYN packets error\n";
        return false;
    }
    foreach ($arrRes['data'] as $packet) {
        $arrPackets['syn'][] = $packet;
    }

    //get DATA packets
    $arrRes = get_packets($fp, $arrRes['last_buf'], "0002", intval($arrPackets['syn'][0]));
    if (false === $arrRes) {
        echo "get DATA packets error\n";
        return false;
    }
    foreach ($arrRes['data'] as $packet) {
        $arrPackets['data'][] = $packet;
    }

    //get FIN packets
    $arrRes = get_packets($fp, $arrRes['last_buf'], "0001", 1);
    if (false === $arrRes) {
        echo "get FIN packets error\n";
        return false;
    }
    foreach ($arrRes['data'] as $packet) {
        $arrPackets['fin'][] = $packet;
    }

    return $arrPackets;
}

function main() {
    $fp = fsockopen("unix://./unixsocket.socket", -1, $errno, $errstr, 0.1);
    if (!$fp) {
        echo "$errstr ($errno)\n";
    } else {
    
        $str = "我就是觉得现在互联网的限制太过无耻了！我擦，到底网络防火墙是谁搞出来的.为什么现在互联网什么都不给用？广电总局到底要封杀网络影音到什么时候。什么时候中国的网络环境能变好?我们要更开放的网络!";
        $arrDataPackets = pack_data($str);
        $syn_packet     = pack_syn(count($arrDataPackets));
        $fin_packet     = pack_fin();
        fwrite($fp, $syn_packet, strlen($syn_packet));
        foreach ($arrPackets as $data_packet) {
            fwrite($fp, $data_packet, strlen($data_packet));
        }
        fwrite($fp, $fin_Packet, strlen($fin_packet));
        echo "written something\n";
    
        $arrPackets = read_packets($fp);
        fclose($fp);

        if (false === $arrPackets) {
            echo "Error reading packets\n";
            die(-1);
        }

        echo implode($arrPackets['data']) . "\n";
        echo "done\n";
    }
}
main();
