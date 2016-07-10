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

use MeepoPS\Core\Log;
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
    //新Business链接加入时, 等待权限校验的超时时间
    public $waitVerifyTimeout;

    //confluence的IP
    public $confluenceIp;
    //confluence的端口
    public $confluencePort;
    //confluence的协议
    public $confluenceProtocol;
    //confluencePING的时间间隔
    public $confluencePingInterval;
    //confluence多久没有发来PING,没有收到响应的限制次数, 超出限制将断开连接
    public $confluencePingNoResponseLimit;



    //本类只操作API,不操作$this。因为本类并没有继承MeepoPS
    private $_apiClass;
    //MeepoPS对象, 监听内部端口, 等待Business的链接。
    private $_transfer;
    //Confluence的TCP链接
    private $_confluence;
    //所有Business的链接
    private $_businessList = array();

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
        $this->_connectConfluence();
    }

    /**
     * 监听一个端口, 用来做内部通讯(Business会链接这个端口)。
     */
    private function _listenBusiness(){
        $this->_transfer = new MeepoPS($this->innerProtocol, $this->innerIp, $this->innerPort);
        $this->_transfer->callbackConnect = array($this, 'callbackBusinessConnect');
        $this->_transfer->callbackNewData = array($this, 'callbackBusinessNewData');
        $this->_transfer->listen();
    }

    /**
     * 向中心机(Confluence层)发送自己的地址和端口, 以便Business感知。
     */
    private function _connectConfluence(){
        $this->_confluence = new TcpClient($this->confluenceProtocol, $this->confluenceIp, $this->confluencePort, true);
        //实例化一个空类
        $this->_confluence->instance = new \stdClass();
        $this->_confluence->instance->callbackNewData = array($this, 'callbackConfluenceNewData');
        $this->_confluence->confluence = array();
        $this->_confluence->connect();
        $this->_confluence->send(array('token'=>'', 'msg_type'=>MsgTypeConst::MSG_TYPE_ADD_TRANSFER, 'msg_content'=>array('ip'=>$this->innerIp, 'port'=>$this->innerPort)));
    }

    public function callbackTransferConnect(){}
    public function callbackTransferNewData($connect, $data){}
    public function callbackTransferConnectClose($connect){}

    //--------和Business层交互部分--------------

    public function callbackBusinessConnect($connect){
        $connect->business['waiter_verify_timer_id'] = Timer::add(function ($connect){
            Log::write('Transfer: Wait for token authentication timeout', 'ERROR');
            $this->_closeBusiness($connect);
        }, array($connect), $this->waitVerifyTimeout, false);
    }

    public function callbackBusinessNewData($connect, $data){
        //token校验
        if(!isset($data['token']) || Tool::verifyAuth($data['token']) !== true){
            Log::write('Confluence: New link token validation failed', 'ERROR');
            $this->_closeBusiness($connect);
            return;
        }
        switch($data['msg_type']){
            case MsgTypeConst::MSG_TYPE_ADD_BUSINESS:
                if($this->_receiveBusiness($connect, $data)){
                    //删除等待校验超时的定时器
                    Timer::delOne($connect->business['waiter_verify_timer_id']);
                }
                break;
            case MsgTypeConst::MSG_TYPE_PONG:
                $this->_receivePongBusiness($connect, $data);
                break;
        }
    }

    private function _receiveBusiness($connect, $data){
        var_dump($data);
        $this->_businessList[$connect->id] = $connect;
        //初始化发送PING未收到PONG的次数
        $connect->business['ping_no_response_count'] = 0;
        //设定PING的定时器
        $connect->business['ping_timer_id'] = Timer::add(function ($connect){
            $connect->send(array('msg_type'=>MsgTypeConst::MSG_TYPE_PING, 'msg_content'=>'PING'));
        }, array($connect), $this->confluencePingInterval);
        //检测PING回复情况
        $connect->business['check_ping_timer_id'] = Timer::add(array($this, 'checkPingLimit'), array($connect), $this->confluencePingInterval);
        //告知对方, 已经收到消息, 并且已经添加成功了
        return $connect->send(array('msg_type'=>MsgTypeConst::MSG_TYPE_ADD_BUSINESS, 'msg_content'=>'OK'));
    }

    private function _receivePongBusiness($connect, $data){
        if($data['msg_content'] === 'PONG'){
            $connect->business['ping_no_response_count']--;
        }
    }

    /**
     * 检测PING的回复情况
     * @param $connect
     */
    public function checkPingLimit($connect){
        $connect->business['ping_no_response_count']++;
        //超出无响应次数限制时断开连接
        if( ($connect->business['ping_no_response_count'] - 1) >= $this->confluencePingNoResponseLimit){
            $conn = '';
            if(isset($this->_businessList[$connect->id])){
                $conn = $this->_businessList[$connect->id];
            }
            Log::write('Transfer: PING no response beyond the limit, has been disconnected. connect=' . json_encode($conn), 'ERROR');
            $this->_closeBusiness($connect);
        }
    }

    private function _closeBusiness($connect){
        if(isset($connect->business['waiter_verify_timer_id'])){
            Timer::delOne($connect->business['waiter_verify_timer_id']);
        }
        $connect->close();
    }

    //------------和Confluence层交互部分-------------

    public function callbackConfluenceNewData($connect, $data){
        switch($data['msg_type']){
            case MsgTypeConst::MSG_TYPE_ADD_TRANSFER:
                $this->_addConfluenceReponse($connect, $data);
                break;
            case MsgTypeConst::MSG_TYPE_PING:
                $this->_receivePingToConfluence($connect, $data);
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
        $this->_confluence->confluence['confluence_no_ping_limit'] = 0;
        //添加计时器, 如果一定时间内没有收到中心机发来的PING, 则断开本次链接并重新向中心机发起注册
        $this->_confluence->confluence['waiter_confluence_ping_timer_id'] = Timer::add(function(){
            if((++$this->_confluence->confluence['confluence_no_ping_limit']) >= $this->confluencePingNoResponseLimit){
                //重连
                $this->_reConnectConfluence();
            }
        }, array(), $this->confluencePingInterval);
    }

    private function _receivePingToConfluence($connect, $data){
        if($data['msg_content'] !== 'PING'){
            return;
        }
        if($this->_confluence->confluence['confluence_no_ping_limit'] >= 1){
            $this->_confluence->confluence['confluence_no_ping_limit']--;
        }
        $connect->send(array('token'=>'', 'msg_type'=>MsgTypeConst::MSG_TYPE_PONG, 'msg_content'=>'PONG'));
    }

    private function _closeConfluence(){
        $this->_confluence->close();
        Timer::delOne($this->_confluence->confluence['waiter_confluence_ping_timer_id']);
    }

    private function _reConnectConfluence(){
        $this->_closeConfluence();
        $this->_connectConfluence();
    }
}