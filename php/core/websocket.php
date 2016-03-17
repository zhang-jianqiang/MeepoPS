<?php
/**
 * WebSocket核心程序
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/3/17
 * Time: 下午2:50
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Core;

class WebSocket{

    //一次接受的消息长度
    public $msgLength = 2048;

    //socket句柄
    private $socket = null;

    public function __construct()
    {
        //第一步: 创建一个socket
        $this->socketCreate();
        //第二步: 给socket绑定IP和端口
        $this->socketBind();
        //第三步: 监听socket句柄的所有连接
        $this->socketListen();
    }

    /**
     * 第一步: 创建一个socket
     */
    private function socketCreate(){
        if (($this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            return false;
        }
        return true;
    }

    /**
     * 第二步: 给socket绑定IP和端口
     */
    private function socketBind(){
        if (socket_bind($this->socket, FAST_WS_SOCKET_HOST, FAST_WS_SOCKET_PORT) === false) {
            return false;
        }
        return true;
    }

    /**
     * 第三步: 监听socket句柄的所有连接
     */
    private function socketListen(){
        if (socket_listen($this->socket, FAST_WS_SOCKET_BACKLOG) === false) {
            return false;
        }
        return true;
    }

    /**
     * 接受一个新连接
     * @return bool|resource
     */
    private function socketAccept(){
        if (($connect = socket_accept($this->socket)) === false) {
            return false;
        }
        return $connect;
    }

    /**
     * 向指定的连接发送消息
     * @param $connect resource 指定的连接资源,由socket_accept()返回
     * @param $msg string 消息内容
     * @param $length int 消息长度
     * @return int 已发送的消息长度
     */
    private function socketWrite($connect, $msg, $length){
        return socket_write($connect, $msg, $length);
    }

    /**
     * 读取指定链接中的数据
     * @param $connect resource 指定的连接资源,由socket_accept()返回
     * @param int $length int 一次读取的长度
     * @param int $type
     * @return bool|string
     */
    private function socketRead($connect, $type=PHP_BINARY_READ){
        $buf = socket_read($connect, $this->msgLength, $type);
        if ($buf === false) {
            return false;
        }
        return $buf;
    }

    /**
     * 关闭指定的链接
     * @param $connect resource 指定的连接资源,由socket_create()或socket_accept()返回
     */
    private function socketClose($connect){
        socket_close($connect);
    }

    /**
     * 获取上一次的错误信息
     * @return array errno是错误码,error是错误提示
     */
    private function socketError(){
        return ['errno' => socket_last_error(), 'error'=>socket_strerror(socket_last_error())];
    }

    /**
     * 启动WebSocket
     * @return bool
     */
    public function run(){
        //有用户连接进来后...
        do {
            $connect = $this->socketAccept();
            if ($connect === false) {
                Log::write('socket_accept() failed: reason: ' . $this->socketError());
                break;
            }
            /* Send instructions. */
            $msg = "\nWelcome to the PHP Test Server. \n" .
                "To quit, type 'quit'. To shut down the server type 'shutdown'.\n";
            $this->socketWrite($connect, $msg, strlen($msg));

            //有用户发送消息后...
            do {
                $buf = $this->socketRead($connect, PHP_NORMAL_READ);
                echo "收到消息: $buf\n";
                if ($buf === false) {
                    Log::write('socket_read() failed: reason: ' . $this->socketError());
                    break 2;
                }
                if (!$buf = trim($buf)) {
                    continue;
                }
                if ($buf === 'quit') {
                    break;
                }
                if ($buf === 'shutdown') {
                    $this->socketClose($connect);
                    break 2;
                }
                $backMsg = "FastWS: You said '$buf'.\n";
                $this->socketWrite($connect, $backMsg, strlen($backMsg));
            } while (true);
            socket_close($connect);
        } while (true);
        socket_close($this->socket);
        return true;
    }
}