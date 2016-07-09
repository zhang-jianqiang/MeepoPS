<?php
/**
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/7/9
 * Time: 下午6:19
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace MeepoPS\Core\ThreeLayerMould;

use MeepoPS\Api\ThreeLayerMould;
use MeepoPS\Core\Log;
use MeepoPS\Core\MeepoPS;
use MeepoPS\Core\Timer;
use MeepoPS\Library\TcpClient;

class BusinessAndTransferService{

    public $confluenceIp;
    public $confluencePort;

    private $_confluence;
    
    private $_transferList;

    /**
     * 向中心机(Confluence层)发送自己的地址和端口, 以便Business感知。
     */
    public function connectConfluence(){
        $this->_confluence = new TcpClient(ThreeLayerMould::INNER_PROTOCOL, $this->confluenceIp, $this->confluencePort, true);
        //实例化一个空类
        $this->_confluence->instance = new \stdClass();
        $this->_confluence->instance->callbackNewData = array($this, 'callbackConfluenceNewData');
        $this->_confluence->instance->callbackConnectClose = array($this, 'callbackConfluenceConnectClose');
        $this->_confluence->confluence = array();
        $this->_confluence->connect();
        $this->_confluence->send(array('token'=>'', 'msg_type'=>MsgTypeConst::MSG_TYPE_ADD_TRANSFER));
    }

    public function callbackConfluenceNewData($connect, $data){
        switch($data['msg_type']){
            case MsgTypeConst::MSG_TYPE_ADD_BUSINESS:
                $this->_addConfluenceResponse($connect, $data);
                break;
            case MsgTypeConst::MSG_TYPE_PING:
                $this->_receivePingFromConfluence($connect, $data);
                break;
            case MsgTypeConst::MSG_TYPE_RESET_TRANSFER_LIST:
                $this->_resetTransferList($connect, $data);
                break;
        }
    }

    private function _addConfluenceResponse($connect, $data){
        //链接失败
        if($data['msg_content'] !== 'OK'){
            $this->_closeConfluence();
            return;
        }
        //链接成功
        $this->_confluence->confluence['confluence_no_ping_limit'] = 0;
        //添加计时器, 如果一定时间内没有收到中心机发来的PING, 则断开本次链接并重新向中心机发起注册
        $this->_confluence->confluence['waiter_confluence_ping_timer_id'] = Timer::add(function(){
            if((++$this->_confluence->confluence['confluence_no_ping_limit']) >= MEEPO_PS_THREE_LAYER_MOULD_SYS_PING_NO_RESPONSE_LIMIT){
                //断开链接
                $this->_closeConfluence();
            }
        }, array(), MEEPO_PS_THREE_LAYER_MOULD_SYS_PING_INTERVAL);
    }

    private function _receivePingFromConfluence($connect, $data){
        if($data['msg_content'] !== 'PING'){
            return;
        }
        if($this->_confluence->confluence['confluence_no_ping_limit'] >= 1){
            $this->_confluence->confluence['confluence_no_ping_limit']--;
        }
        $connect->send(array('msg_type'=>MsgTypeConst::MSG_TYPE_PONG, 'msg_content'=>'PONG'));
    }

    private function _resetTransferList($connect, $data){
        if(empty($data['msg_content']['transfer_list'])){
            //todo =dai fanyi
            Log::write('Business: confluence ', 'ERROR');
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

    public function callbackConfluenceConnectClose(){
        $this->_reConnectConfluence();
    }

    private function _reConnectConfluence(){
        $this->_closeConfluence();
        $this->connectConfluence();
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
}