<?php
/*
 * 稳定性测试
 * 模拟Telnet，以死循环的方式连续发送hello world\n。并且等待回复。每个链接发送100次。
 */
$totalCount = 0;
$errConnect = 0;
$errWrite = 0;
$errRead = 0;
$f = fopen('/home/lane/fast_ws_test_stability_connect_err', 'w+');
while(true){
    $totalCount++;
    $socket = fsockopen('127.0.0.1', '19910', $errno, $errmsg);
    if(!$socket){
        fwrite($f, "error: connect. errno=$errno, errmsg=$errmsg\n");
        $errConnect++;
        continue;
    }
    for($i=0; $i<100; $i++){
        $result = fwrite($socket, "hello world\n");
        if(!$result){
            $errWrite++;
            continue;
        }
        $data = '';
        while(feof($socket)){
            $data .= fread($socket, 2000);
        }
        if(!$data || strlen($data)<10){
            $errRead++;
        }
    }
    fclose($socket);
    file_put_contents('/home/lane/fast_ws_test_stability_statistic', json_encode(array('total_count'=>$totalCount, 'err_connect'=>$errConnect, 'err_write'=>$errWrite, 'err_read'=>$errRead)));
}
fclose($f);
