<?php
/**
 * DEMO文件. 展示基于Telnet协议的数据传输
 * Created by lixuan868686@163.com
 * User: lane
 * Date: 16/4/16
 * Time: 下午10:05
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */

//引入FastWS
require_once 'FastWS/index.php';

//使用文本传输的Api类
$telnet = new \FastWS\Api\Telnet('0.0.0.0', '19910');

//启动的子进程数量. 通常为CPU核心数
$telnet->childProcessCount = 1;

//设置FastWS实例名称
$telnet->instanceName = 'FastWS-Telnet';

//设置回调函数 - 这是所有应用的业务代码入口
$telnet->callbackStartInstance = 'callbackStartInstance';
$telnet->callbackConnect = 'callbackConnect';
$telnet->callbackNewData = 'callbackNewData';
$telnet->callbackSendBufferEmpty = 'callbackSendBufferEmpty';
$telnet->callbackInstanceStop = 'callbackInstanceStop';
$telnet->callbackConnectClose = 'callbackConnectClose';

$telnet->callbackConnect = function($connect){
    foreach($connect->instance->clientList as $client){
        //上线提示就不用告诉自己了, 对吧!
        if($connect->id != $client->id){
            $client->send('新用户'.$connect->id.'已经上线了.');
        }
    }
};

//启动FastWS
\FastWS\runFastWS();


//以下为回调函数, 业务相关.
function callbackStartInstance($instance)
{
    echo '实例' . $instance->instanceName . '成功启动' . "\n";
}

function callbackConnect($connect)
{
    global $telnet;
    foreach ($telnet->clientList as $client) {
        $client->send('hi');
    }
    //信号定时器
//    \FastWS\Core\Timer::add(function($conn){
//        var_dump("hello\n");
//        $conn->send("hello world\n");
//    }, array($connect), 1, true);
    //事件定时器
//    \FastWS\Api\Telnet::$globalEvent->add(function(){var_dump("hello\n");}, array(), 1, \FastWS\Core\Event\EventInterface::EVENT_TYPE_TIMER);

    var_dump('收到新链接. UniqueId=' . $connect->id . "\n");
}

function callbackNewData($connect, $data)
{
    $connect->send('用户' . $connect->id . '说: ' . $data . "\n");
    var_dump('UniqueId=' . $connect->id . '说:' . $data . "\n");
    _broadcast($connect, $data);
}

function callbackSendBufferEmpty($connect)
{
    var_dump('用户' . $connect->id . "的待发送队列已经为空\n");
}

function callbackInstanceStop($instance)
{
    foreach ($instance->clientList as $client) {
        $client->send('服务即将停止.');
    }
}

function clientListClose($connect)
{
    var_dump('UniqueId=' . $connect->id . '断开了' . "\n");
}

function _broadcast($connect, $data)
{
    $userId = $connect->id;
    foreach ($connect->instance->clientList as $client) {
        if ($connect->id != $client->id) {
            $client->send('用户' . $userId . '说: ' . $data . "\n");
        }
    }
}

function callbackConnectClose($connect)
{
    var_dump($connect);
    $connect->send('88');
}