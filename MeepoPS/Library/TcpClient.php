<?php
/**
 * Created by Lane
 * User: lane
 * Date: 16/6/27
 * Time: 下午4:40
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace MeepoPS\Library;

use MeepoPS\Core\Event\EventInterface;
use MeepoPS\Core\Log;
use MeepoPS\Core\MeepoPS;
use MeepoPS\Core\TransportProtocol\Tcp;

class TcpClient extends Tcp{

    public $callbackConnect;
    private $_static = self::CONNECT_STATUS_CLOSING;
    private $_protocol;
    private $_host;
    private $_port;
    private $_isAsync;

    /**
     * TcpClient constructor.
     * @param string $protocol
     * @param string $host
     * @param string $port
     * @param bool $isAsync
     */
    public function __construct($protocol, $host, $port, $isAsync=false){
        //传入的协议是应用层协议还是传输层协议
        $protocol = '\MeepoPS\Core\ApplicationProtocol\\' . ucfirst($protocol);
        if (class_exists($protocol, false)) {
            $this->_protocol = '\MeepoPS\Core\ApplicationProtocol\\' . $protocol;
        } else {
            Log::write('Application layer protocol class not found. portocol:' . $protocol, 'FATAL');
        }
        $this->_applicationProtocolClassName = $protocol;
        //属性赋值
        $this->_host = $host;
        $this->_port = $port;
        $this->id = self::$_recorderId++;
        $this->_isAsync = $isAsync ? STREAM_CLIENT_ASYNC_CONNECT : STREAM_CLIENT_CONNECT;
        //更改统计信息
        self::$statistics['total_connect_count']++;
    }

    public function connect(){
        $this->_connect = stream_socket_client('tcp://' . $this->_host . ':' . $this->_port, $errno, $errmsg, 5, $this->_isAsync);
        if(!$this->_connect){
            $this->_connect->close();
            $this->_static = self::CONNECT_STATUS_CLOSED;
            return;
        }
        //监听此链接
        MeepoPS::$globalEvent->add(array($this, 'checkConnection'), array(), $this->_connect, EventInterface::EVENT_TYPE_WRITE);
    }

    /**
     * @param $tcpConnect resource TCP链接
     */
    public function checkConnection($tcpConnect){
        if(!stream_socket_get_name($tcpConnect, true)){
            $this->destroy();
            Log::write('Get Socket name found socket resource is invalid.', 'ERROR');
            return;
        }
        MeepoPS::$globalEvent->delOne($tcpConnect, EventInterface::EVENT_TYPE_WRITE);
        stream_set_blocking($tcpConnect, 0);
        if (function_exists('socket_import_stream')) {
            $socket = socket_import_stream($tcpConnect);
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