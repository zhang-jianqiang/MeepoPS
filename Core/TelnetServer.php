<?php
/**
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/3/23
 * Time: 下午2:12
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Core;

class TelnetServer extends FastWS{

    public function __construct($host, $contextOptionList=array())
    {
        if(!$host){
            return;
        }
        $hostTmp = explode(':', $host, 2);
        if(!$hostTmp[1]){
            return;
        }
        parent::__construct($host, $contextOptionList);
    }

    /**
     * 运行一个WebService实例
     */
    public function run(){
        $this->callbackConnect = array($this, 'callbackConnect');
        $this->callbackNewData = array($this, 'callbackNewData');
        $this->callbackConnectClose = array($this, 'callbackConnectClose');
        parent::run();
    }

    public function callbackConnect($connect){
        var_dump('收到新链接. UniqueId='.$connect->id);
    }

    public function callbackNewData($connect, $data){
        var_dump('UniqueId='.$connect->id.'说:'.$data);
        $this->_broadcast($connect, $data);
    }

    public function callbackConnectClose($connect){
        var_dump('UniqueId='.$connect->id.'断开了');
    }

    private function _broadcast($connect, $data){
        $userId = $connect->id;
        foreach($connect->worker->clientList as $client){
            $client->send('用户'.$userId.'说: '.$data);
        }
    }
}