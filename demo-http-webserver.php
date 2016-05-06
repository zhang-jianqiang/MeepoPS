<?php
/**
 * DEMO文件. 展示基于HTTP协议的WebServer
 * Created by lixuan868686@163.com
 * User: lane
 * Date: 16/4/16
 * Time: 下午10:05
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */

//引入FastWS
include_once 'FastWS/index.php';

//使用文本传输的Api类
$webServer = new \FastWS\Api\WebServer('http', '0.0.0.0', '19910');

//启动的子进程数量. 通常为CPU核心数
$webServer->childProcessCount = 1;

//设置FastWS实例名称
$webServer->instanceName = 'FastWS-Http';

//设置启动FastWS的用户和用户组
$webServer->user = 'lane';
$webServer->group = 'staff';

//设置主
$webServer->setRoot('www.lanecn.com', __DIR__.'/Test/Web');

//启动FastWS
\FastWS\runFastWS();