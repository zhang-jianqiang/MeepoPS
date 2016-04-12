<?php
/**
 * 使用Select方式进行事件轮询
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/3/29
 * Time: 下午5:38
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Core\Event;

use FastWS\Core\Log;

class Select implements EventInterface
{

    //所有事件 - 只有读事件和写事件
    private $_eventList = array();
    //读事件
    private $_readEventList = array();
    //写事件
    private $_writeEventList = array();
    //信号事件
    private $_signalEventList = array();
    //SPL_PRIORITY_QUEUE
    private $_splPriorityQueue = array();
    //计时器事件监听者
    private $_timerEventList = array();
    //计时器任务ID
    private $_timerId = 1;
    //select 超时时间 微妙 默认100秒
    private $_selectTimeout = 100000000;

    /**
     * 初始化
     * EventInterface constructor.
     */
    public function __construct(){
        //创建一个管道, 加入到事件队列中,避免空轮询
        $channel = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        if($channel){
            //设置资源流为非阻塞
            stream_set_blocking($channel[0], 0);
            //加入到读取事件列表
            $this->_readEventList[] = $channel[0];
        }
        //初始化一个队列,
        $this->_splPriorityQueue = new \SplPriorityQueue();
        //设置队列为提取数组包含值和优先级
        $this->_splPriorityQueue->setExtractFlags(\SplPriorityQueue::EXTR_BOTH);
    }

    /**
     * 添加事件
     * @param $callback string|array 回调函数
     * @param $args array 回调函数的参数
     * @param $resource resource|int resource类型为资源(socket链接等), int类型为信号(alarm信号等)或者间隔时间(定时器任务)
     * @param $type int 类型
     * @return bool
     */
    public function add($callback, array $args, $resource, $type){
        switch($type){
            case EventInterface::EVENT_TYPE_READ:
                $uniqueResourceId = (int)($resource);
                $this->_eventList[$uniqueResourceId][$type] = array($callback, $resource);
                $this->_readEventList[$uniqueResourceId] = $resource;
                break;
            case EventInterface::EVENT_TYPE_WRITE:
                $uniqueResourceId = (int)($resource);
                $this->_eventList[$uniqueResourceId][$type] = array($callback, $resource);
                $this->_writeEventList[$uniqueResourceId] = $resource;
                break;
            case EventInterface::EVENT_TYPE_SIGNAL:
                $uniqueResourceId = (int)($resource);
                $this->_signalEventList[$uniqueResourceId][$type] = array($callback, $resource);
                pcntl_signal($resource, array($this, 'signalHandler'));
                break;
            case EventInterface::EVENT_TYPE_TIMER:
            case EventInterface::EVENT_TYPE_TIMER_ONCE:
                //下次运行时间 = 当前时间 + 时间间隔
                $runTime = microtime(true) + $resource;
                //添加到定时器任务
                $this->_timerEventList[$this->_timerId] = array($callback, $args, $resource, $type);
                //入队,优先级大的排在队列的前面,所以传入下次运行时间的负数.
                $this->_splPriorityQueue->insert($this->_timerId, -$runTime);
                $this->_timerId++;
                //执行
                $this->_runTimerEvent();
                break;
            default:
                Log::write('FastWS: Event library Select adds an unknown type: ' . $type, 'ERROR');
                return false;
        }
        return true;
    }

    /**
     * 信号回调函数
     * @param $signal int 信号
     */
    public function signalHandler($signal){
        $signal = (int)($signal);
        call_user_func($this->_signalEventList[$signal][EventInterface::EVENT_TYPE_SIGNAL][0], $signal);
    }

    /**
     * 执行定时器任务
     */
    private function _runTimerEvent()
    {
        //如果队列不为空
        while(!$this->_splPriorityQueue->isEmpty()){
            //查看队列顶部的最高优先级的数据(只看,不出队)
            $data = $this->_splPriorityQueue->top();
            //优先级 - 下次运行时间的负数
            $runTime = -$data['priority'];
            //定时器任务的Id
            $timerId = $data['data'];
            $nowTime = microtime(true);
            //当前时间还没有到下次运行时间
            if($nowTime < $runTime){
                $this->_selectTimeout = ($runTime - $nowTime) * 1000000;
                return;
            }
            //将数据出队
            $this->_splPriorityQueue->extract();
            if(!isset($this->_timerEventList[$timerId])){
                continue;
            }
            //从定时器任务列表中获取本次的任务
            //$task = array($callbackFunc, $callbackArgs, $timeInterval, $type);
            $task = $this->_timerEventList[$timerId];
            //如果是长期的定时器任务.则计算下次执行时间,并入队
            if($task[3] === EventInterface::EVENT_TYPE_TIMER){
                $nextRunTime = $nowTime + $task[2];
                $this->_splPriorityQueue->insert($task, $nextRunTime);
            //如果是一次性定时任务,则从队列列表中删除
            }else if($task[3] === EventInterface::EVENT_TYPE_TIMER_ONCE){
                $this->delOne($timerId, EventInterface::EVENT_TYPE_TIMER_ONCE);
            }
            call_user_func_array($task[0], $task[1]);
            continue;
        }
        $this->_selectTimeout = 100000000;
    }

    /**
     * 删除指定的事件
     * @param $resource int 标识
     * @param $type int 类型
     * @return bool
     */
    public function delOne($resource, $type){
        $uniqueId = (int)($resource);
        switch($type){
            case EventInterface::EVENT_TYPE_READ:
                unset($this->_eventList[$uniqueId][$type]);
                unset($this->_readEventList[$uniqueId]);
                if(empty($this->_eventList[$uniqueId][$type])){
                    unset($this->_eventList[$uniqueId]);
                }
                break;
            case EventInterface::EVENT_TYPE_WRITE:
                unset($this->_eventList[$uniqueId][$type]);
                unset($this->_writeEventList[$uniqueId]);
                if(empty($this->_eventList[$uniqueId])){
                    unset($this->_eventList[$uniqueId]);
                }
                break;
            case EventInterface::EVENT_TYPE_SIGNAL:
                unset($this->_signalEventList[$uniqueId]);
                //将信号设置为忽略信号
                pcntl_signal($resource, SIG_IGN);
                break;
            case EventInterface::EVENT_TYPE_TIMER_ONCE:
            case EventInterface::EVENT_TYPE_TIMER:
                unset($this->_timerEventList[$uniqueId]);
                break;
            default:
                Log::write('FastWS: Event library Select delete an unknown type: ' . $type, 'ERROR');
                return false;
        }
        return true;
    }

    /**
     * 清除所有的计时器事件
     * @return mixed
     */
    public function delAllTimer(){
        //清空队列
        $this->_splPriorityQueue = new \SplPriorityQueue();
        $this->_splPriorityQueue->setExtractFlags(\SplPriorityQueue::EXTR_BOTH);
        //清空计时器任务列表
        $this->_timerEventList = array();
    }

    /**
     * 循环事件
     * @return mixed
     */
    public function loop(){
        $e = null;
        while(true){
            //调用等待信号的处理器.即收到信号后执行通过pcntl_signal安装的信号处理函数
            pcntl_signal_dispatch();
            //已添加的读事件 - 每个元素都是socket资源
            $readList = $this->_readEventList;
            //已添加的写事件 - 每个元素都是socket资源
            $writeList = $this->_writeEventList;
            //监听读写事件列表,如果哪个有变化则发回变化数量.同时引用传入的两个列表将会变化
            var_dump(123);
            var_dump($readList);
            $selectNum = stream_select($readList, $writeList, $e, 0, $this->_selectTimeout);
            var_dump($selectNum);
            var_dump($readList);
            var_dump(456);
//            exit;
            //执行定时器队列
            if(!$this->_splPriorityQueue->isEmpty()){
                $this->_runTimerEvent();
            }
            //如果没有变化的读写事件则开始执行下次等待
            if(!$selectNum){
                continue;
            }
            //处理接收到的读和写请求
            $selectList = array(
                array('type' => EventInterface::EVENT_TYPE_READ, 'data' => $readList),
                array('type' => EventInterface::EVENT_TYPE_WRITE, 'data' => $writeList),
            );
            foreach($selectList as $select){
                foreach($select['data'] as $item){
                    $uniqueId = (int)($item);
                    if(isset($this->_eventList[$uniqueId][$select['type']])){
                        call_user_func($this->_eventList[$uniqueId][$select['type']][0], $this->_eventList[$uniqueId][$select['type']][1]);
                    }
                }
            }
        }
    }
}