<?php
/**
 * 服务器端容量测试
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/4/26
 * Time: 下午2:32
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
$clientList = array();
for($i=1; $i<=5000; $i++){
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
    var_dump(count($clientList));
    sleep(10);
}