<?php
/**
 * DEMO文件. 展示基于Telnet协议的数据传输
 * Created by Lane
 * User: lane
 * Date: 16/4/16
 * Time: 下午10:05
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */

ini_set('memory_limit', '4096M');

//引入MeepoPS
require_once 'MeepoPS/index.php';

//使用文本传输的Api类
$telnet = new \MeepoPS\Api\Telnet('0.0.0.0', '19910');

//启动的子进程数量. 通常为CPU核心数
$telnet->childProcessCount = 32;

//设置MeepoPS实例名称
$telnet->instanceName = 'MeepoPS-Telnet';

//设置回调函数 - 这是所有应用的业务代码入口
$telnet->callbackNewData = function($connect, $data){
    if($data === 'PING'){
        $connect->send('PONG');
    }
};

$telnet->callbackConnect = function($connect){
    var_dump($connect->id);
};
//启动MeepoPS
\MeepoPS\runMeepoPS();

//2016年 05月 28日 星期六 12:00:00 CST


