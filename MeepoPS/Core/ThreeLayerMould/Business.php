<?php
/**
 * 业务逻辑层
 * 集中管理Transfer和Business的在线/离线状态。提供离线踢出, 上线推送等功能。
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/6/29
 * Time: 下午3:20
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace MeepoPS\Core\ThreeLayerMould;

use MeepoPS\Core\MeepoPS;

class Business extends MeepoPS{

    public function __construct()
    {
        $this->callbackStartInstance = array($this, 'callbackBusinessStartInstance');
        $this->callbackConnect = array($this, 'callbackBusinessConnect');
        $this->callbackNewData = array($this, 'callbackBusinessNewData');
        $this->callbackConnectClose = array($this, 'callbackBusinessConnectClose');
        parent::__construct();
    }

    /**
     * 进程启动时, 监听端口, 提供给Business, 同时, 链接到Confluence
     * @param $instance
     */
    public function callbackBusinessStartInstance($instance){
        //向中心机(Confluence层)发送自己的地址和端口, 以便Business感知。
        $this->connectConfluence();
        //向中心机(Confluence层)发送自己的地址和端口, 以便Business感知。
        $this->connectTransfer();
    }

    //------------Transfer层自身的交互部分-------------

    /**
     * 向中心机(Confluence层)发送自己的地址和端口, 以便Business感知。
     */
    public function connectConfluence(){
        $this->_confluence = new TcpClient($this->confluenceProtocol, $this->confluenceIp, $this->confluencePort, true);
        //实例化一个空类
        $this->_confluence->instance = new \stdClass();
        $this->_confluence->instance->callbackNewData = array($this, 'callbackConfluenceNewData');
        $this->_confluence->confluence = array();
        $this->_confluence->connect();
        $this->_confluence->send(array('token'=>'', 'msg_type'=>MsgTypeConst::MSG_TYPE_ADD_TRANSFER, 'msg_content'=>array('ip'=>$this->innerIp, 'port'=>$this->innerPort)));
    }

    public function callbackTransferConnect(){

    }

    public function callbackTransferNewData($connect, $data){

    }

    public function callbackTransferConnectClose(){

    }

    //--------和Business层交互部分--------------

    public function callbackBusinessConnect(){

    }

    public function callbackBusinessNewData(){

    }

    public function callbackBusinessConnectClose(){

    }


    //------------和Confluence层交互部分-------------

    public function callbackConfluenceNewData($connect, $data){
        switch($data['msg_type']){
            case MsgTypeConst::MSG_TYPE_ADD_TRANSFER:
                $this->_addConfluenceReponse($connect, $data);
                break;
            case MsgTypeConst::MSG_TYPE_PING:
                $this->_receivePing($connect, $data);
                break;
        }
    }

    private function _addConfluenceReponse($connect, $data){
        //链接失败
        if($data['msg_content'] !== 'OK'){
            $this->_reConnectConfluence();
            return;
        }
        //链接成功
        //添加计时器, 如果一定时间内没有收到中心机发来的PING, 则断开本次链接并重新向中心机发起注册
        $this->_confluence->confluence['waiter_confluence_ping_timer_id'] = Timer::add(function(){
            $this->_confluence->confluence['confluence_no_ping_limit'] = !empty($this->_confluence->confluence['confluence_no_ping_limit']) ? $this->_confluence->confluence['confluence_no_ping_limit']++ : 1;
            if($this->_confluence->confluence['confluence_no_ping_limit'] >= $this->_confluenceNoPingLimit){
                //重连
                $this->_reConnectConfluence();
            }
        }, array(), $this->_confluenceWaitPingTimeout);
    }

    private function _receivePing($connect, $data){
        if($data['msg_content'] !== 'PING'){
            return;
        }
        if(!empty($this->_confluence->confluence['confluence_no_ping_limit'])){
            $this->_confluence->confluence['confluence_no_ping_limit']--;
            if($this->_confluence->confluence['confluence_no_ping_limit'] < 0){
                $this->_confluence->confluence['confluence_no_ping_limit'] = 0;
            }
        }else{
            $this->_confluence->confluence['confluence_no_ping_limit'] = 0;
        }
        $connect->send(array('token'=>'', 'msg_type'=>MsgTypeConst::MSG_TYPE_PONG, 'msg_content'=>'PONG'));
    }

    private function _closeConfluence(){
        $this->_confluence->close();
        Timer::delOne($this->_confluence->confluence['waiter_confluence_ping_timer_id']);
    }

    private function _reConnectConfluence(){
        $this->_closeConfluence();
        $this->connectConfluence();
    }
}