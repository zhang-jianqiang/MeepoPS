<?php
/**
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/3/25
 * Time: 下午6:38
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Core;

class Log{
    public static function write($msg)
    {
        if(FASTWS_DEBUG){
            echo $msg;
        }
        $msg = '[' . date('Y-m-d H:i:s') . ']['.getmypid().']' . $msg . "\n";
        file_put_contents(FASTWS_LOG_PATH, $msg, FILE_APPEND | LOCK_EX);
    }
}