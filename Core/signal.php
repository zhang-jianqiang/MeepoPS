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
class Signal{

    public static function setSignal(){

        // uninstall stop signal handler
        pcntl_signal(SIGINT,  SIG_IGN, false);
        // uninstall reload signal handler
        pcntl_signal(SIGUSR1, SIG_IGN, false);
        // uninstall  status signal handler
        pcntl_signal(SIGUSR2, SIG_IGN, false);
        // reinstall stop signal handler
        self::$globalEvent->add(SIGINT, EventInterface::EV_SIGNAL, array('\Workerman\Worker', 'signalHandler'));
        //  uninstall  reload signal handler
        self::$globalEvent->add(SIGUSR1, EventInterface::EV_SIGNAL,array('\Workerman\Worker', 'signalHandler'));
        // uninstall  status signal handler
        self::$globalEvent->add(SIGUSR2, EventInterface::EV_SIGNAL, array('\Workerman\Worker', 'signalHandler'));

    }

    /**
     * 信号处理函数
     * @param int $signal
     */
    public static function signalHandler($signal)
    {
        switch($signal)
        {
            // stop
            case SIGINT:
                self::stopAll();
                break;
            // reload
            case SIGUSR1:
                self::$_pidsToRestart = self::getAllWorkerPids();
                self::reload();
                break;
            // show status
            case SIGUSR2:
                self::writeStatisticsToStatusFile();
                break;
        }
    }
}