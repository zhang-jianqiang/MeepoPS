<?php
/**
 * DEMO文件. 展示基于WebSocket协议的机器人聊天
 * producer - consumer
 * Created by Lane
 * User: lane
 * Date: 16/4/16
 * Time: 下午10:05
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */

//引入MeepoPS
require_once '../../MeepoPS/index.php';

//使用WebSocket协议传输的Api类
$webSocket = new \MeepoPS\Api\Websocket('0.0.0.0', '19910');
$webSocket->callbackNewData = 'callbackNewData';
//启动MeepoPS
\MeepoPS\runMeepoPS();

function callbackNewData($connect, $data){
    $msg = '收到消息: ' . $data;
    $message = array(
        'data' => $msg,
        'create_time' => date('Y-m-d H:i:s'),
    );
    $connect->send(json_encode($message));
}
