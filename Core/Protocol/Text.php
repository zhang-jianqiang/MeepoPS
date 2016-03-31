<?php
/**
 * 从TCP数据流中解析Text协议
 * 客户端为telnet方式
 * 每个数据包已\n来结尾.如果发现\n,则\n之前为一个数据包.如果没有\n,则等待下次数据的到来
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/3/26
 * Time: 下午10:27
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Core\Protocol;

use FastWS\Core\Connect\ConnectInterface;
use FastWS\Core\Connect\Tcp;

class Text implements ProtocolInterface {
    /**
     * 检测数据, 返回数据包的长度.
     * 没有数据包或者数据包未结束,则返回0
     * @param string $data
     * @param ConnectInterface $connect
     * @return bool|int
     */
    public static function input($data, ConnectInterface $connect)
    {
        //如果数据量超过所能接受的最大限制,则关闭这个链接.结束本方法
        if(strlen($data) > FASTWS_TCP_CONNECT_MAX_PACKAGE_SIZE){
            $connect->close();
            return 0;
        }
        $position = strpos($data, "\n");
        //如果没有,则暂时不处理本次请求
        if($position === false){
            return 0;
        }
        //返回数据包的长度. 因为计数从0开始,所以返回时+1
        return $position + 1;
    }

    /**
     * 数据编码.在发送数据前调用此方法.
     * @param $data
     * @param Tcp $connect
     * @return string
     */
    public static function encode($data, ConnectInterface $connect)
    {
        return $data."\n";
    }

    /**
     * 数据解码.在接收数据前调用此方法
     * @param $data
     * @param Tcp $connect
     */
    public static function decode($data, ConnectInterface $connect)
    {
        return trim($data);
    }
}
