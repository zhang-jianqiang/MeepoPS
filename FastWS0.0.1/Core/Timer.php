<?php
/**
 * 定时器
 * 监听Alarm信号,并通过Alarm信号来控制每次的执行.即收到Alarm信号后开始执行定时器任务
 * 比如每十秒发送一次Alarm信号,那么每十秒就执行一次定时器任务
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/3/25
 * Time: 下午2:52
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Core;
class Timer{
    //事件
    private static $_event = null;
    //任务列表
    private static $_taskList = null;

    /**
     * 初始化定时器
     * @param array $event
     */
    public static function init($event=array())
    {
        //如果没有事件,则安装一个alarm信号处理器
        $event ? (self::$_event = $event) : (pcntl_signal(SIGALRM, array('FastWS\Core\Timer', 'sigHandler'), false));
    }

    /**
     * 信号处理函数
     */
    public static function sigHandler(){
        if(!self::$_event) {
            //创建一个计时器，每秒向进程发送一个SIGALRM信号。
            pcntl_alarm(1);
            self::execute();
        }
    }

    /**
     * 执行任务
     */
    public static function execute()
    {
        //没有定时任务时,则取消之前创建的alarm信号
        if(empty(self::$_taskList)){
            pcntl_alarm(0);
            return;
        }
        $nowTime = time();
        foreach(self::$_taskList as $startTime => $tasks){
            //当前时间小于启动时间,则不启动该时间段任务
            if($nowTime < $startTime){
                continue;
            }
            //循环该时间段的任务执行任务,开始执行每一个任务
            foreach($tasks as $key=>$task){
                //判断回调函数能否调用
                if(is_callable($task[0])){
                    call_user_func_array($task[0], $task[1]);
                }
                //如果是持续性定时器任务,则添加到下次执行的队伍中
                if($task[3]){
                    self::add($task[0], $task[1], $task[2], $task[3]);
                }
            }
            //删除该时间段需要执行的任务列表
            unset(self::$_taskList[$startTime]);
        }
    }

    /**
     * 添加任务
     * @param $func string|array 回调函数
     * @param $args array 参数
     * @param $intervalSecond int 每次执行的间隔时间,单位秒,必须大于0
     * @param bool|true $isAlways 是否一直执行,默认为true. 一次性任务请传入false
     * @return bool
     */
    public static function add($callback, array $args, $intervalSecond, $isAlways=true){
        if($intervalSecond <= 0 || !is_callable($callback)){
            return false;
        }
        if(self::$_event){
            return self::$_event->add($callback, $args, $intervalSecond, $isAlways ? EventInterface::EVENT_TYPE_TIMER : EventInterface::EVENT_TYPE_TIMER_ONCE);
        }
        if(empty(self::$_taskList)){
            pcntl_alarm(1);
        }
        $startTime = time() + $intervalSecond;
        if(!isset(self::$_taskList[$startTime])){
            self::$_taskList[$startTime] = array();
        }
        self::$_taskList[$startTime][] = array($callback, $args, $intervalSecond, $isAlways);
        return true;
    }

    /**
     * 删除一个定时器
     * @param $timerId
     */
    public static function delOne($timerId)
    {
        if(self::$_event){
            self::$_event->delOne($timerId, EventInterface::EVENT_TYPE_TIMER);
        }
    }

    /**
     * 删除所有的定时器任务
     */
    public static function delAll()
    {
        self::$_taskList = array();
        pcntl_alarm(0);
        if(self::$_event){
            self::$_event->delAll();
        }
    }
}