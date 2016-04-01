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
    public static function write($msg, $type='INFO')
    {
        $type = strtoupper($type);
        if(!in_array($type, array('INFO', 'ERROR', 'FATAL', 'WARNING', "TEST"))){
            exit('Log type no match');
        }
        $msg = '[' . $type . '][' . date('Y-m-d H:i:s') . ']['.getmypid().']' . $msg . "\n";
        if(FASTWS_DEBUG){
            echo $msg;
        }
        file_put_contents(FASTWS_LOG_PATH, $msg, FILE_APPEND | LOCK_EX);
        if($type === 'FATAL'){
            exit;
        }
    }
}