<?php
/**
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/6/27
 * Time: 下午4:40
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace Workerman\Connection;

use MeepoPS\Core\Event\EventInterface;
use MeepoPS\Core\Log;
use MeepoPS\Core\MeepoPS;
use MeepoPS\Core\TransportProtocol\Tcp;

class AsyncTcp extends Tcp{

    public $callbackConnect;
    private $_static = self::CONNECT_STATUS_CLOSING;
    private $_protocol;
    private $_host;
    private $_port;

    public function __construct($protocol, $host, $port){
        //传入的协议是应用层协议还是传输层协议
        if (class_exists('\MeepoPS\Core\TransportProtocol\\' . $protocol)) {
            $this->_protocol = '\MeepoPS\Core\TransportProtocol\\' . $protocol;
        //不是传输层协议,则认为是应用层协议.直接new应用层协议类
        } else if (class_exists('\MeepoPS\Core\TransportProtocol\\' . $protocol)) {
            $this->_protocol = '\MeepoPS\Core\TransportProtocol\\' . $protocol;
        }else{
            Log::write('Application layer protocol class not found.', 'FATAL');
        }
        //属性赋值
        $this->_host = $host;
        $this->_port = $port;
        $this->id = self::$_recorderId++;
        //更改统计信息
        self::$statistics['total_connect_count']++;
    }

    public function connect(){
        $this->_connect = stream_socket_client('tcp://' . $this->_host . ':' . $this->_port, $errno, $errmsg, 0, STREAM_CLIENT_ASYNC_CONNECT);
        if(!$this->_connect){
            $this->_static = self::CONNECT_STATUS_CLOSED;
            return;
        }
        //监听此链接
        MeepoPS::$globalEvent->add(array($this, 'checkConnection'), array(), $this->_connect, EventInterface::EVENT_TYPE_WRITE);
    }

    /**
     * @param $socket resource TCP链接
     */
    public function checkoutConnect($tcpConnect){
        if(stream_socket_get_name($tcpConnect, true) !== false){
            $this->destroy();
            Log::write('Get Socket name found socket resource is invalid.', 'ERROR');
            return;
        }
        MeepoPS::$globalEvent->delOne($tcpConnect, EventInterface::EVENT_TYPE_WRITE);
        stream_set_blocking($tcpConnect, 0);
        if (function_exists('socket_import_stream')) {
            $socket = socket_import_stream($this->$tcpConnect);
            @socket_set_option($socket, SOL_SOCKET, SO_KEEPALIVE, 1);
            @socket_set_option($socket, SOL_TCP, TCP_NODELAY, 1);
        }
        MeepoPS::$globalEvent->add(array($this, 'read'), array(), $tcpConnect, EventInterface::EVENT_TYPE_READ);
        if($this->_sendBuffer){
            MeepoPS::$globalEvent->add(array($this, 'fwrite'), array(), $tcpConnect, EventInterface::EVENT_TYPE_WRITE);
        }
        $this->_static = self::CONNECT_STATUS_ESTABLISH;
    }
}