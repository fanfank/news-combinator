<?php
function odd_check_byte($data) {
    static $bits = array(1, 2, 4, 8, 16, 32, 64, 128);

    $cnt = 0;
    for ($i = 0, $len = strlen($data); $i < $len; ++$i) {
        foreach ($bits as $bit) {
            $cnt += $bit & ord($data[$i]);
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

function read_packets(&$fp) {
    $arrPackets = array(
        'syn'  => '',
        'data' => array(),
        'fin'  => '',
    );

    $last_buf = "";
    $buf = "";

    $nr_data_packet = 0;

    //read SYN
    while (!feof($fp)) {
        $buf = fread($fp, 128);

        //get type
        $st = 0;
        $ed = 4;
        $type = substr($buf, $st, $ed - $st);
        if ("0000" !== $type) {
            echo "read for SYN type error\n";
            return false;
        }

        //get payload_len
        $st = 4;
        $ed = $st;
        for ($len = strlen($buf); '$' !== $buf[$ed] && $ed < $len; $ed++) {}
        $seperator = '$';
        if ($ed >= $len || $seperator !== $buf[$ed]) {
            echo "read for SYN payload length error\n";
            return false;
        }
        $payload_len = substr($buf, $st, $ed - $st);

        //get payload
        $st = $ed + 1;
        $ed = $st + intval($payload_len);
        $payload = substr($buf, $st, $ed - $st);

        //get odd_check_byte
        $st = $ed;
        $ed = $st + 1;
        $odd_check_byte = substr($buf, $st, $ed - $st);

        $packet = $type . $payload_len . $seperator . $payload;
        if (odd_check_byte($packet) !== $odd_check_byte) {
            echo "check for SYN odd check byte error\n";
            return false;
        }

        $nr_data_packet    = intval($payload);
        $arrPackets['syn'] = $payload;

        $st = $ed;
        $last_buf = substr($buf, $st);
        break;
    }

    //read for DATA
    $status = 0; // 0 => start reading a new packet
                 // 1 => continue reading packet's payload and odd check byte
    $type         = "";
    $seperator    = '$';
    $payload_len  = 0;
    $payload_read = 0;
    $payload      = "";
    while (!feof($fp) && $nr_data_packet > 0) {
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
                if ("0001" !== $type) {
                    echo "read DATA type error\n";
                    return false;
                }

                //read for payload_len
                $st = $ed;
                for (; $seperator !== $buf[$ed] && $ed < $buf_len; $ed++) {}
                if ($ed >= $buf_len || $seperator !== $buf[$ed]) {
                    $last_buf = strval(substr($buf, $st_old));
                    break;
                }
                $payload_len = substr($buf, $st, $ed - $st);

                $payload_read = 0;
                $payload      = "";
                $status       = 1;
            }

            //read payload
            $ed = $st + $payload_len - strlen($payload_read);
            $if ($ed - $st < 0) {
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
                echo "odd_check_bute not match\n";
                return false;
            }

            $arrPacket['data'][] = $payload;
            $st = $ed;
            $status = 0;
            $nr_data_packet++;
        } while (0 === $status)

        if ($nr_data_packet > 0) {
            $last_buf .= fread($fp, 128);
        }
    }

    if ($nr_data_packet > 0) {
        echo "receive data packet error, some are missed\n";
        return false;
    }

    //read for FIN
    $st = 0;
    $ed = 4;
    //TODO


}

function handle_packets($arrPackets, $bolCheckSynFin = false) {

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

        if (false === $arrRes) {
            echo "Error reading packets\n";
            die(-1);
        }

        $data = handle_packets($arrPackets, true);
        //echo fread($fp, 128);
        /*
        while (!feof($fp)) {
            echo fread($fp, 128);
        }
        */
    
        echo "done\n";
    }
}
main();
