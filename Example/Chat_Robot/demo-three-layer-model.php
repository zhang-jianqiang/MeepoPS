<?php
/**
 * DEMO文件. 展示基于Telnet协议的数据传输
 * producer - consumer
 * Created by Lane
 * User: lane
 * Date: 16/4/16
 * Time: 下午10:05
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */

//引入MeepoPS
require_once 'MeepoPS/index.php';

//使用文本传输的Api类
$telnet = new \MeepoPS\Api\ThreeLayerMould('telnet', '0.0.0.0', '19911');

$telnet->confluenceIp = '0.0.0.0';
$telnet->confluencePort = '19910';
$telnet->confluenceInnerIp = '127.0.0.1';

$telnet->transferInnerIp = '0.0.0.0';
$telnet->transferInnerPort = '19912';
$telnet->transferChildProcessCount = 2;

$telnet->businessChildProcessCount = 3;

$telnet->callbackNewData = function($connect, $data){
    $data = json_decode($data, true);
    if(empty($data['type'])){
        return;
    }
    $data['type'] = strtoupper($data['type']);
     switch($data['type']){
         case 'SEND_ALL':
             if(empty($data['content'])){
                 return;
             }
             $message = '收到群发消息: ' . $data['content'];
             \MeepoPS\Core\ThreeLayerMould\AppBusiness::sendToAll($message);
             break;
         case 'SEND_ONE':
             $message = '收到私聊消息: ' . $data['content'] . '(From: ' . $_SERVER['MEEPO_PS_CLIENT_ID'] . ')';
             $clientId = $data['send_to_one'];
             \MeepoPS\Core\ThreeLayerMould\AppBusiness::sendToOne($message, $clientId);
             break;
         default:
             return;
     }
};

//启动三层模型
$telnet->run();

//启动MeepoPS
\MeepoPS\runMeepoPS();