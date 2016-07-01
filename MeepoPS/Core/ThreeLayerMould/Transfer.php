<?php
/**
 * 传输层
 * 只接受用户的链接, 不做任何的业务逻辑。
 * 接收用户发送的数据转发给Business层, 再将Business层返回的结果发送给用户
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/6/29
 * Time: 下午3:20
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace MeepoPS\Core\ThreeLayerMould;

use MeepoPS\Core\MeepoPS;
use MeepoPS\Core\Timer;
use MeepoPS\Library\TcpClient;

class Transfer {
    //和Business通讯的内部IP(Business链接到这个IP)
    public $innerIp = '0.0.0.0';
    //和Business通讯的内部端口(Business链接到这个端口)
    public $innerPort = 19912;
    //和Business通信的协议
    public $innerProtocol = 'telnetjson';

    //confluence的IP
    public $confluenceIp;
    //confluence的端口
    public $confluencePort;
    //confluence的协议
    public $confluenceProtocol = 'telnetjson';
    //PING的时间间隔
    private $_confluenceWaitPingTimeout = 1;
    //PING没有收到响应的限制次数, 超出限制将断开连接
    public $_confluenceNoPingLimit = 2;

    //本类只操作API,不操作$this。因为本类并没有继承MeepoPS
    private $_apiClass;
    //MeepoPS对象, 监听内部端口, 等待Business的链接。
    private $_transfer;
    //Confluence的TCP链接
    private $_confluence;

    public function __construct($apiName, $host, $port, array $contextOptionList=array())
    {
        $this->_apiClass = new $apiName($host, $port, $contextOptionList);
        $this->_apiClass->callbackStartInstance = array($this, 'callbackTransferStartInstance');
        $this->_apiClass->callbackConnect = array($this, 'callbackTransferConnect');
        $this->_apiClass->callbackNewData = array($this, 'callbackTransferNewData');
        $this->_apiClass->callbackConnectClose = array($this, 'callbackTransferConnectClose');
    }

    public function setApiClassProperty($name, $value){
        $this->_apiClass->$name = $value;
    }

    //------------Transfer层自身的交互部分-------------
    
    /**
     * 进程启动时, 监听端口, 提供给Business, 同时, 链接到Confluence
     * @param $instance
     */
    public function callbackTransferStartInstance($instance){
        //监听一个端口, 用来做内部通讯(Business会链接这个端口)。
        $this->_listenBusiness();
        //向中心机(Confluence层)发送自己的地址和端口, 以便Business感知。
        $this->connectConfluence();
    }

    /**
     * 监听一个端口, 用来做内部通讯(Business会链接这个端口)。
     */
    private function _listenBusiness(){
        $this->_transfer = new MeepoPS($this->innerProtocol, $this->innerIp, $this->innerPort);
        $this->_transfer->callbackConnect = array($this, 'callbackBusinessConnect');
        $this->_transfer->callbackNewData = array($this, 'callbackBusinessNewData');
        $this->_transfer->callbackConnectClose = array($this, 'callbackBusinessConnectClose');
        $this->_transfer->listen();
    }

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