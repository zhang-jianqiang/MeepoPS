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

    private $_transferList;
    private $_connectingTransferList;

    /**
     * 批量更新Transfer的链接
     * @param $data
     */
    public function resetTransferList($data){
        if(empty($data['msg_content']['transfer_list'])){
            Log::write('Business: Transfer sent by the Confluence is empty ', 'ERROR');
        }
        $transferList = $this->_transferList;
        $this->_transferList = array();
        foreach($data['msg_content']['transfer_list'] as $transfer){
            if(empty($transfer['ip']) || empty($transfer['port'])){
                continue;
            }
            //之前是否已经链接好了
            $transferKey = Tool::encodeTransferAddress($transfer['ip'], $transfer['port']);
            if(isset($transferList[$transferKey])){
                $this->_transferList[$transferKey] = array('ip' => $transfer['ip'], 'port' => $transfer['port']);
                continue;
            }
            //新链接到Transfer
            $result = $this->_connectTransfer($transfer['ip'], $transfer['port']);
            if($result === false){
                continue;
            }
            $this->_connectingTransferList[$transferKey] = array('ip' => $transfer['ip'], 'port' => $transfer['port']);
        }
    }

    /**
     * 作为客户端, 链接到Transfer
     *
     */
    private function _connectTransfer($ip, $port){
        $transfer = new TcpClient(ThreeLayerMould::INNER_PROTOCOL, $ip, $port, true);
        //实例化一个空类
        $transfer->instance = new \stdClass();
        $transfer->instance->callbackNewData = array($this, 'callbackTransferNewData');
        $transfer->instance->callbackConnectClose = array($this, 'callbackTransferConnectClose');
        $transfer->transfer = array();
        $transfer->connect();
        $result = $transfer->send(array('token'=>'', 'msg_type'=>MsgTypeConst::MSG_TYPE_ADD_BUSINESS));
        if($result === false){
            Log::write('Business: Link transfer failed.' . $ip . ':' . $port , 'WARNING');
            $this->_close($transfer);
        }
        return $result;
    }

    /**
     * 回调函数 - 收到Transfer发来新消息时
     * 只接受新增Business、PING两种消息
     * @param $connect
     * @param $data
     */
    public function callbackTransferNewData($connect, $data){
        switch($data['msg_type']){
            case MsgTypeConst::MSG_TYPE_ADD_BUSINESS:
                $this->_addTransferResponse($connect, $data);
                break;
            case MsgTypeConst::MSG_TYPE_PING:
                $this->_receiveTransferPing($connect, $data);
                break;
        }
    }

    private function _addTransferResponse($connect, $data){
        //链接失败
        if($data['msg_content'] !== 'OK' || empty($data['msg_attachment']['ip']) || empty($data['msg_attachment']['port'])){
            $this->_close($connect);
            return;
        }
        $transferKey = Tool::encodeTransferAddress($data['msg_attachment']['ip'], $data['msg_attachment']['port']);
        if(!isset($this->_connectingTransferList[$transferKey])){
            Log::write('Business: Rejected an unknown Transfer that would like to join.', 'WARNING');
            $this->_close($connect);
            return;
        }
        //链接成功
        $connect->business['transfer_no_ping_limit'] = 0;
        //添加计时器, 如果一定时间内没有收到Transfer发来的PING, 则断开本次链接并重新链接到Transfer
        $connect->business['waiter_transfer_ping_timer_id'] = Timer::add(function()use($connect){
            $connect->business['transfer_no_ping_limit']++;
            if( $connect->business['transfer_no_ping_limit'] >= MEEPO_PS_THREE_LAYER_MOULD_SYS_PING_NO_RESPONSE_LIMIT){
                //断开连接
                $this->_close($connect);
            }
        }, array(), MEEPO_PS_THREE_LAYER_MOULD_SYS_PING_INTERVAL);
        $this->_transferList[$transferKey] = array('ip' => $data['msg_attachment']['ip'], 'port' => $data['msg_attachment']['port']);
        Log::write('Business: link Transfer success. ' . $connect->host . ':' . $connect->port);
    }

    private function _receiveTransferPing($connect, $data){
        if($data['msg_content'] !== 'PING'){
            return;
        }
        if($connect->business['transfer_no_ping_limit'] >= 1){
            $connect->business['transfer_no_ping_limit']--;
        }
        $connect->send(array('msg_type'=>MsgTypeConst::MSG_TYPE_PONG, 'msg_content'=>'PONG'));
    }

    public function callbackTransferConnectClose($connect){
        $this->_reConnectTransfer($connect);
    }

    private function _reConnectTransfer($connect){
        $this->_close($connect);
        $this->_connectTransfer($connect->host, $connect->port);
    }

    private function _close($connect){
        if(isset($connect->business['waiter_transfer_ping_timer_id'])){
            Timer::delOne($connect->business['waiter_transfer_ping_timer_id']);
        }
        $connect->close();
    }
}