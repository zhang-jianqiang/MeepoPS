<?php
/**
 * 信号处理
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/3/18
 * Time: 下午1:52
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Core;

class Signal{

    public static $fastWS;

    public static function setSignal(&$fastWS){
        self::$fastWS = $fastWS;
        // uninstall stop signal handler
        pcntl_signal(SIGINT, array('self', 'signalHandler'));
        // uninstall reload signal handler
        pcntl_signal(SIGUSR1, array('self', 'signalHandler'));
        // uninstall  status signal handler
        pcntl_signal(SIGUSR2, array('self', 'signalHandler'));
        //
        pcntl_signal_dispatch();
    }

    /**
     * 信号处理函数
     * @param int $signal
     */
    public static function signalHandler($signal)
    {
        Log::write('signalHandler - 1');
        self::$fastWS->close();
        error_log(1);
        switch($signal)
        {
            // stop
            case SIGINT:
                error_log(2);
                break;
            // reload
            case SIGUSR1:
                error_log(3);
                break;
            // show status
            case SIGUSR2:
                error_log(4);
                break;
        }
    }
}