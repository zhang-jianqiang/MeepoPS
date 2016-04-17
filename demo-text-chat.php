<?php
/**
 * DEMO文件. 展示本文聊天
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
$telnet = new \FastWS\Api\Telnet('telnet', '0.0.0.0', '19910');

//启动的子进程数量. 通常为CPU核心数
$telnet->childProcessCount = 1;

//设置主进程名称
$telnet->instanceName = 'FastWS-Telnet';

//设置启动FastWS的用户和用户组
$telnet->user = 'lane';
$telnet->group = 'staff';

//设置回调函数 - 这是所有应用的业务代码入口
$telnet->callbackConnect = 'callbackConnect';
$telnet->callbackNewData = 'callbackNewData';
$telnet->callbackConnectClose = 'callbackConnectClose';

//启动FastWS
\FastWS\runFastWS();


//以下为回调函数, 业务相关.
function callbackConnect($connect){
    var_dump('收到新链接. UniqueId='.$connect->id."\n");
}

function callbackNewData($connect, $data){
//    $connect->send('用户'.$connect->id.'说: '.$data."\n");
    var_dump('UniqueId='.$connect->id.'说:'.$data."\n");
    _broadcast($connect, $data);
}

function callbackConnectClose($connect){
    var_dump('UniqueId='.$connect->id.'断开了'."\n");
}

function _broadcast($connect, $data){
    $userId = $connect->id;
    foreach($connect->instance->clientList as $client){
        if($connect->id != $client->id){
            $client->send('用户'.$userId.'说: '.$data."\n");
        }
    }
}
