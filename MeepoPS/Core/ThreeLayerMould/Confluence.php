<?php
/**
 * 汇聚管理层
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

class Confluence extends MeepoPS{

    //所有的Business列表
    private $_businessList = array();
    //所有的Transfer列表
    private $_transferList = array();
    //新链接加入时, 等待权限校验的超时时间
    private $_waitVerifyTimeout = 10;
    //PING的时间间隔
    public $pingInterval = 1;
    //PING没有收到响应的限制次数, 超出限制将断开连接
    public $pingNoResponseLimit = 1;

    public function __construct($protocol, $host, $port, array $contextOptionList=array())
    {
        $this->callbackConnect = array($this, 'callbackConfluenceConnect');
        $this->callbackNewData = array($this, 'callbackConfluenceNewData');
        $this->callbackConnectClose = array($this, 'callbackConfluenceConnectClose');
        parent::__construct($protocol, $host, $port, $contextOptionList);
    }

    /**
     * 回调函数 - 收到新链接时
     * 新链接加入时, 先不做处理, 等待token验证通过后再处理
     * token的验证是收到token后校验, 因此会进入callbackConfluenceNewData方法中
     * 再次处加入一次性的定时器, 如果10秒后仍然未通过验证, 则断开链接。
     * @param $connect
     */
    public function callbackConfluenceConnect($connect){
        $connect->confluence['waiter_verify_timer_id'] = Timer::add(function ($connect){
            Log::write('Confluence: Wait for token authentication timeout', 'ERROR');
            $this->_close($connect);
        }, array($connect), 3, false);
    }

    /**
     * 回调函数 - 收到新消息时
     * 只接受新增Transfer、新增Business、PONG三种消息
     * @param $connect
     * @param $data
     */
    public function callbackConfluenceNewData($connect, $data){
        //token校验
        if($this->_verifyAuth($data['token']) !== true){
            Log::write('Confluence: New link token validation failed', 'ERROR');
            $this->_close($connect);
            return;
        }
        //新的Transfer加入
        switch($data['msg_type']){
            case MsgTypeConst::MSG_TYPE_PONG:
                $this->_pong($connect, $data);
                break;
            case MsgTypeConst::MSG_TYPE_ADD_TRANSFER:
                $this->_addTransfer($connect, $data);
                break;
            case MsgTypeConst::MSG_TYPE_ADD_BUSINESS:
                $this->_addBusiness($connect, $data);
                break;
            default:
                Log::write('Confluence: New link message type is not supported, meg_type=' . $data['msg_type'], 'ERROR');
                $this->_close($connect);
                return;
        }
        //删除等待校验超时的定时器
        Timer::delOne($connect->confluence['waiter_verify_timer_id']);
        //告知对方, 已经收到消息, 并且已经添加成功了
        $connect->send(array('msg_type'=>MsgTypeConst::MSG_TYPE_ADD_TRANSFER, 'msg_content'=>'OK'));
    }

    /**
     * 回调函数 - 断开链接时
     * @param $connect
     */
    public function callbackConfluenceConnectClose($connect){
        if(isset($this->_transferList[$connect->id])){
            unset($this->_transferList[$connect->id]);
            $this->_broadcastToBusiness();
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
        $this->_businessList[$connect->id] = array(
            'ip' => $data['msg_content']['ip'],
            'port' => $data['msg_content']['port'],
        );
        $this->_broadcastToBusiness($connect);
    }

    /**
     * 新增一个Transfer
     * @param $connect
     * @param $data
     * @return bool
     */
    private function _addTransfer($connect, $data){
        $this->_transferList[$connect->id] = array(
            'ip' => $data['msg_content']['ip'],
            'port' => $data['msg_content']['port'],
        );
        //设定PING的定时器
        $connect->confluence['ping_timer_id'] = Timer::add(function ($connect){
            $connect->confluence['ping_no_response_count'] = empty($connect->confluence['ping_no_response_count']) ? 0 : $connect->confluence['ping_no_response_count']++;
            $connect->send(array('msg_type'=>MsgTypeConst::MSG_TYPE_PING, 'msg_content'=>'PING'));
        }, array($connect), $this->pingInterval);
        //检测PING回复情况
        $connect->confluence['check_ping_timer_id'] = Timer::add(array($this, 'checkPingLimit'), array($connect), $this->pingInterval);
        $this->_broadcastToBusiness();
    }

    /**
     * 检测PING的回复情况
     * @param $connect
     */
    public function checkPingLimit($connect){
        $connect->confluence['ping_no_response_count']++;
        //超出无响应次数限制时断开连接
        if( ($connect->confluence['ping_no_response_count'] - 1) >= $this->pingNoResponseLimit){
            $conn = '';
            if(isset($this->_businessList[$connect->id])){
                $conn = $this->_businessList[$connect->id];
            }else if(isset($this->_transferList[$connect->id])){
                $conn = $this->_transferList[$connect->id];
            }
            Log::write('Confluence: PING no response beyond the limit, has been disconnected. connect=' . json_encode($conn), 'ERROR');
            $this->_close($connect);
        }
    }

    /**
     * 验证token
     * @param $token
     * @return bool
     */
    private function _verifyAuth($token){
        if(!empty($token)){
        }
        return true;
    }

    /**
     * 关闭连接
     * @param $connect
     */
    private function _close($connect){
        if(isset($connect->confluence['ping_timer_id'])){
            Timer::delOne($connect->confluence['ping_timer_id']);
        }
        if(isset($connect->confluence['check_ping_timer_id'])){
            Timer::delOne($connect->confluence['check_ping_timer_id']);
        }
        if(isset($connect->confluence['waiter_verify_timer_id'])){
            Timer::delOne($connect->confluence['waiter_verify_timer_id']);
        }
        $connect->close();
    }
    /**
     * 接收到消息PONG
     * @param $connect
     * @param $data string
     */
    private function _pong($connect, $data){
        if($data['msg_content'] === 'PONG'){
            $connect->confluence['ping_no_response_count']--;
        }
    }

    /**
     * 给Business发送消息
     * @param null $connect
     */
    private function _broadcastToBusiness($connect=null){

        $message = array();
        $message['msg_type'] = MsgTypeConst::MSG_TYPE_RESET_TRANSFER_LIST;
        $message['msg_content']['transfer_list'] = array_unique($this->_transferList);
        //新增Business时, 只给指定的Business发送
        if(!is_null($connect)){
            $connect->send($message);
            return;
        }
        //给所有的Business发送
        foreach($this->_businessList as $business){
            $business->send($message);
        }
    }
}