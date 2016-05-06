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
for($i=1; $i<=100; $i++){
    $errno = $errmsg = '';
    $client = stream_socket_client('127.0.0.1:19910', $errno, $errmsg);
    if(!$client){
        continue;
    }
    $clientList[] = $client;
}
echo "创建成功\n";
while(1){
    $readList = $clientList;
    $writeList = array();
    stream_select($readList, $writeList, $err, 10, 0);
    foreach($readList as $id=>$client){
        fwrite($client, 'hello world');
        $data = '';
        while(feof($client) === false && $d = fgetc($client)){
            if($d === "\n"){
                break;
            }
            $data .= $d;
        }
        var_dump('客户端用户' . $id . '号收到消息: "' . $data . '"');
    }
}