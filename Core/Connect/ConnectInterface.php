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
    protected static $statistics = array(
        //当前链接数
        'current_connect_count' => 0,
        //总链接数
        'total_connect_count' => 0,
        //当前处理的请求数
        'current_request_count' => 0,
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
     * 发送数据
     */
    abstract public function send();
    
    /**
     * 关闭客户端链接
     * @return mixed
     */
    abstract public function close();

    /**
     * 获取客户端IP
     * @return mixed
     */
    abstract public function getIp();

    /**
     * 获取客户端端口
     * @return mixed
     */
    abstract public function getPort();
}