<?php
/**
 * FastWS核心文件
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/3/23
 * Time: 下午2:41
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Core;

class FastWS{
    /**
     * Worker相关.每个Worker是一个子进程.可类比Nginx的Worker概念和PHP-FPM的child_processes
     */
    //主进程名称.即FastWS实例名称
    public $name;
    //所有的worker列表
    protected static $_workerList = array();
    //每个Worker的Pid
    private static $_workerPid;
    //Worker和Pid的对应关系列表
    private static $_workerPidMapList;
    //WorkerId和Pid的对应关系列表
    private static $_idMap;
    //workerId
    private $_workerId;
    //子进程名称
    public $workerName;
    //worker总数
    public $workerCount = 1;
    //启动Worker的用户
    public $user = 'nobody';
    //启动Worker的用户组
    public $group = 'nobody';
    //当前状态
    private static $_currentStatus = FASTWS_STATUS_STARTING;

    /**
     * 回调函数
     */
    //FastWS启动时触发该回调函数
    public $callbackStart;
    //有新的链接加入时触发该回调函数
    public $callbackConnect;
    //收到新数据时触发该回调函数
    public $callbackNewData;
    //Worker停止时触发该回调函数
    public $callbackWorkerStop;
    //有错误时触发该回调函数
    public $callbackError;
    //缓冲区已经塞满时触发该回调函数
    public $callbackBufferFull;
    //缓冲区没有积压时触发该回调函数
    public $callbackBufferEmpty;

    /**
     * 协议相关
     */
    //传输层协议
    private $_protocolTransfer = 'tcp';
    //应用层协议
    private $_protocolApplication = 'webscoket';

    /**
     * 客户端相关
     */
    //客户端列表.每个链接是一个客户端
    public $clientList = array();

    /**
     * 事件相关
     */
    //全局事件
    private static $_globalEvent;
    //当前的事件轮询方式,默认为select.但是不推荐select,建议使用ngxin所采用的epoll方式.需要安装libevent
    private static $_currentPoll = 'select';


    /**
     * Socket相关
     */
    //主进程PID
    private static $_masterPid;
    //主进程Socket资源.由stream_socket_server()返回
    private static $_masterSocket;
    //主进程Socket属性,包括协议/IP/端口.new FastWS()时传入,格式为http://127.0.0.1:19910
    private static $_masterSocketAttribute;
    //Socket上下文资源,由stream_context_create()返回
    private static $_stramContext;

    /**
     * 其他
     */
    //是否启动守护进程
    public static $isDaemon = false;
    //统计信息
    private static $_statistics;


    public static function runAll(){
        self::_init();
        self::_command();
        self::_daemon();
        self::_createWorkers();
        self::_signalInstalls();
//        self::saveMasterPid();
//        self::forkWorkers();
//        self::displayUI();
//        self::resetStd();
//        self::monitorWorkers();
    }

    /**
     * 初始化操作
     */
    private static function _init()
    {
        //添加统计数据
        self::$_statistics['start_time'] = date('Y-m-d H:i:s');
        //给主进程起个名字
        Func::setProcessTitle('FastWS_Master_process');
        //设置ID
        foreach(self::$_workerList as $workerId=>$worker)
        {
            self::$_idMap[$workerId] = array_fill(0, $worker->count, 0);
        }
        //初始化定时器
        Timer::init();
    }

    /**
     * 解析启动命令,比如start, stop等 执行不同的操作
     */
    private static function _command()
    {
        global $argv;
        $operation = trim($argv[1]);
        //获取主进程ID
        $masterPid = @file_get_contents(FASTWS_MASTER_PID_PATH);
        //主进程当前是否正在运行
        $masterIsAlive = false;
        if($masterPid){
            //给FastWS主进程发送一个信号, 信号为SIG_DFL, 表示采用默认信号处理程序.如果发送信号成功则该进程正常
            if(@posix_kill($masterPid, SIG_DFL)){
                $masterIsAlive = true;
            }
        }
        //不能重复启动
        if($masterIsAlive && $operation === 'start'){
            Log::write('FastWS already running. file: ' . $argv[0]);
            exit;
        }
        //未启动不能查看状态
        if(!$masterIsAlive && $operation === 'status'){
            Log::write('FastWS no running. file: ' . $argv[0]);
            exit;
        }
        //未启动不能终止
        if(!$masterIsAlive && $operation === 'stop'){
            Log::write('FastWS no running. file: ' . $argv[0]);
            exit;
        }
        //根据不同的执行参数执行不同的动作
        switch($operation){
            //启动
            case 'start':
                if(isset($argv[2]) && trim($argv[2]) === '-d'){
                    self::$isDaemon = true;
                }
                break;
            //停止和重启
            case 'stop':
            case 'restart':
                //给当前正在运行的主进程发送中指信号SIGINT(ctrl+c)
                if($masterPid){
                    posix_kill($masterPid, SIGINT);
                }
                $nowTime = time();
                $timeout = 5;
                while(true){
                    //主进程是否在运行
                    $masterIsAlive = $masterPid && posix_kill($masterPid, SIG_DFL);
                    if($masterIsAlive){
                        //如果超时
                        if((time() - $nowTime) > $timeout){
                            Log::write('FastWS stop master process failed');
                            exit;
                        }
                        //等待10毫秒,再次判断是否终止.
                        usleep(10000);
                        continue;
                    }
                    if($operation === 'stop'){
                        exit();
                    }
                    if(trim($argv[2]) === '-d'){
                        self::$isDaemon = true;
                    }
                    break;
                }
                break;
            //状态
            case 'status':
                //删除之前的统计文件.忽略可能发生的warning(文件不存在的时候)
                @unlink(FASTWS_STATISTICS_PATH);
                //给正在运行的FastWS的主进程发送SIGUSR2信号,此时主进程收到SIGUSR信号后会通知子进程将当前状态写入文件当中
                posix_kill($masterPid, SIGUSR2);
                //本进程sleep.目的是等待正在运行的FastWS的子进程完成写入状态文件的操作
                sleep(1);
                //输出状态
                echo file_get_contents(FASTWS_STATISTICS_PATH);
                exit();
            //停止所有的FastWS
            case 'kill':
                exec("ps aux | grep $argv[0] | grep -v grep | awk '{print $2}' |xargs kill -SIGINT");
                exec("ps aux | grep $argv[0] | grep -v grep | awk '{print $2}' |xargs kill -SIGKILL");
                exit();
            //参数不合法
            default :
                Log::write('Parameter error. Usage: php index.php start|stop|restart|status|kill');
                exit;
        }
    }

    /**
     * 启动守护进程
     */
    private static function _daemon(){
        if(!self::$isDaemon){
            return;
        }
        //改变umask
        umask(0);
        //创建一个子进程
        $pid = pcntl_fork();
        //fork失败
        if($pid === -1){
            throw new \Exception('_daemon: fork failed');
        //父进程
        }else if ($pid > 0){
            exit();
        }
        //设置子进程为Session leader, 可以脱离终端工作.这是实现daemon的基础
        if(posix_setsid() === -1){
            throw new \Exception("_daemon: setsid failed");
        }
        //再次在开启一个子进程
        //这不是必须的,但通常都这么做,防止获得控制终端.
        $pid = pcntl_fork();
        if($pid === -1){
            throw new \Exception('_daemon: fork2 failed');
        //将父进程退出
        }else if($pid !== 0){
            exit();
        }
    }


    /**
     * 创建Worker
     */
    private static function _createWorkers()
    {
        foreach (self::$_workerList as $worker) {
            //给每个Worker起名字
            $worker->name = $worker->name ? $worker->name : 'not_set';
            //获取每个Worker的用户
            if (!empty($worker->user)) {
                $worker->user = Func::getCurrentUser();
            } else if (posix_getuid() && $worker->user != Func::getCurrentUser()) {
                Log::write('Warning: You must have the root permission to change uid and gid.');
            }
            //每个Worker开始监听端口
            $worker->listen();
        }
    }

    /**
     * 注册信号,给信号添加回调函数
     */
    private static function _signalInstalls(){
        //SIGINT为停止FastWS的信号
        pcntl_signal(SIGINT, array('\FastWS\Core\Worker', 'signalHandler'), false);
        //SIGUSR1 尚未使用.计划为载入文件,即nginx的reload
        pcntl_signal(SIGUSR1, array('\FastWS\Core\Worker', 'signalHandler'), false);
        //SIGUSR2 为查看FastWS所有状态的信号
        pcntl_signal(SIGUSR2, array('\FastWS\Core\Worker', 'signalHandler'), false);
        //SIGPIPE 信号会导致Linux下Socket进程终止.我们忽略他
        pcntl_signal(SIGPIPE, SIG_IGN, false);
    }

    public static function signalHandler($signal){
        switch($signal){
            case SIGINT:
                self::stopAll();
                break;
            case SIGUSR1:
                break;
            case SIGUSR2:
                self::_statisticsToFile();
                break;
        }
    }

    /**
     * 终止FastWS所有进程
     */
    private static function stopAll()
    {
        self::$_currentStatus = FASTWS_STATUS_CLOSING;
        if(self::$_masterPid === posix_getpid()){
            $workerPidArray = self::getAllWorksPids();
            foreach($workerPidArray as $workerPid){
                posix_kill($workerPid, SIGINT);
                Timer::add('posix_kill', array($workerPid, SIGKILL), FASTWS_KILL_WORKER_TIME_INTERVAL, false);
            }
        }else {
            foreach(self::$_workerList as $worker) {
                $worker->stop();
            }
            exit();
        }
    }

    /**
     * 将当前信息统计后写入文件中
     */
    private static function _statisticsToFile()
    {
        if(self::$_masterPid == posix_getpid());
    }
    protected static function writeStatisticsToStatusFile()
    {
        // For master process.
        if(self::$_masterPid === posix_getpid())
        {
            $loadavg = sys_getloadavg();
            
            file_put_contents(FASTWS_STATISTICS_PATH, "---------------------------------------GLOBAL STATUS--------------------------------------------\n");
            file_put_contents(FASTWS_STATISTICS_PATH, 'Workerman version:' . Worker::VERSION . "          PHP version:".PHP_VERSION."\n", FILE_APPEND);
            file_put_contents(FASTWS_STATISTICS_PATH, 'start time:'. date('Y-m-d H:i:s', self::$_globalStatistics['start_timestamp']).'   run ' . floor((time()-self::$_globalStatistics['start_timestamp'])/(24*60*60)). ' days ' . floor(((time()-self::$_globalStatistics['start_timestamp'])%(24*60*60))/(60*60)) . " hours   \n", FILE_APPEND);
            $load_str = 'load average: ' . implode(", ", $loadavg);
            file_put_contents(FASTWS_STATISTICS_PATH, str_pad($load_str, 33) . 'event-loop:'.self::getEventLoopName()."\n", FILE_APPEND);
            file_put_contents(FASTWS_STATISTICS_PATH,  count(self::$_pidMap) . ' workers       ' . count(self::getAllWorkerPids())." processes\n", FILE_APPEND);
            file_put_contents(FASTWS_STATISTICS_PATH, str_pad('worker_name', self::$_maxWorkerNameLength) . " exit_status     exit_count\n", FILE_APPEND);
            foreach(self::$_pidMap as $worker_id =>$worker_pid_array)
            {
                $worker = self::$_workers[$worker_id];
                if(isset(self::$_globalStatistics['worker_exit_info'][$worker_id]))
                {
                    foreach(self::$_globalStatistics['worker_exit_info'][$worker_id] as $worker_exit_status=>$worker_exit_count)
                    {
                        file_put_contents(FASTWS_STATISTICS_PATH, str_pad($worker->name, self::$_maxWorkerNameLength) . " " . str_pad($worker_exit_status, 16). " $worker_exit_count\n", FILE_APPEND);
                    }
                }
                else
                {
                    file_put_contents(FASTWS_STATISTICS_PATH, str_pad($worker->name, self::$_maxWorkerNameLength) . " " . str_pad(0, 16). " 0\n", FILE_APPEND);
                }
            }
            file_put_contents(FASTWS_STATISTICS_PATH,  "---------------------------------------PROCESS STATUS-------------------------------------------\n", FILE_APPEND);
            file_put_contents(FASTWS_STATISTICS_PATH, "pid\tmemory  ".str_pad('listening', self::$_maxSocketNameLength)." ".str_pad('worker_name', self::$_maxWorkerNameLength)." connections ".str_pad('total_request', 13)." ".str_pad('send_fail', 9)." ".str_pad('throw_exception', 15)."\n", FILE_APPEND);

            chmod(FASTWS_STATISTICS_PATH, 0722);

            foreach(self::getAllWorkerPids() as $worker_pid)
            {
                posix_kill($worker_pid, SIGUSR2);
            }
            return;
        }

        // For child processes.
        $worker = current(self::$_workers);
        $wrker_status_str = posix_getpid()."\t".str_pad(round(memory_get_usage(true)/(1024*1024),2)."M", 7)." " .str_pad($worker->getSocketName(), self::$_maxSocketNameLength) ." ".str_pad(($worker->name === $worker->getSocketName() ? 'none' : $worker->name), self::$_maxWorkerNameLength)." ";
        $wrker_status_str .= str_pad(ConnectionInterface::$statistics['connection_count'], 11)." ".str_pad(ConnectionInterface::$statistics['total_request'], 14)." ".str_pad(ConnectionInterface::$statistics['send_fail'],9)." ".str_pad(ConnectionInterface::$statistics['throw_exception'],15)."\n";
        file_put_contents(FASTWS_STATISTICS_PATH, $wrker_status_str, FILE_APPEND);
    }

    /**
     * Get all pids of worker processes.
     * @return array
     */
    protected static function getAllWorkerPids()
    {
        $pid_array = array();
        foreach(self::$_pidMap as $worker_pid_array)
        {
            foreach($worker_pid_array as $worker_pid)
            {
                $pid_array[$worker_pid] = $worker_pid;
            }
        }
        return $pid_array;
    }

    private static function getPollWay()
    {
        array('libevent', 'select');
    }
}