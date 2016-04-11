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
    private $_isPauseRead = false;

    /**
     * 构造函数
     * Tcp constructor.
     * @param $socket
     * @param $clientAddress
     */
    public function __construct($socket, $clientAddress)
    {
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
     * 读取数据
     * @param $connect resource 是一个Socket的资源
     * @param $isCheckEof bool 如果fread()读取到的是空数据或者false的话,是否销毁链接.默认为true
     */
    public function read($connect, $isCheckEof=true){
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
            $this->destroy();
            return;
        }
        //处理应用层协议
        if($this->applicationProtocol){
            $applicationProtocolClassName = ucfirst($this->applicationProtocol);
            //如果接收到的数据不为空,并且没有被暂停
            while($this->_readDate && $this->_isPauseRead === false){
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
                    }else if($this->_currentPackageSize > 0 && $this->_currentPackageSize <= FASTWS_TCP_CONNECT_MAX_PACKET_SIZE){
                        if($this->_currentPackageSize > strlen($this->_readDate)) {
                            break;
                        }
                    //数据包长度不正确,销毁链接
                    }else{
                        Log::write('data packet size incorrect. size='.$this->_currentPackageSize, 'WARNING');
                        $this->destroy();
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
                        self::$statistics['exception_count']++;
                        Log::write('FastWS: execution callback function callbackNewData-'.$this->worker->callbackNewData . ' throw exception', 'FATAL');
                    }
                }
            }
        //只有没有设置应用层协议,执行下面的部分.
        }else{
            //如果读取到的数据是空,或者链接已经被暂停
            if($this->_readDate === '' || $this->_isPauseRead){
                return;
            }
            self::$statistics['total_request_count']++;
            //触发接收到新数据的回调函数
            if($this->worker->callbackNewData){
                try{
                    call_user_func_array($this->worker->callbackNewData, array($this, $this->_readDate));
                }catch (\Exception $e){
                    self::$statistics['exception_count']++;
                    Log::write('FastWS: execution callback function callbackNewData-'.$this->worker->callbackNewData . ' throw exception', 'FATAL');
                }
            }
            $this->_readDate = '';
        }
    }

    /**
     * 发送数据
     * @param $data string 待发送的数据
     * @param $isEncode bool 发送前是否根据应用层协议转码
     * @return int|bool 拒绝发送为0, 发送成功为发送成功的数据长度.加入待发送缓冲区延迟发送为-1 发送失败为false.
     */
    public function send($data, $isEncode=true){
        //如果需要根据协议转码,并且应用层协议类存在
        if($isEncode === true && $this->applicationProtocol && class_exists($this->applicationProtocol)){
            $applicationProtocolClassname = $this->applicationProtocol;
            $data = $applicationProtocolClassname::encode($data, $this);
            if(!$data){
                return 0;
            }
        }
        //如果状态是链接中.
        if($this->_currentStatus === self::CONNECT_STATUS_CONNECTING){
            $this->_sendBuffer .= $data;
            return 0;
        //如果状态是正在关闭或者和已经关闭
        }else if($this->_currentStatus === self::CONNECT_STATUS_CLOSING || $this->_currentStatus === self::CONNECT_STATUS_CLOSED){
            return 0;
        }
        //如果待发送的缓冲区为空,直接发送本次需要发送的数据
        if(empty($this->_sendBuffer)){
            self::$statistics['total_send_count']++;
            $length = @fwrite($this->_connect, $data);
            //全部发送成功
            if($length > 0 && $length === strlen($data)){
                return $length;
            //部分发送成功
            }else if($length > 0 && $length !== strlen($data)){
                $this->_sendBuffer = substr($data, $length);
            //发送失败
            }else{
                //socket资源无效
                if(!is_resource($this->_connect) || feof($this->_connect)){
                    Log::write('Send data failed. Possible socket resource has disabled', 'INFO');
                    self::$statistics['send_failed_count']++;
                    //触发错误的回调函数
                    if($this->worker->callbackError){
                        try{
                            call_user_func($this->worker->callbackError, $this, 'FASTWS_ERROR_CODE_SEND_SOCKET_INVALID', 'Send data failed. Possible socket resource has disabled');
                        }catch (\Exception $e){
                            self::$statistics['exception_count']++;
                            Log::write('FastWS: execution callback function callbackError-'.$this->worker->callbackError . ' throw exception', 'FATAL');
                        }
                    }
                    $this->destroy();
                    return false;
                //socket资源还有效,只是发送过程遭遇了失败,则将数据放入待发送缓冲区
                }else{
                    $this->_sendBuffer .= $data;
                }
            }
            //因为没有全部发送成功,则将发送事件加入到事件监听列表中
            FastWS::$globalEvent->add(array($this, 'write'), array(), $this->_connect, EventInterface::EVENT_TYPE_WRITE);
            //检测队列是否为空
            $this->_sendBufferIsFull();
            return -1;
        //如果待发送队列有值.
        }else{
            if(strlen($this->_sendBuffer) >= $this->maxSendBufferSize){
                Log::write('Send data failed. The send buffer is full. Data is discarded', 'WARNING');
                self::$statistics['send_failed_count']++;
                //触发错误的回调函数
                if($this->worker->callbackError){
                    try{
                        call_user_func($this->worker->callbackError, $this, FASTWS_ERROR_CODE_SEND_BUFFER_FULL, 'The send buffer is full. Data is discarded');
                    }catch (\Exception $e){
                        self::$statistics['exception_count']++;
                        Log::write('FastWS: execution callback function callbackError-'.$this->worker->callbackError . ' throw exception', 'FATAL');
                    }
                }
                return false;
            }
            $this->_sendBuffer .= $data;
            $this->_sendBufferIsFull();
            return -1;
        }
    }

    /**
     * 给链接中写入数据.为轮询事件用的
     * @return void
     */
    public function write()
    {
        //给socket资源中写入数据
        self::$statistics['total_send_count']++;
        $length = @fwrite($this->_connect, $this->_sendBuffer);
        //写入失败
        if(!is_int($length) || intval($length) <= 0){
            Log::write('Write data failed. Possible socket resource has disabled', 'WARNING');
            self::$statistics['send_failed_count']++;
            $this->destroy();
            return ;
        }
        //全部发送成功
        if($length === strlen($this->_sendBuffer)){
            //全部发送成功后不再轮训这个事件
            FastWS::$globalEvent->delOne($this->_connect, EventInterface::EVENT_TYPE_WRITE);
            $this->_sendBuffer = '';
            //出发待发送缓冲区为空的队列
            if($this->worker->callbackSendBufferEmpty){
                try{
                    call_user_func($this->worker->callbackSendBufferEmpty, $this);
                }catch (\Exception $e){
                    self::$statistics['exception_count']++;
                    Log::write('FastWS: execution callback function callbackSendBufferEmpty-'.$this->worker->callbackSendBufferEmpty . ' throw exception', 'FATAL');
                }
            }
            //如果是正在关闭中的状态(平滑断开链接会发送完待发送缓冲区的所有数据后再销毁资源)
            if($this->_currentStatus === self::CONNECT_STATUS_CLOSING){
                $this->destroy();
            }
        }else{
            $this->_sendBuffer = substr($this->_sendBuffer, $length);
        }
    }

    /**
     * 关闭客户端链接
     * @param string $data 关闭前需要发送的数据
     */
    public function close($data=null){
        if($this->_currentStatus === self::CONNECT_STATUS_CLOSING || $this->_currentStatus === self::CONNECT_STATUS_CLOSED){
            return;
        }else{
            if(!is_null($data)){
                $this->send($data);
            }
            $this->_currentStatus = self::CONNECT_STATUS_CLOSING;
        }
        if($this->_sendBuffer === ''){
            $this->destroy();
        }
    }

    /**
     * 将数据从一个流到另一个目的地
     */
    public function pipe($dest){
        $source = $this;
        $this->worker->onMessage = function($source, $data)use($dest)
        {
            $dest->send($data);
        };
        $this->worker->onClose = function($source)use($dest)
        {
            $dest->destroy();
        };
        $dest->worker->onBufferFull = function($dest)use($source)
        {
            $source->pauseRead();
        };
        $dest->worker->onBufferDrain = function($dest)use($source)
        {
            $source->resumeRead();
        };
    }

    /**
     * 获取客户端地址
     * @return array|int 成功返回array[0]是ip,array[1]是端口. 失败返回false
     */
    public function getClientAddress(){
        if($this->_clientAddress){
            $postion = strrpos($this->_clientAddress, ':');
            if(is_int($postion)){
                $ret[0] = substr($this->_clientAddress, 0, $postion);
                $ret[1] = substr($this->_clientAddress, $postion+1);
                return $ret;
            }
        }
        return false;
    }

    /**
     * 销毁链接
     * @param $connect resource 需要销毁的链接
     */
    public function destroy(){
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
                self::$statistics['exception_count']++;
                Log::write('FastWS: execution callback function callbackConnectClose-'.$this->worker->callbackConnectClose . ' throw exception', 'FATAL');
            }
        }
    }

    /**
     * 截取消息的后部分. 扔掉前部分
     * @param $start
     * @param null $length
     */
    public function substrReadData($start, $length=null)
    {
        if(is_null($length)){
            $this->_readDate = substr($this->_readDate, $start);
        }else{
            $this->_readDate = substr($this->_readDate, $start, $length);
        }

    }

    /**
     * 待发送的缓冲区是否已经超过最大限度.
     * 本函数会出发待发送缓冲区已满的回调函数
     * @return bool 大于或等于待发送缓冲区的最大限度
     */
    private function _sendBufferIsFull()
    {
        if(strlen($this->_sendBuffer) >= $this->maxSendBufferSize){
            if($this->worker->callbackSendBufferFull){
                try{
                    call_user_func($this->worker->callbackSendBufferFull, $this);
                }catch (\Exception $e){
                    self::$statistics['exception_count']++;
                    Log::write('FastWS: execution callback function callbackSendBufferFull-'.$this->worker->callbackSendBufferFull . ' throw exception', 'FATAL');
                }
                return true;
            }
        }
        return false;
    }

    /**
     * 暂停读取消息
     */
    private function pauseRead(){
        FastWS::$globalEvent->delOne($this->_connect, EventInterface::EVENT_TYPE_READ);
        $this->_isPauseRead = true;
    }

    /**
     * 继续读取消息
     */
    private function resumeRead(){
        if($this->_isPauseRead === true){
            FastWS::$globalEvent->add(array($this, 'read'), array(), $this->_connect, EventInterface::EVENT_TYPE_READ);
            $this->_isPauseRead = false;
            $this->read($this->_connect);
        }
    }
}