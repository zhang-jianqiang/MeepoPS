<?php
/**
 * Created by lixuan868686@163.com
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

class TransferAndBusinessService{
    //本Transfer的内部通讯的IP
    public $transferIp;
    //本Transfer的内部通讯的端口
    public $transferPort;

    //与本Transfer链接的所有Business的列表
    private $_businessList = array();
    //用户内部通讯的Transfer的MeepoPS对象, 监听端口, 和Business通信
    private $_transfer;

    /**
     * 监听一个端口, 用来做内部通讯(Business会链接这个端口)。
     */
    public function listenBusiness(){
        $this->_transfer = new MeepoPS(ThreeLayerMould::INNER_PROTOCOL, $this->transferIp, $this->transferPort);
        $this->_transfer->callbackConnect = array($this, 'callbackBusinessConnect');
        $this->_transfer->callbackNewData = array($this, 'callbackBusinessNewData');
        $this->_transfer->callbackConnectClose = array($this, 'callbackBusinessConnectClose');
        $this->_transfer->listen();
    }

    /**
     * 回调函数 - 收到新链接时
     * 新链接加入时, 先不做处理, 等待token验证通过后再处理
     * token的验证是收到token后校验, 因此会进入callbackConfluenceNewData方法中
     * 再此处加入一次性的定时器, 如果N秒后仍然未通过验证, 则断开链接。
     * @param $connect
     */
    public function callbackBusinessConnect($connect){
        $connect->business['waiter_verify_timer_id'] = Timer::add(function ($connect){
            Log::write('Transfer: Wait Business for token authentication timeout', 'ERROR');
            $this->_close($connect);
        }, array($connect), MEEPO_PS_THREE_LAYER_MOULD_SYS_WAIT_VERIFY_TIMEOUT, false);
    }

    /**
     * 回调函数 - 收到新消息时
     * 只接受新增Business、PONG两种消息
     * @param $connect
     * @param $data
     */
    public function callbackBusinessNewData($connect, $data){
        switch($data['msg_type']){
            case MsgTypeConst::MSG_TYPE_ADD_BUSINESS:
                //token校验
                if(!isset($data['token']) || Tool::verifyAuth($data['token']) !== true){
                    Log::write('Confluence: New link token validation failed', 'ERROR');
                    $this->_close($connect);
                    return;
                }
                if($this->_addBusiness($connect, $data)){
                    //删除等待校验超时的定时器
                    Timer::delOne($connect->business['waiter_verify_timer_id']);
                }
                break;
            case MsgTypeConst::MSG_TYPE_PONG:
                $this->_receivePongFromBusiness($connect, $data);
                break;
            case MsgTypeConst::MSG_TYPE_SEND_ALL:
                $this->_sendAll($data);
                break;
            case MsgTypeConst::MSG_TYPE_SEND_ONE:
                $this->_sendOne($data);
                break;
            default:
                Log::write('Transfer: Business message type is not supported, meg_type=' . $data['msg_type'], 'ERROR');
                $this->_close($connect);
                return;
        }
    }

    /**
     * 回调函数 - 断开链接时
     * @param $connect
     */
    public function callbackBusinessConnectClose($connect){
        if(isset($this->_businessList[$connect->id])){
            unset($this->_businessList[$connect->id]);
        }else{
            unset($this->_businessList[$connect->id]);
        }
    }

    /**
     * 新增一个Business
     * @param $connect
     * @param $data
     * @return bool
     */
    private function _addBusiness($connect, $data){
        $this->_businessList[$connect->id] = $connect;
        //初始化发送PING未收到PONG的次数
        $connect->business['ping_no_response_count'] = 0;
        //设定PING的定时器
        $connect->business['ping_timer_id'] = Timer::add(function ($connect){
            $connect->send(array('msg_type'=>MsgTypeConst::MSG_TYPE_PING, 'msg_content'=>'PING'));
        }, array($connect), MEEPO_PS_THREE_LAYER_MOULD_SYS_PING_INTERVAL);
        //检测PING回复情况
        $connect->business['check_ping_timer_id'] = Timer::add(array($this, 'checkPingLimit'), array($connect), MEEPO_PS_THREE_LAYER_MOULD_SYS_PING_INTERVAL);
        //告知对方, 已经收到消息, 并且已经添加成功了
        return $connect->send(array('msg_type'=>MsgTypeConst::MSG_TYPE_ADD_BUSINESS, 'msg_content'=>'OK', 'msg_attachment'=>array('ip' => $this->transferIp, 'port'=>$this->transferPort)));
    }

    /**
     * 接收到消息PONG
     * @param $connect
     * @param $data string
     */
    private function _receivePongFromBusiness($connect, $data){
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
        if( ($connect->business['ping_no_response_count'] - 1) >= MEEPO_PS_THREE_LAYER_MOULD_SYS_PING_NO_RESPONSE_LIMIT){
            $conn = '';
            if(isset($this->_businessList[$connect->id])){
                $conn = $this->_businessList[$connect->id];
            }
            Log::write('Transfer: PING Business no response beyond the limit, has been disconnected. connect=' . json_encode($conn), 'ERROR');
            $this->_close($connect);
        }
    }

    private function _close($connect){
        if(isset($connect->business['waiter_verify_timer_id'])){
            Timer::delOne($connect->business['waiter_verify_timer_id']);
        }
        if(isset($connect->business['ping_timer_id'])){
            Timer::delOne($connect->business['ping_timer_id']);
        }
        if(isset($connect->business['check_ping_timer_id'])){
            Timer::delOne($connect->business['check_ping_timer_id']);
        }
        $connect->close();
    }
    
    
    //----------------Transfer To Business----------
    
    public function sendToBusiness($connect, $data){
        $business = $this->_selectBusiness();
        if($business === false){
            return false;
        }
        $message = $this->_formatMessageToBusiness($connect, $data);
        $result = $business->send($message);
        return $result;
    }

    /**
     * 选择一个Business
     * @return bool|mixed
     */
    private function _selectBusiness(){
        if(empty($this->_businessList)){
            return false;
        }
        $businessKey = array_rand($this->_businessList);
        if($businessKey === false || !isset($this->_businessList[$businessKey])){
            return false;
        }
        return $this->_businessList[$businessKey];
    }

    /**
     * 发送给Business的消息格式
     */
    private function _formatMessageToBusiness(&$connect, &$data){
        $clientAddress = $connect->getClientAddress();
        return array(
            'msg_type' => MsgTypeConst::MSG_TYPE_APP_MSG,
            'msg_content' => $data,
            'transfer_ip' => $this->transferIp,
            'transfer_port' => $this->transferPort,
            'client_ip' => $clientAddress[0],
            'client_port' => $clientAddress[1],
            'client_connect_id' => $connect->id,
            'client_id' => Tool::encodeClientId($this->transferIp, $this->transferPort, $connect->id),
        );
    }

    private function _sendAll($data){
        foreach(Transfer::$clientList as $client){
            $clientId = Tool::encodeClientId($this->transferIp, $this->transferPort, $client->id);
            if($clientId !== $data['client_id']){
                $client->send($data['msg_content']);
            }
        }
    }

    private function _sendOne($data){
        if(!isset(Transfer::$clientList[$data['to_client_connect_id']])){
            return;
        }
        $clientConnect = Transfer::$clientList[$data['to_client_connect_id']];
        $clientConnect->send($data['msg_content']);
    }
}