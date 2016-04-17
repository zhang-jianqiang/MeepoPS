<?php
/**
 * UDP链接
 * Created by lixuan868686@163.com
 * User: lane
 * Date: 16/3/27
 * Time: 上午1:13
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Core\Connect;

class Udp implements ConnectInterface{

    /**
     * 构造函数
     * @param $socket resource 由stream_socket_accept()返回
     * @param $clientAddress string 由stream_socket_accept()的第三个参数$peerName
     */
    public function __construct($socket, $clientAddress){

    }

    /**
     * 析构函数
     */
    public function __destruct(){

    }

    /**
     * 读取数据
     * @param $connect resource 由stream_socket_accept()返回
     * @param $isCheckEof bool 如果fread读取到的是空数据或者false的话,是否销毁链接.默认为true
     */
    public function read($connect, $isCheckEof=true){

    }

    /**
     * 发送数据
     * @param $data string 待发送的数据
     * @param $isEncode bool 发送前是否根据应用层协议转码
     */
    public function send($data, $isEncode=true){
        return false;
    }

    /**
     * 关闭客户端链接
     * @param $data string 关闭链接前发送的消息
     * @return mixed
     */
    public function close($data=''){
        return array();
    }

    /**
     * 获取客户端地址
     * @return array|int 成功返回array[0]是ip,array[1]是端口. 失败返回false
     */
    public function getClientAddress(){
        return array();
    }
}