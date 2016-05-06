<?php
/**
 * 常用函数
 * Created by lixuan868686@163.com
 * User: lane
 * Date: 16/3/25
 * Time: 下午2:32
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Core;
class Func{

    /**
     * 获取客户端IP地址
     *
     */
    public static function  getClientIp()
    {
        if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown")) {
            $ip = getenv("HTTP_CLIENT_IP");
        } elseif (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown")) {
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        } elseif (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")) {
            $ip = getenv("REMOTE_ADDR");
        } elseif (isset($_SERVER["REMOTE_ADDR"]) && $_SERVER["REMOTE_ADDR"] && strcasecmp($_SERVER["REMOTE_ADDR"], "unknown")) {
            $ip = $_SERVER["REMOTE_ADDR"];
        } else {
            $ip = "unknown";
        }
        return $ip;
    }

    /**
     * @descrpition 数组的KEY变更为项中的ID
     * @param $arr
     * @return array
     */
    public static function  arrayKey($arr, $key = 'id')
    {
        $data = array();
        foreach ($arr as $a) {
            $data[$a[$key]] = $a;
        }
        return $data;
    }

    public static function setProcessTitle($title)
    {
        if(function_exists('cli_set_process_title')) {
            @cli_set_process_title($title);
        }elseif(extension_loaded('proctitle') && function_exists('setproctitle')){
            @setproctitle($title);
        }
    }

    public static function getCurrentUser(){
        $userInfo = posix_getpwuid(posix_getuid());
        return $userInfo['name'];
    }
}