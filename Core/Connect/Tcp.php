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

class Tcp implements ConnectInterface{

    /**
     * 所能接收的最大数据
     * @var int
     */
    public static $maxPackageSize = 10485760;

    /**
     * 关闭客户端链接
     */
    public static function close(){

    }

    /**
     * 获取客户端来访IP
     */
    public static function getIp(){

    }

    /**
     * 获取客户端来访端口
     */
    public static function getPort(){

    }
}