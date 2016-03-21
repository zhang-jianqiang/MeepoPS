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
    //客户端列表
    public $clientList = array();
    //回调函数
    public $callFunc = array(
        'new_connection' => null,
        'read_data' => null,
    );

    //socket句柄
    private $socket = null;

    /**
     * 初始化
     * WebSocket constructor.
     */
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
     * 析构函数.释放所有的资源
     */
    public function __destruct()
    {
        $this->socketClose();
    }

    /**
     * 启动WebSocket
     * @return bool
     */
    public function run(){
        do {
            $selectList = array_merge([$this->socket], $this->clientList);
            //监听
            if($this->socketSelect($selectList, $write, $except) < 1) {
                Log::write('socketSelect() failed: reason: ' . $this->socketError());
                continue;
            }
            //新增客户端
            if (in_array($this->socket, $selectList)) {
                $connect = $this->addContent();
                if($connect === false){
                    Log::write('addContent() failed: reason: ' . $this->socketError());
                }
                //将socket_create返回的资源删掉
                unset($selectList[0]);
            }
            //遍历客户端
            if($this->readClientList($selectList) === false){
                Log::write('readClientList() failed: reason: ' . $this->socketError());
                continue;
            }
        } while (true);
        $this->socketClose();
    }

    /**
     * 向指定的连接发送消息
     * @param $connect resource 指定的连接资源,由socket_accept()返回
     * @param $msg string 消息内容
     * @param $length int 消息长度
     * @return int 已发送的消息长度
     */
    public function socketWrite($connect, $msg, $length=null){
        if(is_null($length)){
            $length = strlen($msg);
        }
        if(!$msg){
            return false;
        }
        return socket_write($connect, $msg, $length);
    }

    /**
     * 关闭指定的链接
     * @param $connect resource 指定的连接资源,由socket_accept()返回
     */
    public function connectClose($connect){
        if(($key = array_search($connect, $this->clientList)) !== false) {
            unset($this->clientList[$key]);
        }
        socket_close($connect);
    }

    /**
     * 关闭Socket,会出发析构函数.
     */
    public function close(){
        die('FastWS has been closed');
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
     * 读取指定链接中的数据
     * @param $connect resource 指定的连接资源,由socket_accept()返回
     * @param int $type
     * @param int $length int 一次读取的长度
     * @return bool|string
     */
    private function socketRead($connect, $type=PHP_BINARY_READ, $length=null){
        if(is_null($length)){
            $length = $this->msgLength;
        }
        $buf = socket_read($connect, $length, $type);
        if ($buf === false) {
            return false;
        }
        return $buf;
    }

    /**
     * 读取指定链接中的数据
     * @param $connect resource 指定的连接资源,由socket_accept()返回
     * @param int $type
     * @param int $length int 一次读取的长度
     * @return int
     */
    private function socketRecv($connect, &$buffer, $type=MSG_DONTWAIT, $length=null){
        if(is_null($length)){
            $length = $this->msgLength;
        }
        $byte = socket_recv($connect, $buffer, $length, $type);
        return $byte;
    }

    /**
     * 监控指定的Socket列表
     * @param $selectList array(resource) 指定的连接资源,由socket_create()或socket_accept()返回
     */
    private function socketSelect(&$selectList, &$write=null, &$except=null, $timeout=null){
        return socket_select($selectList, $write, $except, $timeout);
    }

    /**
     * 关闭所有资源
     */
    private function socketClose(){
        foreach($this->clientList as $client){
            socket_close($client);
        }
        socket_close($this->socket);
        unset($this->clientList);
        unset($this->socket);
    }

    /**
     * 获取上一次的错误信息
     * @return array errno是错误码,error是错误提示
     */
    private function socketError($isJson=true){
        $ret = ['errno' => socket_last_error(), 'error'=>socket_strerror(socket_last_error())];
        return $isJson ? json_encode($ret) : $ret;
    }

    /**
     * 回调函数
     * @param $func array|string
     */
    private function callFunction($func, $param, $paramIsArray=false){
        if($paramIsArray){
            $sysFunc = 'call_user_func_array';
        }else{
            $sysFunc = 'call_user_func';
        }
        if(is_string($func) && function_exists($func)){
            $sysFunc($func, $param);
        }else if(is_array($func) && count($func) >= 2) {
            $sysFunc([$func[0], $func[1]], $param);
        }
        return false;
    }

    /**
     * 新增客户端
     * @return bool|resource
     */
    private function addContent(){
        $connect = $this->socketAccept();
        if ($connect === false) {
            return false;
        }
        $this->clientList[] = $connect;
        $this->callFunction($this->callFunc['new_connection'], $connect);
        return $connect;
    }

    /**
     * 读取 - 遍历所有客户端
     * @return bool|resource
     */
    private function readClientList($selectList){
        foreach ($selectList as $select) {
            $msg = '';
            do {
                $num = $this->socketRecv($select, $buf);
                if($num < 1){
                    break;
                }
                $msg .= $buf;
            }while(PRIORITY_ONE_QUERY);
            $msg = trim($msg);
            if (!$msg) {
                continue;
            }
            $this->callFunction($this->callFunc['read_data'], array($select, $msg), true);
        }
    }
}