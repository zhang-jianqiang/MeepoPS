<?php
/**
 * 协议接口
 * Created by Lane
 * User: lane
 * Date: 16/3/26
 * Time: 下午10:28
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace MeepoPS\Core\Protocol;

interface ProtocolInterface
{

    /**
     * 将输入的内容(包)进行检测.返回包的长度(可以为0,如果为0则等待下个数据包),如果失败返回false并关闭参数中的链接.
     * @param string $data 数据包
     * @return mixed
     */
    public static function input($data);

    /**
     * 对发送的数据进行encode. 例如将数据整理为符合Http/WebSocket/stream(json/text等)等协议的规定
     * @param $data
     * @return mixed
     */
    public static function encode($data);

    /**
     * 对接收到的数据进行decode. 例如将数据按照客户端约定的协议如Http/WebSocket/stream(json/text等)等进行解析
     * 本方法将会触发MeepoPS::$callbackNewData的回调函数
     * @param $data string
     * @return mixed
     */
    public static function decode($data);
}
