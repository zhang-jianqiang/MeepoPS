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

class Select implements EventInterface
{

    //所有事件
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
    private $_task = array();
    //计时器任务ID
    private $_timerId = 1;
    //select 超时时间
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
     * @param $intervalSecond int 间隔
     * @param $type int 类型
     * @return mixed
     */
    public function add($callback, array $args, $intervalSecond, $type){

    }

    /**
     * 删除指定的事件
     * @param $timerId int 标识
     * @param $type int 类型
     * @return mixed
     */
    public function delOne($timerId, $type){

    }

    /**
     * 清除所有的事件
     * @return mixed
     */
    public function delAll(){

    }

    /**
     * 循环事件
     * @return mixed
     */
    public function loop(){

    }
}