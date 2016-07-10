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

class TransferAndBusinessService{

    public $transferIp;
    public $transferPort;

    public $businessList = array();
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
            Log::write('Transfer: Wait for token authentication timeout', 'ERROR');
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
            default:
                Log::write('Confluence: New link message type is not supported, meg_type=' . $data['msg_type'], 'ERROR');
                $this->_close($connect);
                return;
        }
    }

    /**
     * 回调函数 - 断开链接时
     * @param $connect
     */
    public function callbackBusinessConnectClose($connect){
        if(isset($this->businessList[$connect->id])){
            unset($this->businessList[$connect->id]);
        }else{
            unset($this->businessList[$connect->id]);
        }
    }

    /**
     * 新增一个Business
     * @param $connect
     * @param $data
     * @return bool
     */
    private function _addBusiness($connect, $data){
        $this->businessList[$connect->id] = $connect;
        //初始化发送PING未收到PONG的次数
        $connect->business['ping_no_response_count'] = 0;
        //设定PING的定时器
        $connect->business['ping_timer_id'] = Timer::add(function ($connect){
            $connect->send(array('msg_type'=>MsgTypeConst::MSG_TYPE_PING, 'msg_content'=>'PING'));
        }, array($connect), MEEPO_PS_THREE_LAYER_MOULD_SYS_PING_INTERVAL);
        //检测PING回复情况
        $connect->business['check_ping_timer_id'] = Timer::add(array($this, 'checkPingLimit'), array($connect), MEEPO_PS_THREE_LAYER_MOULD_SYS_PING_INTERVAL);
        //告知对方, 已经收到消息, 并且已经添加成功了
        return $connect->send(array('msg_type'=>MsgTypeConst::MSG_TYPE_ADD_BUSINESS, 'msg_content'=>'OK'));
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
            if(isset($this->businessList[$connect->id])){
                $conn = $this->businessList[$connect->id];
            }
            Log::write('Business: PING no response beyond the limit, has been disconnected. connect=' . json_encode($conn), 'ERROR');
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
}