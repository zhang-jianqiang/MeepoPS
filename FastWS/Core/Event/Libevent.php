<?php
/**
 * 使用Libevent方式进行事件驱动
 * Created by lixuan868686@163.com
 * User: lane
 * Date: 16/3/29
 * Time: 下午5:38
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Core\Event;

class Libevent implements EventInterface{
    /**
     * 初始化
     * EventInterface constructor.
     */
    public function __construct(){
        
    }

    /**
     * 添加事件
     * @param $callback string|array 回调函数
     * @param $args array 回调函数的参数
     * @param $resource resource 资源
     * @param $type int 类型
     * @return mixed
     */
    public function add($callback, array $args, $resource, $type){

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
     * 清除所有的计时器事件
     * @return mixed
     */
    public function delAllTimer(){

    }

    /**
     * 循环事件
     * @return mixed
     */
    public function loop(){

    }
}

