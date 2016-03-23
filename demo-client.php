<?php
/**
 * 命令行输入 php demp-client.php 启动
 */
error_reporting(E_ALL);
//端口
$service_port = 19910;
//本地
$address = 'localhost';
//创建 TCP/IP socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket < 0) {
    echo "socket创建失败原因:" . socket_strerror(socket_last_error($socket)) . "\n";
}
$result = socket_connect($socket, $address, $service_port);
if ($result < 0) {
    echo "SOCKET连接失败原因: " . socket_strerror(socket_last_error($socket)) . "\n";
}
//发送命令
$out = '';
$in = 'hello world';
socket_write($socket, $in, strlen($in));
while ($out = socket_read($socket, 2048)) {
    echo $out;
}
socket_close($socket);
exit();
?>