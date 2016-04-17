<?php
/**
 * 协议接口
 * Created by lixuan868686@163.com
 * User: lane
 * Date: 16/3/26
 * Time: 下午10:28
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Core\Protocol;
use FastWS\Core\Connect\ConnectInterface;

interface ProtocolInterface{

    /**
     * 将输入的内容(包)进行检测.返回包的长度(可以为0,如果为0则等待下个数据包),如果失败返回false并关闭参数中的链接.
     * @param string $data 数据包
     * @param ConnectInterface $connect 链接,继承ConnectInterface的类对象
     * @return mixed
     */
    public static function input($data, ConnectInterface $connect);

    /**
     * 对发送的数据进行encode. 例如将数据整理为符合Http/WebSocket/stream(json/text等)等协议的规定
     * @param $data
     * @param ConnectInterface $connect
     * @return mixed
     */
    public static function encode($data, ConnectInterface $connect);

    /**
     * 对接收到的数据进行decode. 例如将数据按照客户端约定的协议如Http/WebSocket/stream(json/text等)等进行解析
     * 本方法将会触发FastWS::$callbackNewData的回调函数
     * @param $data string
     * @param ConnectInterface $connect
     * @return mixed
     */
    public static function decode($data, ConnectInterface $connect);
}
