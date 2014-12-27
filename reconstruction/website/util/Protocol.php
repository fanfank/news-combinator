<?php
/*
 * @author xuruiqi
 * @date   20141227
 * @desc   自己写的简单协议. A simple self-written protocol
 */
class Util_Protocol {
    /**
     * @author xuruiqi
     * @param
     *      string $data
     * @return
     *      string $oc_byte //odd check result
     * @desc   返回一段字符串的奇校验结果. returns the odd check result of a string
     */
    public static function odd_check_byte($data) {
        static $bits = array(1, 2, 4, 8, 16, 32, 64, 128);

        $cnt = 0;
        for ($i = 0, $len = strlen($data); $i < $len; ++$i) {
            foreach ($bits as $bit) {
                if ($bit & ord($data[$i])) {
                    $cnt += 1;
                }
            }
        }

        $oc_byte = '';

        if ($cnt % 2) {
            $oc_byte = chr(48);
        } else {
            $oc_byte = chr(49);
        }

        return $oc_byte;
    }

    public static function pack_syn($num_packets) {
        $payload     = strval($num_packets);
        $type        = "0000";
        $payload_len = strlen($payload);
        $seperator   = '$';
    
        $packet = $type . $payload_len . $seperator . $payload;
        $packet .= self::odd_check_byte($packet);
    
        return $packet;
    }
    
    public static function pack_fin() {
        $payload     = "";
        $type        = "0001";
        $payload_len = strlen($payload);
        $seperator   = '$';
    
        $packet = $type . $payload_len . $seperator . $payload;
        $packet .= self::odd_check_byte($packet);
    
        return $packet;
    }
    
    /**
     * @author xuruiqi
     * @param
     *      string $data           //data to be packed
     *      int    $payload_mx_len //maximum length of a packet's payload
     * @return
     *      array $arrDataPackets :
     *          string 0 //the 1st data packet
     *          string 1
     *          ...
     *          string n //the n+1th data packet
     * @desc pack data into data packets
     */
    public static function pack_data($data, $payload_mx_len = 4096) {
        if (is_array($data)) {
            $data = serialize($data);
        }
    
        $arrDataPackets = array();
        for ($i = 0, $len = strlen($data); $i < $len; $i += $payload_mx_len) {
            $payload = substr($data, $i, $payload_mx_len);
            $type        = "0002";
            $payload_len = strlen($payload);
            $seperator   = '$';
    
            $packet = $type . $payload_len . $seperator . $payload;
            $packet .= self::odd_check_byte($packet);
    
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
     *              str n //payload for the n+1th packet
     * @desc read packets for a special type for a specific number
     */
    public static function get_packets(&$fp, $last_buf = "", $target_type = "0000", $nr_packets = 1) {
        $status       = 0;  //0 means finished reading the last packet
                            //1 means continue reading the packet's payload
        $payload_len  = 0;
        $payload_read = 0;
        $type         = "";
        $seperator    = '$';
        $payload      = "";
    
        while ($nr_packets > 0) {
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
                        if ($st_old >= strlen($buf)) {
                            $last_buf = "";
                        } else {
                            $last_buf = substr($buf, $st_old);
                        }
                        break;
                    }
                    $type = substr($buf, $st, $ed - $st);
                    if ($target_type !== $type) {
                        echo "read $target_type type error, get $type instead\n";
                        return false;
                    }
    
                    //read for payload_len
                    $st = $ed;
                    for (; $ed < $buf_len && $seperator !== $buf[$ed]; $ed++) {}
                    if ($ed >= $buf_len || $seperator !== $buf[$ed]) {
                        if ($st_old >= strlen($buf)) {
                            $last_buf = "";
                        } else {
                            $last_buf = substr($buf, $st_old);
                        }
                        break;
                    }
                    $payload_len = intval(substr($buf, $st, $ed - $st));
    
                    $payload_read = 0;
                    $payload      = "";
                    $status       = 1;
                    $st           = $ed + 1;
                }
    
                //read payload
                $ed = $st + $payload_len - $payload_read;
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
                if ($ed > $buf_len) {
                    $last_buf = "";
                    break;
                }
    
                $oc_byte = substr($buf, $st, $ed - $st);
                $packet = $type . $payload_len . $seperator . $payload;
                if ($oc_byte !== self::odd_check_byte($packet)) {
                    echo "oc_byte not match, packet is [$packet]\n";
                    echo "DEBUG $oc_byte vs " . self::odd_check_byte($packet) . "\n";
                    return false;
                }
    
                $arrPackets['data'][] = $payload;
                $st = $ed;
                $status = 0;
                if (0 >= --$nr_packets) {
                    $last_buf = substr($buf, $st);
                    break;
                }
            } while (0 === $status && $nr_packets > 0);
    
            if ($nr_packets > 0) {
                $last_buf .= fread($fp, 128);
            }
        }
    
        if ($nr_packets > 0) {
            //echo "nr_packet $nr_packets\n";
            //$arrPackets['last_buf'] = $last_buf;
            //echo print_r($arrPackets, true) . "\n";
            echo "receive data packet error, some are missed\n";
            return false;
        }
    
        $arrPackets['last_buf'] = $last_buf;
    
        return $arrPackets;
    }
    
    /**
     * @author xuruiqi
     * @param
     *      mixed &$fp //file descriptor
     * @return
     *      array $arrPackets :
     *          array syn :
     *              string 0 //num of data packets, in string
     *          array data :
     *              string 0 //payload of the 1st data packet
     *              string 1
     *              ...
     *              string n //payload of the n+1th data packet
     *          array fin : //empty array if fin packet doesn't have payload
     *
     * @desc read packets until fin is read
     */
    public static function read_packets(&$fp) {
        $arrPackets = array(
            'syn'  => array(),
            'data' => array(),
            'fin'  => array(),
        );
    
        //get SYN packets
        $arrRes = self::get_packets($fp, "", "0000", 1);
        if (false === $arrRes) {
            echo "get SYN packets error\n";
            return false;
        }
        foreach ($arrRes['data'] as $packet) {
            $arrPackets['syn'][] = $packet;
            //echo "SYN:$packet\n";
        }
    
        //get DATA packets
        $arrRes = self::get_packets($fp, $arrRes['last_buf'], "0002", intval($arrPackets['syn'][0]));
        if (false === $arrRes) {
            echo "get DATA packets error\n";
            return false;
        }
        foreach ($arrRes['data'] as $packet) {
            $arrPackets['data'][] = $packet;
            //echo "DATA:$packet\n";
        }
    
        //get FIN packets
        $arrRes = self::get_packets($fp, $arrRes['last_buf'], "0001", 1);
        if (false === $arrRes) {
            echo "get FIN packets error\n";
            return false;
        }
        foreach ($arrRes['data'] as $packet) {
            $arrPackets['fin'][] = $packet;
            //echo "FIN:$packet\n";
        }
    
        return $arrPackets;
    }
}
