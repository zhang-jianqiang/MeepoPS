<?php
/**
 * 服务器端QPS测试
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/4/26
 * Time: 下午2:32
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */

$clientList = array();
$clientCount = 1000;
while(true){
    if(count($clientList) >= $clientCount){
        break;
    }
    $errno = $errmsg = '';
    $client = stream_socket_client('127.0.0.1:19910', $errno, $errmsg);
    if(!$client){
        var_dump($errno);
        var_dump($errmsg);
        continue;
    }
    $clientList[] = $client;
}
echo "创建成功\n";
while(1){
    foreach($clientList as $id=>$client){
        fwrite($client, "hello world\n");
        $data = '';
        while(feof($client) !== true){
            $data .= fread($client, 2000);
            if($data[strlen($data)-1] === "\n"){
                break;
            }
            if(!$data){
                echo "获取消息为空";
            }
        }
    }
}