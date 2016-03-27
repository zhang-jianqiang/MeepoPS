<?php
/**
 * 链接的Interface.如TCP或UDP等
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/3/27
 * Time: 上午1:13
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Core\Connect;

interface ConnectInterface{

    /**
     * 关闭客户端链接
     * @return mixed
     */
    public static function close();

    /**
     * 获取客户端IP
     * @return mixed
     */
    public static function getIp();

    /**
     * 获取客户端端口
     * @return mixed
     */
    public static function getPort();
}