<?php
/**
 * TCP链接
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/3/27
 * Time: 上午1:13
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Core\Connect;

use FastWS\Core\Event\EventInterface;
use FastWS\Core\FastWS;
use FastWS\Core\Log;

class Tcp extends ConnectInterface
{
    //读取buffer的最大空间
    const READ_BUFFER_SIZE = 65535;
    //状态 - 链接中
    const CONNECT_STATUS_CONNECTING = 1;
    //状态 - 链接已经建立
    const CONNECT_STATUS_ESTABLISH = 2;
    //状态 - 链接关闭中
    const CONNECT_STATUS_CLOSING = 4;
    //状态 - 链接已经关闭
    const CONNECT_STATUS_CLOSED = 8;

    //应用层协议
    public $applicationProtocol;
    //属于哪个Worker
    public $worker;
    //链接ID
    public $id = 0;
    private $_id = 0;
    //待发送的缓冲区的最大容量
    public $maxSendBufferSize = 1048576;

    //记录
    private static $_recorderId = 1;
    //本次链接,是一个Socket资源
    private $_connect;
    //待发送的缓冲区
    private $_sendBuffer = '';
    //已接收到的数据
    private $_readDate = '';
    //当前包长
    private $_currentPackageSize = 0;
    //当前链接状态
    private $_currentStatus = self::CONNECT_STATUS_ESTABLISH;
    //客户端地址
    private $_clientAddress = '';
    //是否暂停
    private $_isPaused = false;


    public function __construct($socket, $clientAddress)
    {
        Log::write(__METHOD__, 'TEST');
        //更改统计信息
        self::$statistics['current_connect_count']++;
        self::$statistics['total_connect_count']++;
        $this->id = $this->_id = self::$_recorderId++;
        $this->_connect = $socket;
        stream_set_blocking($this->_connect, 0);
        FastWS::$globalEvent->add(array($this, 'read'), array(), $this->_connect, EventInterface::EVENT_TYPE_READ);
        $this->maxSendBufferSize = FASTWS_TCP_CONNECT_DEFAULT_MAX_SEND_BUFFER_SIZE;
        $this->_clientAddress = $clientAddress;
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        self::$statistics['current_connect_count']--;
    }

    /**
     * 发送数据
     */
    public function send()
    {

    }

    /**
     * 关闭客户端链接
     */
    public function close(){

    }

    /**
     * 获取客户端来访IP
     */
    public function getIp(){

    }

    /**
     * 获取客户端来访端口
     */
    public function getPort(){

    }

    /**
     * 读取数据
     * @param $connect resource 是一个Socket的资源
     * @param $isCheckEof bool 是否检测链接断开
     */
    public function read($connect, $isCheckEof=true){
        Log::write(__METHOD__, 'TEST');
        //是否读取到了数据
        $isAlreadyReaded = false;
        while(true){
            $buffer = fread($connect, self::READ_BUFFER_SIZE);
            if($buffer === false || $buffer === ''){
                break;
            }
            $isAlreadyReaded = true;
            $this->_readDate .= $buffer;
        }
        //检测连接是否关闭
        if($isAlreadyReaded===false && $isCheckEof){
            $this->_destroy();
            return;
        }
        //处理应用层协议
        if($this->applicationProtocol){
            $applicationProtocolClassName = ucfirst($this->applicationProtocol);
            //如果接收到的数据不为空,并且没有被暂停
            while($this->_readDate && $this->_isPaused === false){
                //如果当前的包已经有长度(不是第一次读取,每次完整包后会重置为0)
                if($this->_currentPackageSize){
                    if($this->_currentPackageSize > strlen($this->_readDate)){
                        break;
                    }
                //本包是第一次读取
                }else{
                    if(!class_exists($applicationProtocolClassName)) {
                        Log::write('Application protocol class: '.$applicationProtocolClassName.' not exists', 'FATAL');
                    }
                    $this->_currentPackageSize = $applicationProtocolClassName::input($this->_readDate, $this);
                    //如果数据包未完\超过配置的最大TCP链接所接收的数据量
                    if($this->_currentPackageSize === 0){
                        break;
                    //如果数据包在配置的最大TCP链接所接收的数据量之内,并且值>0
                    }else if($this->_currentPackageSize > 0 && $this->_currentPackageSize <= FASTWS_TCP_CONNECT_MAX_PACKAGE_SIZE){
                        if($this->_currentPackageSize > strlen($this->_readDate)) {
                            break;
                        }
                    //数据包长度不正确,销毁链接
                    }else{
                        Log::write('data package size incorrect. size='.$this->_currentPackageSize, 'WARNING');
                        $this->_destroy();
                        return;
                    }
                }
                //处理完整长度的数据包
                self::$statistics['total_request_count']++;
                if($this->_currentPackageSize == strlen($this->_readDate)){
                    $requestBuffer = $this->_readDate;
                    $this->_readDate = '';
                }else{
                    //从读取缓冲区中获取一个完整的包
                    $requestBuffer = substr($this->_readDate, 0, $this->_currentPackageSize);
                    //从读取缓冲区删除获取到的包
                    $this->_readDate = substr($this->_readDate, $this->_currentPackageSize);
                }
                $this->_currentPackageSize = 0;
                if($this->worker->callbackNewData){
                    try{
                        call_user_func_array($this->worker->callbackNewData, array($this, $applicationProtocolClassName::decode($requestBuffer, $this)));
                    }catch (\Exception $e){
                        echo $e;
                        exit(250);
                    }
                }
            }
        //只有没有设置应用层协议,执行下面的部分.
        }else{
            //如果读取到的数据是空,或者链接已经被暂停
            if($this->_readDate === '' || $this->_isPaused){
                return;
            }
            self::$statistics['total_request_count']++;
            //触发接收到新数据的回调函数
            if($this->worker->callbackNewData){
                try{
                    call_user_func_array($this->worker->callbackNewData, array($this, $this->_readDate));
                }catch (\Exception $e){
                    echo $e;
                    exit(250);
                }
            }
            $this->_readDate = '';
        }
    }

    /**
     * 销毁链接
     * @param $connect resource 需要销毁的链接
     */
    private function _destroy(){
        //如果当前状态是已经关闭的,则不处理
        if($this->_currentStatus === self::CONNECT_STATUS_CLOSED){
            return ;
        }
        //从事件中移除对链接的读写监听
        FastWS::$globalEvent->delOne($this->_connect, EventInterface::EVENT_TYPE_READ);
        FastWS::$globalEvent->delOne($this->_connect, EventInterface::EVENT_TYPE_WRITE);
        @fclose($this->_connect);
        //从Worker的客户端列表中移除
        unset($this->worker->clientList[$this->_id]);
        //变更状态为已经关闭
        $this->_currentStatus = self::CONNECT_STATUS_CLOSED;
        //执行链接断开时的回调函数
        if($this->worker->callbackConnectClose) {
            try {
                call_user_func($this->worker->callbackConnectClose, $this);
            } catch (\Exception $e) {
                echo $e;
                exit(250);
            }
        }
    }
}