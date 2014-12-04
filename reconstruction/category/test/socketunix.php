<?php

$socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
$path = "./unixsocket.socket";
if (!socket_connect($socket, $path)) {
    echo "error connecting\n";
    die("error");
}

$str = "testsocket";
socket_send($socket, $str, strlen($str), MSG_EOF);
echo "msg sent\n";

$byte = 0;
while (($byte = socket_recv($socket, $str, 128, 0)) > 0) {
    echo "msg received\n";
    echo $str . "\n";
}

socket_close($socket);

