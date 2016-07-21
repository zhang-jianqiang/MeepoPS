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
$webSocket->callbackStartInstance = 'callbackStartInstance';
$webSocket->callbackNewData = 'callbackNewData';
$mysql1 = '';
$mysql2 = '';
//启动MeepoPS
\MeepoPS\runMeepoPS();

function callbackNewData($connect, $data){
    $msg = '收到消息: ' . $data;
    $connect->send($msg);
}
