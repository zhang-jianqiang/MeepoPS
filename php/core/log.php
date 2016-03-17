<?php
/**
 * Log类
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/3/17
 * Time: 下午5:05
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Core;

class Log{
    public static function write($log, $type='INFO'){
        $filename = FAST_WS_LOG_PATH;
        $path = dirname($filename);
        if (!file_exists($path)) {
            if(!mkdir($path, 0777, true)){
                die('log path mkdir failed: ' . $path);
            }
        }
        if (!is_writable($path)) {
            die('log path is not writeable!' . $path);
        }

        $nowTime = date('[Y-m-d H:i:s]');
        $pid = getmypid();
        $logMessage = $nowTime . '[' . strtoupper($type) . '][' . $pid . '] ' . $log . "\r\n";
        return error_log($logMessage, 3, $filename);
    }
}