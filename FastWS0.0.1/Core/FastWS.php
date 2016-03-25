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
        self::init();
//        self::parseCommand();
//        self::daemonize();
//        self::initWorkers();
//        self::installSignal();
//        self::saveMasterPid();
//        self::forkWorkers();
//        self::displayUI();
//        self::resetStd();
//        self::monitorWorkers();
    }

    public static function init()
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




    private static function getPollWay()
    {
        array('libevent', 'select');
    }
}