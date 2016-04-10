<?php
/**
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/3/23
 * Time: 下午2:10
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
// Websocket
$webSocket = new \FastWS\Core\FastWS("Websocket://0.0.0.0:8082");
// Websocket数量
$webSocket->workerCount = 1;
// 设置站点根目录
$webSocket->name = 'FastWS-Websocket';

//当客户端连接上来时，设置连接的onWebSocketConnect，即在websocket握手时的回调
$webSocket->callbackConnect = 'callbackConnect';
$webSocket->callbackNewData = 'callbackNewData';
$webSocket->callbackConnectClose = 'callbackConnectClose';

function callbackConnect($connect){
    var_dump('收到新链接. UniqueId='.$connect->id);
}

function callbackNewData($connect, $data){
    var_dump('UniqueId='.$connect->id.'说:'.$data);
//    _broadcast($connect, $data);
}

function callbackConnectClose($connect){
    var_dump('UniqueId='.$connect->id.'断开了');
}

function _broadcast($connect, $data){
    $userId = $connect->id;
    foreach($connect->worker->clientList as $client){
        $client->send('用户'.$userId.'说: '.$data);
    }
}

// 如果不是在根目录启动，则运行runAll方法
//if(!defined('GLOBAL_START'))
//{
//    Worker::runAll();
//}
