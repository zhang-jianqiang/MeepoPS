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

    public static function encodeClientId($transferIp, $transferPort, $connectId){
        return $transferIp . '_' . $transferPort . '_' . $connectId;
    }

    public static function decodeClientId($clientId){
        $result = explode('_', $clientId);
        return array('transfer_ip' => $result[0], 'transfer_port' => $result[1], 'connect_id' => $result[2]);
    }
}