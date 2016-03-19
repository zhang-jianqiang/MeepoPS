<?php
/**
 * 服务器程序Demo
 */
namespace Test;
class Test{

    public $fastWS = null;

    public function __construct(){
        $this->fastWS = require_once 'fastws.php';
        $this->fastWS->callFunc['new_connection'] = array($this, 'callFuncNewConnection');
        $this->fastWS->callFunc['read_data'] = array($this, 'callFuncReadData');
    }

    public function run(){
        $this->fastWS->run();
    }

    public function callFuncNewConnection($connect){
        $totalNum = count($this->fastWS->clientList);
        $msg = "第".$totalNum."位新用户你好:\n这里是FastWS测试服务器\n请输入'quit'退出本次回话.\n";
        $this->fastWS->socketWrite($connect, $msg);
    }

    public function callFuncReadData($connect, $data){
var_dump($connect);
var_dump($data);
        $msg = "服务器收到了你发送的信息: " . $data . "\n";
        $this->fastWS->socketWrite($connect, $msg);
    }
}

$obj = new Test();
$obj->run();
