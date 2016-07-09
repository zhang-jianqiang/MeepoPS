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

use MeepoPS\Core\Log;
use MeepoPS\Core\MeepoPS;
use MeepoPS\Core\Timer;
use MeepoPS\Library\TcpClient;

class Business extends MeepoPS{
    //confluence的IP
    public $confluenceIp;
    //confluence的端口
    public $confluencePort;
    //confluence的协议
    public $confluenceProtocol;

    //confluence多久没有发来PING,没有收到响应的限制次数, 超出限制将断开连接
    public $confluencePingNoResponseLimit;

    public $transferProtocol;

    //Transfer的TCP链接
    private $_transferList;
    //Confluence的TCP链接
    private $_confluence;

    public function __construct()
    {
        $this->callbackStartInstance = array($this, 'callbackBusinessStartInstance');
        parent::__construct();
    }

    //------------Business层自身的交互部分-------------

    /**
     * 进程启动时, 链接到Confluence
     * 作为客户端, 连接到中心机(Confluence层), 获取Transfer列表
     * @param $instance
     */
    public function callbackBusinessStartInstance($instance){
        //作为客户端, 连接到中心机(Confluence层), 获取Transfer列表
        $this->_connectConfluence();
    }

    /**
     * 向中心机(Confluence层)发送自己的地址和端口, 。
     */
    private function _connectConfluence(){
        $this->_confluence = new TcpClient($this->confluenceProtocol, $this->confluenceIp, $this->confluencePort, true);
        //实例化一个空类
        $this->_confluence->instance = new \stdClass();
        $this->_confluence->instance->callbackNewData = array($this, 'callbackConfluenceNewData');
        $this->_confluence->instance->callbackConnectClose = array($this, 'callbackConfluenceConnectClose');
        $this->_confluence->confluence = array();
        $this->_confluence->connect();
        $this->_confluence->send(array('token'=>'', 'msg_type'=>MsgTypeConst::MSG_TYPE_ADD_BUSINESS));
    }

    //------------和Confluence层交互部分-------------

    public function callbackConfluenceConnectClose($connect){
        $this->_reConnectConfluence();
    }

    public function callbackConfluenceNewData($connect, $data){
        switch($data['msg_type']){
            case MsgTypeConst::MSG_TYPE_ADD_BUSINESS:
                $this->_addConfluenceResponse($connect, $data);
                break;
            case MsgTypeConst::MSG_TYPE_PING:
                $this->_receivePing($connect, $data);
                break;
            case MsgTypeConst::MSG_TYPE_RESET_TRANSFER_LIST:
                $this->_resetTransferList($connect, $data);
                break;
        }
    }

    private function _addConfluenceResponse($connect, $data){
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
        }, array(), MEEPO_PS_THREE_LAYER_MOULD_SYS_PING_INTERVAL);
    }

    private function _receivePing($connect, $data){
        if($data['msg_content'] !== 'PING'){
            return;
        }
        if($this->_confluence->confluence['confluence_no_ping_limit'] >= 1){
            $this->_confluence->confluence['confluence_no_ping_limit']--;
        }
        $connect->send(array('token'=>'', 'msg_type'=>MsgTypeConst::MSG_TYPE_PONG, 'msg_content'=>'PONG'));
    }

    private function _resetTransferList($connect, $data){
        if(empty($data['msg_content']['transfer_list'])){
            return;
        }
        $transferList = $this->_transferList;
        $this->_transferList = array();
        foreach($data['msg_content']['transfer_list'] as $transfer){
            if(empty($transfer['ip']) || empty($transfer['port'])){
                continue;
            }
            $transferKey = $this->encodeTransferAddress($transfer['ip'], $transfer['port']);
            if(isset($transferList[$transferKey])){
                $this->_transferList[$transferKey] = array('ip' => $transfer['ip'], 'port' => $transfer['port']);
                continue;
            }
            //链接到Transfer
            $this->_connectTransfer($transfer['ip'], $transfer['port']);
        }
    }

    /**
     * 作为客户端, 链接到Transfer
     */
    private function _connectTransfer($ip, $port){
        $result = false;
        for($i=0; $i<1; $i++){
            $business = new TcpClient($this->transferProtocol, $ip, $port, true);
            //实例化一个空类
            $business->instance = new \stdClass();
            $business->instance->callbackNewData = array($this, 'callbackTransferNewData');
            $business->instance->callbackConnectClose = array($this, 'callbackTransferConnectClose');
            $business->transfer = array();
            $result = $business->send(array('token'=>'', 'msg_type'=>MsgTypeConst::MSG_TYPE_ADD_BUSINESS));
            $business->connect();
            if($result !== false){
                break;
            }
            $business->close();
        }
        if($result === false){
            Log::write('Business: Link transfer failed.' . $ip . ':' . $port . 'WARNING');
        }
    }

    private function _reConnectConfluence(){
        $this->_closeConfluence();
        $this->_connectConfluence();
    }

    private function _closeConfluence(){
        $this->_confluence->close();
        if(isset($this->_confluence->confluence['waiter_confluence_ping_timer_id'])){
            Timer::delOne($this->_confluence->confluence['waiter_confluence_ping_timer_id']);
        }
    }

    //--------和Transfer层交互部分--------------

    public function callbackTransferNewData($connect, $data){
        switch($data['msg_type']){
            case MsgTypeConst::MSG_TYPE_ADD_BUSINESS:
                $this->_addTransferResponse($connect, $data);
                break;
            case MsgTypeConst::MSG_TYPE_PING:
                $this->_receiveTransferPing($connect, $data);
                break;
        }
//        $this->_transferList[$transferKey] = array('ip' => $transfer['ip'], 'port' => $transfer['port']);
    }

    private function _addTransferResponse($connect, $data){
        //链接失败
        if($data['msg_content'] !== 'OK'){
            $this->_reConnectTransfer($connect);
            return;
        }
        //链接成功
        $connect->business['transfer_no_ping_limit'] = 0;
        //添加计时器, 如果一定时间内没有收到Transfer发来的PING, 则断开本次链接并重新链接到Transfer
        $connect->business['waiter_transfer_ping_timer_id'] = Timer::add(function()use($connect){
            if((++$connect['transfer_no_ping_limit']) >= $this->confluencePingNoResponseLimit){
                //重连
                $this->_reConnectTransfer($connect);
            }
        }, array(), MEEPO_PS_THREE_LAYER_MOULD_SYS_PING_INTERVAL);
        Log::write('Business: link transfer success. ' . $connect->host . ':' . $connect->port);
    }

    private function _receiveTransferPing($connect, $data){
        if($data['msg_content'] !== 'PING'){
            return;
        }
        if($this->_confluence->confluence['transfer_no_ping_limit'] >= 1){
            $this->_confluence->confluence['transfer_no_ping_limit']--;
        }
        $connect->send(array('token'=>'', 'msg_type'=>MsgTypeConst::MSG_TYPE_PONG, 'msg_content'=>'PONG'));
    }

    private function _reConnectTransfer($connect){
        $this->_closeTransfer($connect);
        $this->_connectTransfer($connect->host, $connect->port);
    }

    private function _closeTransfer($connect){
        $connect->close();
    }

    public function callbackTransferConnectClose($connect){

    }

    //-------其他方法--------

    public function encodeTransferAddress($ip, $port){
        return $ip . '_' . $port;
    }

    public function decodeTransferAddress($key){
        return explode('_', $key);
    }
}