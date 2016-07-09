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
$telnet->transferChildProcessCount = 1;

$telnet->businessChildProcessCount = 1;

//启动MeepoPS
\MeepoPS\runMeepoPS();