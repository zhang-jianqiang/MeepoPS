<?php
/**
 * DEMO文件. 展示基于HTTP协议的WebServer
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
$webServer = new \MeepoPS\Api\Http('0.0.0.0', '19910');

//启动的子进程数量. 通常为CPU核心数
$webServer->childProcessCount = 1;

//设置MeepoPS实例名称
$webServer->instanceName = 'MeepoPS-Http';

//设置根目录
$webServer->setRoot('www.lanecn.com', __DIR__ . '/Test/Web');
//设置默认首页
$webServer->defaultIndex = array('index.html', 'index.php');

//启动MeepoPS
\MeepoPS\runMeepoPS();