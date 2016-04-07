<?php
/**
 * 链接的抽象类.如TCP或UDP等
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/3/27
 * Time: 上午1:13
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Core\Connect;

abstract class ConnectInterface{

    //统计信息
    public static $statistics = array(
        //当前链接数
        'current_connect_count' => 0,
        //总链接数
        'total_connect_count' => 0,
        //当前处理的请求数
//        'current_request_count' => 0,
        //总请求数
        'total_request_count' => 0,
        //总发送数
        'total_send_count' => 0,
        //发送失败数
        'send_failed_count' => 0,
        //异常数
        'exception_count' => 0,
    );

    /**
     * 构造函数
     * @param $socket resource 由stream_socket_accept()返回
     * @param $clientAddress string 由stream_socket_accept()的第三个参数$peerName
     */
    abstract public function __construct($socket, $clientAddress);

    /**
     * 析构函数
     */
    abstract public function __destruct();

    /**
     * 读取数据
     * @param $connect resource 由stream_socket_accept()返回
     * @param $isCheckEof bool 如果fread读取到的是空数据或者false的话,是否销毁链接.默认为true
     */
    abstract public function read($connect, $isCheckEof=true);

    /**
     * 发送数据
     * @param $data string 待发送的数据
     * @param $isEncode bool 发送前是否根据应用层协议转码
     */
    abstract public function send($data, $isEncode=true);
    
    /**
     * 关闭客户端链接
     * @param $data string 关闭链接前发送的消息
     * @return mixed
     */
    abstract public function close($data='');

    /**
     * 获取客户端地址
     * @return array|int 成功返回array[0]是ip,array[1]是端口. 失败返回false
     */
    abstract public function getClientAddress();
}