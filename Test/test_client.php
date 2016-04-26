<?php
/**
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/4/26
 * Time: 下午2:32
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */

$clientList = array();
for($i=0; $i<10; $i++){
    $client = stream_socket_client('127.0.0.1:19910', $errno, $errmsg);
    if(!$client){
        var_dump($errmsg);
    }
    stream_set_blocking($client, 1);
    $clientList[] = $client;
}
while(true){
    foreach($clientList as $client){
        //写
        fwrite($client, "hello world\n");
        //读
        $data = '';
        while(feof($client)){
            $data .= fread($client, 2000);
        }
        var_dump($data);
        sleep(1);
    }
}
