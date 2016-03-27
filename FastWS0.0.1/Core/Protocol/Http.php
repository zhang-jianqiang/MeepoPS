<?php
/**
 * 从TCP数据流中解析HTTP协议
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/3/26
 * Time: 下午10:27
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Core\Protocol;

use FastWS\Core\Connect\Tcp;

class Http implements ProtocolInterface{
    public static function input($data, Tcp $connect)
    {

    }

    public static function encode($data, Tcp $connect)
    {

    }

    public static function decode($data, Tcp $connect)
    {

    }
}
