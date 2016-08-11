<?php
/**
 * DEMO文件. 展示基于Secure WebSocket协议的后端程序
 * Created by Lane
 * User: lane
 * Date: 16/8/11
 * Time: 下午15:39
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */

//引入MeepoPS
require_once 'MeepoPS/index.php';

//使用WebSocket协议传输的Api类
$webSocket = new \MeepoPS\Api\Websocket('0.0.0.0', '19910');

//启动的子进程数量. 通常为CPU核心数
$webSocket->childProcessCount = 1;

\MeepoPS\runMeepoPS();