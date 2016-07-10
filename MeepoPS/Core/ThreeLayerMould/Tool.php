<?php
/**
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/7/9
 * Time: 下午11:30
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace MeepoPS\Core\ThreeLayerMould;

class Tool{
    public static function verifyAuth($token){
        return true;
    }

    public static function encodeTransferAddress($ip, $port){
        return $ip . '_' . $port;
    }

    public static function decodeTransferAddress($key){
        return explode('_', $key);
    }
}