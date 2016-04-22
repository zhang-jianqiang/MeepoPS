<?php
/**
 * FastWS核心文件
 * Created by lixuan868686@163.com
 * User: lane
 * Date: 16/3/23
 * Time: 下午2:41
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Core;

use FastWS\Core\Connect\ConnectInterface;
use FastWS\Core\Connect\Tcp;
use FastWS\Core\Event\EventInterface;

class FastWS
{
    /**
     * 实例相关.每个实例都是一个FastWS的对象.每个实例至少有一个进程
     */
    //实例名称
    public $instanceName;
    //绑定需要解析的协议
    private $_bindProtocol = '';
    //绑定需要监听的主机IP
    private $_bindHost = '';
    //绑定需要监听的端口
    private $_bindPort = '';
    //所有的实例列表
    protected static $_instanceList = array();
    //实例所属所有子进程的Pid列表.一个实例有多个Pid(多子进程).一个FastWS有多个实例
    //array('instance1'=>array(1001, 1002, 1003), 'instance2'=>array(1004, 1005, 1006))
    private static $_instancePidList;
    //实例ID
    private $_instanceId;
    //这个实例有多少子进程
    public $childProcessCount = 1;
    //启动实例的用户
    public $user = '';
    //启动实例的用户组
    public $group = '';
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
    //实例停止时触发该回调函数
    public $callbackInstanceStop;
    //链接断开时出发该回调函数
    public $callbackConnectClose;
    //有错误时触发该回调函数
    public $callbackError;
    //缓冲区已经塞满时触发该回调函数
    public $callbackSendBufferFull;
    //缓冲区没有积压时触发该回调函数
    public $callbackSendBufferEmpty;
    //接收到Ping时触发回调函数
    public $callbackPing;
    //接收到Pong时触发回调函数
    public $callbackPong;

    /**
     * 协议相关
     */
    //传输层协议
    public $protocolTransfer = 'tcp';
    //应用层协议
    public $protocolApplication = '';
    //应用层协议处理类
    public $protocolApplicationClassName = '';
    //传输层协议
    private static $_protocolTransferList = array(
        'tcp' => 'tcp',
        'udp' => 'udp',
        'ssl' => 'tcp',
        'tsl' => 'tcp',
        'sslv2' => 'tcp',
        'sslv3' => 'tcp',
        'tls' => 'tcp'
    );

    /**
     * 客户端相关
     */
    //客户端列表.每个链接是一个客户端
    public $clientList = array();

    /**
     * 事件相关
     */
    //全局事件
    public static $globalEvent;
    //当前的事件轮询方式,默认为select.但是不推荐select,建议使用ngxin所采用的epoll方式.需要安装libevent
    private static $_currentPoll = 'select';

    /**
     * Socket相关
     */
    //主进程PID
    private static $_masterPid;
    //主进程Socket资源.由stream_socket_server()返回
    private $_masterSocket;
    //Socket上下文资源,由stream_context_create()返回
    private $_streamContext;

    /**
     * 其他
     */
    //是否启动守护进程
    public static $isDaemon = false;
    //统计信息
    private static $_statistics = array(
        'start_time' => '',
        'instance_exit_info' => array(),
    );

    /**
     * 初始化.
     * FastWS constructor.
     * @param string $protocol string 协议,默认为Telnet
     * @param string $host string 需要监听的地址
     * @param string $port string 需要监听的端口
     * @param array $contextOptionList
     */
    public function __construct($protocol='', $host = '', $port='', $contextOptionList = array())
    {
        //---每一个实例的属性---
        $this->_bindProtocol = $protocol;
        $this->_bindHost = $host;
        $this->_bindPort = $port;
        //传入的协议是应用层协议还是传输层协议
        if (isset(self::$_protocolTransferList[$protocol])) {
            $this->protocolTransfer = self::$_protocolTransferList[$this->$protocol];
        //不是传输层协议,则认为是应用层协议.直接new应用层协议类
        } else {
            $this->protocolApplication = $protocol;
            $this->protocolApplicationClassName = '\FastWS\Core\Protocol\\' . ucfirst($this->protocolApplication);
            if (!class_exists($this->protocolApplicationClassName)) {
                Log::write('Application layer protocol calss not found.', 'FATAL');
            }
        }
        //给实例起名
        $this->instanceName = $this->instanceName ? $this->instanceName : $this->_getBind();
        //创建资源流上下文
        $contextOptionList['socket']['backlog'] = !isset($contextOptionList['socket']['backlog']) ? FASTWS_BACKLOG : $contextOptionList['socket']['backlog'];
        if ($this->_bindProtocol && $this->_bindHost && $this->_bindPort) {
            $this->_streamContext = stream_context_create($contextOptionList);
        }
        //---全局共享信息---
        $this->_instanceId = spl_object_hash($this);
        self::$_instanceList[$this->_instanceId] = $this;
        self::$_instancePidList[$this->_instanceId] = array();
    }

    /**
     * 开始运行FastWS
     * @throws \Exception
     * @return void
     */
    public static function runFastWS()
    {
        //初始化工作
        self::_init();
        //根据启动命令选择是开始\重启\查看状态\结束等
        self::_command();
        //保存主进程id到文件中
        self::_saveMasterPid();
        //启动实例
        self::_createInstance();
        //给主进程安装信号处理函数
        self::_installSignal();
        //检测每个实例,并启动相应数量的子进程
        self::_checkInstanceListProcess();
        self::_displayUI();
        self::_redirectStdinAndStdout();
        self::_monitorChildProcess();
    }

    /**
     * 初始化操作
     */
    private static function _init()
    {
        //添加统计数据
        self::$_statistics['start_time'] = date('Y-m-d H:i:s');
        //给主进程起个名字
        Func::setProcessTitle('FastWS_Master_Process');
        //设置ID
        foreach (self::$_instanceList as $instanceId => $instance) {
            self::$_instancePidList[$instanceId] = array_fill(0, $instance->childProcessCount, 0);
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
        $startFilename = trim($argv[0]);
        $operation = trim($argv[1]);
        $isDomain = isset($argv[2]) && trim($argv[2]) === '-d' ? true : false;
        //获取主进程ID - 用来判断当前进程是否在运行
        $masterPid = false;
        if(file_exists(FASTWS_MASTER_PID_PATH)){
            $masterPid = @file_get_contents(FASTWS_MASTER_PID_PATH);
        }
        //主进程当前是否正在运行
        $masterIsAlive = false;
        //给FastWS主进程发送一个信号, 信号为SIG_DFL, 表示采用默认信号处理程序.如果发送信号成功则该进程正常
        if ($masterPid && @posix_kill($masterPid, SIG_DFL)) {
            $masterIsAlive = true;
        }
        //不能重复启动
        if ($masterIsAlive && $operation === 'start') {
            Log::write('FastWS already running. file: ' . $startFilename, 'FATAL');
        }
        //未启动不能查看状态
        if (!$masterIsAlive && $operation === 'status') {
            Log::write('FastWS no running. file: ' . $startFilename, 'FATAL');
        }
        //未启动不能终止
        if (!$masterIsAlive && $operation === 'stop') {
            Log::write('FastWS no running. file: ' . $startFilename, 'FATAL');
        }
        //根据不同的执行参数执行不同的动作
        switch ($operation) {
            //启动
            case 'start':
                self::_commandStart($isDomain);
                break;
            //停止
            case 'stop':
                self::_commandStop($masterPid);
                break;
            //重启
            case 'restart':
                self::_commandRestart($masterPid, $isDomain);
                break;
            //状态
            case 'status':
                self::_commandStatus($masterPid);
                break;
            //停止所有的FastWS
            case 'kill':
                self::_commandKill($startFilename);
                break;
            //参数不合法
            default:
                Log::write('Parameter error. Usage: php index.php start|stop|restart|status|kill', 'FATAL');
        }
    }

    /**
     * 保存FastWS主进程的Pid
     */
    private static function _saveMasterPid()
    {
        self::$_masterPid = posix_getpid();
        if (false === @file_put_contents(FASTWS_MASTER_PID_PATH, self::$_masterPid)) {
            Log::write('Can\'t write pid to ' . FASTWS_MASTER_PID_PATH, 'FATAL');
        }
    }

    /**
     * 创建实例
     */
    private static function _createInstance()
    {
        foreach (self::$_instanceList as &$instance) {
            //每个实例开始监听
            $instance->_listen();
        }
    }

    /**
     * 监听
     */
    private function _listen()
    {
        //如果没有监听的IP,端口,或者已经建立了socket链接.则不再继续监听
        if (!$this->_bindProtocol || !$this->_bindHost || !$this->_bindPort || $this->_masterSocket) {
            return;
        }
        $listen = $this->protocolTransfer . '://' . $this->_bindHost . ':' . $this->_bindPort;
        $errno = 0;
        $errmsg = '';
        $flags = $this->protocolTransfer === 'tcp' ? STREAM_SERVER_BIND | STREAM_SERVER_LISTEN : STREAM_SERVER_BIND;
        $this->_masterSocket = stream_socket_server($listen, $errno, $errmsg, $flags, $this->_streamContext);
        if (!$this->_masterSocket) {
            Log::write('stream_socket_server() error: errno=' . $errno . ' errmsg=' . $errmsg, 'FATAL');
        }
        //如果是TCP协议,打开长链接,并且禁用Nagle算法,默认为开启Nagle
        //Nagle是收集多个数据包一起发送.再实时交互场景(比如游戏)中,追求高实时性,要求一个包,哪怕再小,也要立即发送给服务端.因此我们禁用Nagle
        if ($this->protocolTransfer === 'tcp' && function_exists('socket_import_stream')) {
            $socket = socket_import_stream($this->_masterSocket);
            @socket_set_option($socket, SOL_SOCKET, SO_KEEPALIVE, 1);
            @socket_set_option($socket, SOL_TCP, TCP_NODELAY, 1);
        }
        //使用非阻塞
        stream_set_blocking($this->_masterSocket, 0);
        //创建一个监听事件
        if (self::$globalEvent) {
            self::$globalEvent->add(array($this, 'acceptTcpConnect'), array(), $this->_masterSocket, EventInterface::EVENT_TYPE_READ);
        }
    }

    /**
     * 注册信号,给信号添加回调函数
     */
    private static function _installSignal()
    {
        //SIGINT为停止FastWS的信号
        pcntl_signal(SIGINT, array('\FastWS\Core\FastWS', 'signalHandler'), false);
        //SIGUSR1 为查看FastWS所有状态的信号
        pcntl_signal(SIGUSR1, array('\FastWS\Core\FastWS', 'signalHandler'), false);
        //SIGPIPE 信号会导致Linux下Socket进程终止.我们忽略他
        pcntl_signal(SIGPIPE, SIG_IGN, false);
    }

    /**
     * 检测每个实例的子进程是否都已启动
     * @return void
     */
    private static function _checkInstanceListProcess()
    {
        foreach (self::$_instanceList as $instance) {
            while (count(self::$_instancePidList[$instance->_instanceId]) < $instance->childProcessCount) {
                self::_forkInstance($instance);
            }
        }
    }

    /**
     * 创建子进程
     * @param $instance object 一个FastWS的实例
     */
    private static function _forkInstance($instance)
    {
        //创建子进程
        $pid = pcntl_fork();
        //初始化的时候$_instancePidList是用0来填充的.这次就是查找到0的位置并且替换它.0表示尚未启动的子进程
        $id = array_search(0, self::$_instancePidList[$instance->_instanceId]);
        //如果是主进程
        if ($pid > 0) {
            self::$_instanceIdList[$instance->_instanceId][$id] = $pid;
            self::$_instancePidList[$instance->_instanceId][$pid] = $pid;
        //如果是子进程
        } elseif ($pid === 0) {
            //启动时重置输入输出
            if (self::$_currentStatus === FASTWS_STATUS_STARTING) {
                self::_redirectStdinAndStdout();
            }
            self::$_instancePidList = array();
            self::$_instanceList = array($instance->_instanceId => $instance);
            Timer::delAll();
            Func::setProcessTitle('FastWS: instance process  ' . $instance->instanceName . ' ' . $instance->_getBind());
            $instance->_setUserAndGroup();
            $instance->id = $id;
            $instance->run();
            exit(250);
        //创建进程失败
        } else {
            Log::write('fork child process failed', 'ERROR');
        }
    }

    /**
     * 运行一个实例进程的一个子进程
     */
    protected function run()
    {
        //设置状态
        self::$_currentStatus = FASTWS_STATUS_RUNING;
        //注册一个退出函数.在任何退出的情况下检测是否由于错误引发的.包括die,exit等都会触发
        register_shutdown_function(array('\FastWS\Core\FastWS', 'checkShutdownErrors'));
        //创建一个全局的循环事件
        if (!self::$globalEvent) {
            $eventPollClass = '\FastWS\Core\Event\\' . ucfirst(self::_chooseEventPoll());
            if (!class_exists($eventPollClass)) {
                Log::write('Event class not exists: ' . $eventPollClass, 'FATAL');
            }
            self::$globalEvent = new $eventPollClass();
            //注册一个读事件的监听.当服务器端的Socket准备读取的时候触发这个事件.
            if ($this->_bindHost && $this->_bindPort) {
                $callbackMethod = $this->protocolTransfer !== 'udp' ? 'acceptConnect' : 'acceptUdpConnect';
                self::$globalEvent->add(array($this, $callbackMethod), array(), $this->_masterSocket, EventInterface::EVENT_TYPE_READ);
            }
            //重新安装信号处理函数
            self::_reinstallSignalHandler();
            //初始化计时器任务,用事件轮询的方式
            Timer::init(self::$globalEvent);
            //执行系统开始启动工作时的回调函数
            if ($this->callbackStart) {
                try {
                    call_user_func($this->callbackStart, $this);
                } catch (\Exception $e) {
                    Log::write('FastWS: execution callback function callbackStart-'.$this->callbackStart . ' throw exception', 'FATAL');
                }
            }
            //开启事件轮询
            self::$globalEvent->loop();
        }
    }

    /**
     * 重新安装信号处理函数 - 子进程重新安装
     */
    private static function _reinstallSignalHandler()
    {
        //设置之前设置的信号处理方式为忽略信号.并且系统调用被打断时不可重启系统调用
        pcntl_signal(SIGINT, SIG_IGN, false);
        pcntl_signal(SIGUSR1, SIG_IGN, false);
        //安装新的信号的处理函数,采用事件轮询的方式
        self::$globalEvent->add(array('\FastWS\Core\FastWS', 'signalHandler'), array(), SIGINT, EventInterface::EVENT_TYPE_SIGNAL);
        self::$globalEvent->add(array('\FastWS\Core\FastWS', 'signalHandler'), array(), SIGUSR1, EventInterface::EVENT_TYPE_SIGNAL);
    }

    /**
     * 检测退出的错误
     */
    public static function checkShutdownErrors()
    {
        Log::write('FastWS check shutdown errors');
        if (self::$_currentStatus != FASTWS_STATUS_SHUTDOWN) {
            $errno = error_get_last();
            if (is_null($errno)) {
                Log::write('FastWS normal exit');
                return;
            }
            Log::write('FastWS unexpectedly quits. last error: ' . json_encode($errno), 'ERROR');
        }
    }

    /**
     * 信号处理函数
     * @param $signal
     */
    public static function signalHandler($signal)
    {
        switch ($signal) {
            case SIGINT:
                self::_stopAll();
                break;
            case SIGUSR1:
                self::_statisticsToFile();
                break;
        }
    }

    /**
     * 显示启动界面
     */
    private static function _displayUI()
    {
        echo "-------------------------- FastWS --------------------------\n";
        echo "FastWS Version: " . FASTWS_VERSION . "      PHP Version: " . PHP_VERSION . "      Pid:".self::$_masterPid . "\n";
        echo "-------------------------- Instances -------------------------\n";
        foreach (self::$_instanceList as $instance) {
            echo $instance->user . '    ' . $instance->group . '    ' . $instance->instanceName . '    ' . $instance->_getBind() . '    ' . $instance->childProcessCount . " processes\n";
        }
        echo "------------------------------------------------------------\n";
        echo "FastWS Start success!\n";
        if (self::$isDaemon) {
            global $argv;
            echo "Input \"php $argv[0] stop\" to quit.\n";

        } else {
            echo "Press Ctrl-C to quit.\n";
        }
    }

    /**
     * 重设标准输入输出
     */
    private static function _redirectStdinAndStdout()
    {
        if (!self::$isDaemon) {
            return false;
        }
        global $STDOUT, $STDERR;
        $handle = fopen(FASTWS_STDOUT_PATH, 'a');
        if ($handle) {
            unset($handle);
            @fclose(STDOUT);
            @fclose(STDERR);
            $STDOUT = fopen(FASTWS_STDOUT_PATH, 'a');
            $STDERR = fopen(FASTWS_STDOUT_PATH, 'a');
        } else {
            Log::write('fopen STDIN AND STDOUT file failed. ' . FASTWS_STDOUT_PATH, 'WARNING');
        }
        return true;
    }

    /**
     * 监控子进程
     */
    private static function _monitorChildProcess()
    {
        self::$_currentStatus = FASTWS_STATUS_RUNING;
        while (true) {
            //调用等待信号的处理器.即收到信号后执行通过pcntl_signal安装的信号处理函数
            pcntl_signal_dispatch();
            //函数刮起当前进程的执行直到一个子进程退出或接收到一个信号要求中断当前进程或调用一个信号处理函数
            $status = 0;
            $pid = pcntl_wait($status, WUNTRACED);
            //再次调用等待信号的处理器.即收到信号后执行通过pcntl_signal安装的信号处理函数
            pcntl_signal_dispatch();

            //如果发生错误或者不是子进程
            if (!$pid || $pid <= 0) {
                //如果是关闭状态 并且 已经没有子进程了 则主进程退出
                if (self::$_currentStatus === FASTWS_STATUS_SHUTDOWN && !self::_getAllInstancePidList()) {
                    self::_exitAndClearAll();
                }
                continue;
            }

            //查找是那个子进程退出
            foreach (self::$_instancePidList as $instanceId => $pidList) {
                if (isset($pidList[$pid])) {
                    $instance = self::$_instanceList[$instanceId];
                    Log::write('FastWS instance(' . $instance->instanceName . ':' . $pid . ') exit. Status: ' . $status, 'WARNING');
                    //记录统计信息.
                    self::$_statistics['instance_exit_info'][$instanceId][$status] = !isset(self::$_statistics['instance_exit_info'][$instanceId][$status]) ? 0 : (self::$_statistics['instance_exit_info'][$instanceId][$status]++);
                    //清除数据
                    unset(self::$_instancePidList[$instanceId][$pid]);
                    $id = array_search($pid, self::$_instanceIdList[$instanceId]);
                    self::$_instanceIdList[$instanceId][$id] = 0;
                    break;
                }
            }

            //如果是停止状态, 并且所有的instance的所有进程都没有pid了.那么就退出所有.即所有的子进程都结束了,就退出主进程
            if (self::$_currentStatus === FASTWS_STATUS_SHUTDOWN && !self::_getAllInstancePidList()) {
                self::_exitAndClearAll();
            //如果不是停止状态,则检测是否需要创建一个新的子进程
            }else if(self::$_currentStatus !== FASTWS_STATUS_SHUTDOWN){
                self::_checkInstanceListProcess();
            }
        }
    }

    /**
     * 退出当前进程
     */
    private static function _exitAndClearAll()
    {
        @unlink(FASTWS_MASTER_PID_PATH);
        Log::write('FastWS has been pulled out', 'WARNING');
        exit();
    }

    /**
     * 设置用户和用户组
     * @return mixed
     */
    private function _setUserAndGroup()
    {
        //获取用户的uid.如果$this->user为空则为当前用户.$this是$_instanceList中存储的对象
        $userInfo = posix_getpwnam($this->user);
        if (!$userInfo || !$userInfo['uid']) {
            Log::write('User ' . $this->user . ' not exsits.', 'ERROR');
            return false;
        }
        $uid = $userInfo['uid'];
        //获取用户组的gid
        if ($this->group) {
            $groupInfo = posix_getgrnam($this->group);
            if (!$groupInfo || !$groupInfo['gid']) {
                Log::write('Group ' . $this->group . ' not exsits.', 'ERROR');
                return false;
            }
            $gid = $groupInfo['gid'];
        } else {
            $gid = $userInfo['gid'];
        }
        //设置用户和用户组
        if ($uid != posix_getuid() || $gid != posix_getgid()) {
            //设置当前进程的uid,gid.并计算用户指定的组访问列表
            if (!posix_initgroups($userInfo['name'], $gid) || !posix_setuid($uid) || !posix_setgid($gid)) {
                Log::write('Change user ' . $this->user . ' or group ' . $this->group . ' failed.', 'ERROR');
            }
        }
        return true;
    }

    /**
     * 获取实例的协议://HOST:端口
     * @return string
     */
    private function _getBind()
    {
        if($this->_bindProtocol && $this->_bindHost && $this->_bindPort){
            $protocol = $this->protocolTransfer;
            $bind = lcfirst($protocol.'://'.$this->_bindHost.':'.$this->_bindPort);
        }
        return isset($bind) ? $bind : '';
    }

    /**
     * 获取事件轮询机制
     * @return string 可用的事件轮询机制
     */
    private static function _chooseEventPoll()
    {
        if (extension_loaded('libevent')) {
            self::$_currentPoll = 'libevent';
        } else if (extension_loaded('ev')) {
            self::$_currentPoll = 'ev';
        } else {
            self::$_currentPoll = 'select';
        }
        return self::$_currentPoll;
    }

    /**
     * 获取所有实例的所有进程的pid
     * @return array
     */
    private static function _getAllInstancePidList()
    {
        $pidList = array();
        foreach (self::$_instancePidList as $pidList) {
            foreach ($pidList as $pid) {
                $pidList[$pid] = $pid;
            }
        }
        return $pidList;
    }

    /**
     * 终止FastWS所有进程
     */
    private static function _stopAll()
    {
        self::$_currentStatus = FASTWS_STATUS_SHUTDOWN;
        if (self::$_masterPid === posix_getpid()) {
            Log::write('FastWS is stopping...', 'WARNING');
            $pidList = self::_getAllInstancePidList();
            foreach ($pidList as $pid) {
                posix_kill($pid, SIGINT);
                Timer::add('posix_kill', array($pid, SIGKILL), FASTWS_KILL_INSTANCE_TIME_INTERVAL, false);
            }
        } else {
            foreach (self::$_instanceList as $instance) {
                $instance->_stop();
            }
            exit();
        }
    }

    private function _stop()
    {
        //执行关闭实例的时候的回调
        if ($this->callbackInstanceStop) {
            try {
                call_user_func($this->callbackInstanceStop, $this);
            } catch (\Exception $e) {
                Log::write('FastWS: execution callback function callbackInstanceStop-'.$this->callbackInstanceStop . ' throw exception', 'FATAL');
            }
        }
        //删除这个实例相关的所有事件监听
        self::$globalEvent->delOne($this->_masterSocket, EventInterface::EVENT_TYPE_READ);
        //关闭资源
        @fclose($this->_masterSocket);
        unset($this->_masterSocket);
    }

    /**
     * 接收链接
     * @param resource $socket Socket资源
     */
    public function acceptConnect($socket)
    {
        //接收一个链接
        $connect = @stream_socket_accept($socket, 0, $peerName);
        //false可能是惊群问题.但是在较新(13年下半年开始)的Linux内核已经解决了此问题.
        if ($connect === false) {
            return;
        }
        //TCP协议链接
        $tcpConnect = new Tcp($connect, $peerName);
        //给Tcp链接对象的属性赋值
        $this->clientList[$tcpConnect->id] = $tcpConnect;
        $tcpConnect->instance = $this;
        $tcpConnect->applicationProtocol = $this->protocolApplication;
        //触发有新链接时的回调函数
        if ($this->callbackConnect) {
            try {
                call_user_func($this->callbackConnect, $tcpConnect);
            } catch (\Exception $e) {
                Log::write('FastWS: execution callback function callbackConnect-'.$this->callbackConnect . ' throw exception', 'FATAL');
            }
        }
    }

    /**
     * 接收链接
     * @param resource $socket Socket资源
     */
    public function acceptUdpConnect($socket)
    {
        Log::write('FastWS currently does not support UDP protocol', 'FATAL');
    }


    /**
     * 将当前信息统计后写入文件中
     */
    private static function _statisticsToFile()
    {
        //如果是子进程来执行本方法. 讲统计信息以追加的方式写入文件.
        if (self::$_masterPid === posix_getpid()){
            //以下为主进程部分
            $loadAvg = sys_getloadavg();
            file_put_contents(FASTWS_STATISTICS_PATH, "---------------------------------------GLOBAL STATUS--------------------------------------------\n");
            file_put_contents(FASTWS_STATISTICS_PATH, 'FastWS Version: ' . FASTWS_VERSION . "          PHP version:".PHP_VERSION."\n", FILE_APPEND);
            file_put_contents(FASTWS_STATISTICS_PATH, 'start time:'. self::$_statistics['start_time'] . '   run ' . floor((time() - strtotime(self::$_statistics['start_time'])) / 86400). ' days ' . floor(((time() - strtotime(self::$_statistics['start_time'])) % 86400) / 3600) . " hours\n", FILE_APPEND);
            $loadStr = 'Load Average: ' . implode(', ', $loadAvg);
            file_put_contents(FASTWS_STATISTICS_PATH, str_pad($loadStr, 33) . '      event_loop: ' . self::_chooseEventPoll() . "\n", FILE_APPEND);
            file_put_contents(FASTWS_STATISTICS_PATH,  count(self::$_instancePidList) . ' instances       ' . count(self::_getAllInstancePidList())." processes\n", FILE_APPEND);
            file_put_contents(FASTWS_STATISTICS_PATH, "instance_name      exit_status      exit_count\n", FILE_APPEND);
            foreach(self::$_instancePidList as $instanceId => $pidList){
                $instance = self::$_instanceList[$instanceId];
                if(isset(self::$_statistics['instance_exit_info'][$instanceId]))
                {
                    foreach(self::$_statistics['instance_exit_info'][$instanceId] as $instanceExitStatus => $instanceExitCount)
                    {
                        file_put_contents(FASTWS_STATISTICS_PATH, $instance->instanceName . '   ' . str_pad($instanceExitStatus, 16) . $instanceExitCount . "\n", FILE_APPEND);
                    }
                }else{
                    file_put_contents(FASTWS_STATISTICS_PATH, $instance->instanceName . '     ' . str_pad(0, 16). " 0\n", FILE_APPEND);
                }
            }
            file_put_contents(FASTWS_STATISTICS_PATH,  "---------------------------------------PROCESS STATUS-------------------------------------------\n", FILE_APPEND);
            file_put_contents(FASTWS_STATISTICS_PATH, "pid\tmemory  listening        instance_name connections ".str_pad('total_request', 13) . ' ' . str_pad('send_fail', 9) . ' ' . str_pad('throw_exception', 15)."\n", FILE_APPEND);

            chmod(FASTWS_STATISTICS_PATH, 0722);

            //主进程做完统计后告诉所有子进程进行统计
            foreach(self::_getAllInstancePidList() as $pid)
            {
                posix_kill($pid, SIGUSR1);
            }
            return;
        }

        //当前的实例
        $instance = current(self::$_instanceList);
        //获取系统分配给PHP的内存,四舍五入到两位小数,单位M
        $statistics = posix_getpid() . "\t";
        $statistics .= str_pad( round( memory_get_usage(true) / (1024*1024), 2) . 'M', 7) . ' ';
        $statistics .= $instance->instanceName . ' ';
        $statistics .= str_pad(ConnectInterface::$statistics['current_connect_count'], 11) . ' ';
        $statistics .= str_pad(ConnectInterface::$statistics['total_connect_count'], 11) . ' ';
        $statistics .= str_pad(ConnectInterface::$statistics['total_request_count'], 11) . ' ';
        $statistics .= str_pad(ConnectInterface::$statistics['total_send_count'], 14) . ' ';
        $statistics .= str_pad(ConnectInterface::$statistics['send_failed_count'], 9) . ' ';
        $statistics .= str_pad(ConnectInterface::$statistics['exception_count'], 15) . "\n";
        file_put_contents(FASTWS_STATISTICS_PATH, $statistics, FILE_APPEND);
        return;
    }

    /**
     * 解析命令 - 启动
     */
    private static function _commandStart($isDomain){
        if ($isDomain) {
            self::$isDaemon = true;
            self::_daemon();
        }
    }

    /**
     * 解析命令 - 停止
     */
    private static function _commandStop($masterPid){
        Log::write('FastWS receives the "stop" instruction, FastWS will graceful stop');
        //给当前正在运行的主进程发送终止信号SIGINT(ctrl+c)
        if ($masterPid) {
            posix_kill($masterPid, SIGINT);
        }
        $nowTime = time();
        $timeout = 5;
        while (true) {
            //主进程是否在运行
            $masterIsAlive = $masterPid && posix_kill($masterPid, SIG_DFL);
            if ($masterIsAlive) {
                //如果超时
                if ((time() - $nowTime) > $timeout) {
                    Log::write('FastWS stop master process failed: timeout ' . $timeout . 's', 'FATAL');
                }
                //等待10毫秒,再次判断是否终止.
                usleep(10000);
                continue;
            }
            break;
        }
        exit();
    }

    /**
     * 解析命令 - 重启
     */
    private static function _commandRestart($masterPid, $isDomain){
        Log::write('FastWS receives the "restart" instruction, FastWS will graceful restart');
        //给当前正在运行的主进程发送终止信号SIGINT(ctrl+c)
        if ($masterPid) {
            posix_kill($masterPid, SIGINT);
        }
        $nowTime = time();
        $timeout = 5;
        while (true) {
            //主进程是否在运行
            $masterIsAlive = $masterPid && posix_kill($masterPid, SIG_DFL);
            if ($masterIsAlive) {
                //如果超时
                if ((time() - $nowTime) > $timeout) {
                    Log::write('FastWS stop master process failed: timeout ' . $timeout . 's', 'FATAL');
                }
                //等待10毫秒,再次判断是否终止.
                usleep(10000);
                continue;
            }
            if ($isDomain === true) {
                self::$isDaemon = true;
            }
            break;
        }
    }

    /**
     * 解析命令 - 强行结束
     */
    private static function _commandKill($startFilename){
        Log::write('FastWS receives the "kill" instruction, FastWS will end the violence');
        exec("ps aux | grep $startFilename | grep -v grep | awk '{print $2}' |xargs kill -SIGINT");
        exec("ps aux | grep $startFilename | grep -v grep | awk '{print $2}' |xargs kill -SIGKILL");
        exit();
    }

    /**
     * 解析命令 - 查看状态
     */
    private static function _commandStatus($masterPid){
        //删除之前的统计文件.忽略可能发生的warning(文件不存在的时候)
        @unlink(FASTWS_STATISTICS_PATH);
        //给正在运行的FastWS的主进程发送SIGUSR1信号,此时主进程收到SIGUSR1信号后会通知子进程将当前状态写入文件当中
        posix_kill($masterPid, SIGUSR1);
        //本进程sleep.目的是等待正在运行的FastWS的子进程完成写入状态文件的操作
        sleep(1);
        //输出状态
        echo @file_get_contents(FASTWS_STATISTICS_PATH);
        exit();
    }

    /**
     * 已守护进程的方式启动FastWS
     */
    private static function _daemon()
    {
        //改变umask
        umask(0);
        //创建一个子进程
        $pid = pcntl_fork();
        //fork失败
        if ($pid === -1) {
            Log::write('FastWS _daemon: fork failed', 'FATAL');
            //父进程
        } else if ($pid > 0) {
            exit();
        }
        //设置子进程为Session leader, 可以脱离终端工作.这是实现daemon的基础
        if (posix_setsid() === -1) {
            Log::write('FastWS _daemon: setsid failed', 'FATAL');
        }
        //再次在开启一个子进程
        //这不是必须的,但通常都这么做,防止获得控制终端.
        $pid = pcntl_fork();
        if ($pid === -1) {
            Log::write('FastWS _daemon: fork2 failed', 'FATAL');
            //将父进程退出
        } else if ($pid !== 0) {
            exit();
        }
    }
}