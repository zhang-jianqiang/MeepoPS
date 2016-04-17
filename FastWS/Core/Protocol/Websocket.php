<?php
/**
 * 从TCP数据流中解析WebSocket协议
 * Created by lixuan868686@163.com
 * User: lane
 * Date: 16/3/26
 * Time: 下午10:27
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Core\Protocol;

use FastWS\Core\Connect\ConnectInterface;
use FastWS\Core\Log;

class Websocket implements ProtocolInterface {

    //最小的头长度
    const MIN_HEAD_LENGTH = 6;
    //大二进制类型 " "
    const BINARY_TYPE_BLOB = "\x81";
    //大二进制类型 "'"
    const BINARY_TYPE_ARRAYBUFFER = "\x82";

    /**
     * 检测数据, 返回数据包的长度.
     * 没有数据包或者数据包未结束,则返回0
     * @param string $data
     * @param ConnectInterface $connect
     * @return bool|int
     */
    public static function input($data, ConnectInterface $connect)
    {
        //数据长度
        $dataLength = strlen($data);
        //头部分的长度
        $headerLength = self::MIN_HEAD_LENGTH;
        //数据长度小于规定的最小长度(最小是头的长度)
        if($dataLength < $headerLength){
            return 0;
        }
        //是否已经握手 - websocket底层仍旧是HTTP协议.首次建立链接仍旧需要握手
        if(!isset($connect->isHandshake) || !$connect->isHandshake){
            self::_handshake($data, $connect);
            return 0;
        }
        if($connect->websocketCurrentFrameLength){
            //如果帧长度大于本次数据长度.返回0,等待更多的数据.因为数据长度不明确
            if($connect->websocketCurrentFrameLength > $dataLength){
                return 0;
            }
        }else{
            $firstByte = ord($data[0]);
            $messageLength = ord($data[1]) & 127;
            $isFinFrame = $firstByte >> 7;
            $opcode = $firstByte & 0xf;
            switch($opcode){
                case 0x0:
                //Blob
                case 0x1:
                //Arraybuffer
                case 0x2:
                    break;
                //请求关闭链接
                case 0x8:
                    $connect->close();
                    return 0;
                //ping
                case 0x9:
                    //执行Ping时的回调函数
                    if($connect->instance->callbackPing) {
                        try {
                            call_user_func($connect->instance->callbackPing, $connect);
                        } catch (\Exception $e) {
                            Log::write('FastWS: execution callback function callbackPing-'.$connect->instance->callbackPing . ' throw exception', 'FATAL');
                        }
                    }else{
                        $connect->send(pack('H*', '8a00'));
                    }
                    //如果数据中消息的长度为假,则从接收数据缓冲区中删除规定的消息头部分.
                    if(!$messageLength){
                        $connect->substrReadData($headerLength);
                    }
                    break;
                //Pong
                case 0xa:
                    //执行Pong时的回调函数
                    if($connect->instance->callbackPong) {
                        try {
                            call_user_func($connect->instance->callbackPong, $connect);
                        } catch (\Exception $e) {
                            Log::write('FastWS: execution callback function callbackPong-'.$connect->instance->callbackPong . ' throw exception', 'FATAL');
                        }
                    }
                    //如果数据中消息的长度为假,则从接收数据缓冲区中删除规定的消息头部分.
                    if(!$messageLength){
                        $connect->substrReadData($headerLength);
                    }
                    break;
                default :
                    Log::write('opcode ' . $opcode . ' incorrect', 'WARNING');
                    $connect->close();
                    return 0;
            }
            //计算数据包的长度
            if ($messageLength === 126){
                $headerLength = 8;
                if($headerLength > $dataLength){
                    return 0;
                }
                $pack = unpack('ntotal_len', substr($data, 2, 2));
                $messageLength = $pack['total_len'];
            }else if($messageLength === 127){
                $headerLength = 14;
                if($headerLength > $dataLength){
                    return 0;
                }
                $arr = unpack('N2', substr($data, 2, 8));
                $messageLength = $arr[1]*4294967296 + $arr[2];
            }
            $currentFrameLength = $headerLength + $messageLength;
            if($isFinFrame){
                return $currentFrameLength;
            }else{
                $connect->websocketCurrentFrameLength = $currentFrameLength;
            }
        }
        //数据长度 = frame data
        if($dataLength == $connect->websocketCurrentFrameLength) {
            self::decode($data, $connect);
            $connect->substrReadData($connect->websocketCurrentFrameLength);
            $connect->websocketCurrentFrameLength = 0;
            return 0;
        //接收到的数据长度 大于 frame data
        }elseif($dataLength > $connect->websocketCurrentFrameLength){
            self::decode(substr($data, 0, $connect->websocketCurrentFrameLength), $connect);
            $connect->substrReadData($connect->websocketCurrentFrameLength);
            $currentFrameLength = $connect->websocketCurrentFrameLength;
            $connect->websocketCurrentFrameLength = 0;
            //递归 继续解析
            return self::input(substr($data, $currentFrameLength), $connect);
        //接收到的数据长度 小于 frame data
        }else{
            return 0;
        }
    }

    /**
     * 数据编码.在发送数据前调用此方法.
     * @param $data
     * @param ConnectInterface $connect
     * @return string
     */
    public static function encode($data, ConnectInterface $connect)
    {
        $dataLength = strlen($data);
        if(empty($connect->websocketType)){
            $connect->websocketType = self::BINARY_TYPE_BLOB;
        }
        $firstType = $connect->websocketType;
        if($dataLength <= 125){
            $ret = $firstType . chr($dataLength) . $data;
        }else if($dataLength <= 65535){
            $ret = $firstType . chr(126) . pack("n", $dataLength) . $data;
        }else{
            $ret = $firstType . chr(127).pack("xxxxN", $dataLength) . $data;
        }
        //如果握手没有完成.那么把数据加入到缓冲区中,暂缓发送
        if(!isset($connect->isHandshake) || !$connect->isHandshake){
            $connect->tmpWebsocketData .= empty($connect->tmpWebsocketData) ? '' : $ret;
            return '';
        }
        return $ret;
    }

    /**
     * 数据解码.在接收数据前调用此方法
     * @param $data
     * @param ConnectInterface $connect
     * @return string
     */
    public static function decode($data, ConnectInterface $connect)
    {
        $dataLength = ord($data[1]) & 127;
        if($dataLength === 126){
            $masks = substr($data, 4, 4);
            $message = substr($data, 8);
        }else if($dataLength === 127){
            $masks = substr($data, 10, 4);
            $message = substr($data, 14);
        }else{
            $masks = substr($data, 2, 4);
            $message = substr($data, 6);
        }
        $decodeMessage = '';
        for($i=0; $i<strlen($message); $i++) {
            $decodeMessage .= $data[$i] ^ $masks[$i%4];
        }
        if($connect->websocketCurrentFrameLength){
            $connect->websocketDataBuffer .= $decodeMessage;
            return $connect->websocketDataBuffer;
        }else{
            $decodeMessage = $connect->websocketDataBuffer . $decodeMessage;
            $connect->websocketDataBuffer = '';
            return $decodeMessage;
        }
    }

    /**
     * 进行首次建立链接的握手
     * @param string $data
     * @param ConnectInterface $connect
     */
    private static function _handshake($data, $connect)
    {
        //如果是policy-file-request. 在与Flash通信时,三次握手之后客户端会发送一个<policy-file-request>
        if(strpos($data, '<policy') === 0){
            $xml = '<?xml version="1.0"?><cross-domain-policy><site-control permitted-cross-domain-policies="all"/><allow-access-from domain="*" to-ports="*"/></cross-domain-policy>'."\0";
            $connect->send($xml);
            $connect->substrReadData(strlen($data));
            return;
        //其他不支持的请求类型
        }else if(strpos($data, 'GET') !== 0){
            // Bad websocket handshake request.
            $connect->send("HTTP/1.1 400 Bad Request\r\n\r\n<b>400 Bad Request</b><br>Handshake data not supported by WebSocket");
            $connect->close();
            return;
        }
        //开始处理正常的请求.即HTTP协议头的GET请求
        //head和body是用\r\n\r\n来分割的,获取head结束的位置
        $headEndPosition = strpos($data, "\r\n\r\n");
        if(!$headEndPosition){
            return;
        }
        //非贪婪匹配来获取Sec-WebSocket-Key. 如Sec-WebSocket-Key: 12998 5 Y3 1 .P00
        if(preg_match('#Sec-WebSocket-Key: *(.*?)\r\n#i', $data, $match)){
            $secWebsocketKey = $match[1];
        }else{
            $connect->send("HTTP/1.1 400 Bad Request\r\n\r\n<b>400 Bad Request</b><br>Sec-WebSocket-Key not found.<br>This is a WebSocket service and can not be accessed via HTTP.", true);
            $connect->close();
            return;
        }
        //计算Websocket的key
        $key = base64_encode(sha1($secWebsocketKey."258EAFA5-E914-47DA-95CA-C5AB0DC85B11",true));
        //整理需要返回的握手的响应信息
        $response = "HTTP/1.1 101 Switching Protocols\r\n";
        $response .= "WebSocket Protocol Handshake\r\n";
        $response .= "Upgrade: websocket\r\n";
        $response .= "Connection: Upgrade\r\n";
        $response .= "Connection: Upgrade\r\n";
        $response .= "Connection: Upgrade\r\n";
        $response .= "Sec-WebSocket-Version: 13\r\n";
        $response .= "Sec-WebSocket-Accept: $key\r\n\r\n";
        $connect->isHandshake = true;
        $connect->websocketDataBuffer = '';
        $connect->websocketCurrentFrameBuffer = '';
        $connect->websocketCurrentFrameLength = 0;
        //消费掉数据流中握手的部分
        $connect->substrReadData(strlen($data));
        $connect->send($response);
        //等待数据发送
        if(!empty($connect->tmpWebsocketData)){
            $connect->send($connect->tmpWebsocketData);
            $connect->tmpWebsocketData = '';
        }
        //大二进制或arraybuffer类型
        if(empty($connect->websocketType)){
            $connect->websocketType = self::BINARY_TYPE_BLOB;
        }

        //解析HTTP协议头
        self::parseHttpHeader($data);
        return;
    }

    /**
     * 协议HTTP协议头
     * @param $data
     */
    protected static function parseHttpHeader($data)
    {
        $_GET = $_COOKIE = $_SERVER = array();
        $headerList = explode("\r\n", $data);
        list($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER['SERVER_PROTOCOL']) = explode(' ', $headerList[0]);
        unset($headerList[0]);
        foreach($headerList as $header)
        {
            if(empty($header)){
                continue;
            }
            list($key, $value) = explode(':', $header, 2);
            $key = strtolower($key);
            $value = trim($value);
            switch($key){
                // HTTP_HOST
                case 'host':
                    $_SERVER['HTTP_HOST'] = $value;
                    $tmp = explode(':', $value);
                    $_SERVER['SERVER_NAME'] = $tmp[0];
                    if(isset($tmp[1]))
                    {
                        $_SERVER['SERVER_PORT'] = $tmp[1];
                    }
                    break;
                // HTTP_COOKIE
                case 'cookie':
                    $_SERVER['HTTP_COOKIE'] = $value;
                    parse_str(str_replace('; ', '&', $_SERVER['HTTP_COOKIE']), $_COOKIE);
                    break;
                // HTTP_USER_AGENT
                case 'user-agent':
                    $_SERVER['HTTP_USER_AGENT'] = $value;
                    break;
                // HTTP_REFERER
                case 'referer':
                    $_SERVER['HTTP_REFERER'] = $value;
                    break;
                case 'origin':
                    $_SERVER['HTTP_ORIGIN'] = $value;
                    break;
            }
        }
        // QUERY_STRING
        $_SERVER['QUERY_STRING'] = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
        if($_SERVER['QUERY_STRING']){
            parse_str($_SERVER['QUERY_STRING'], $_GET);
        }else{
            $_SERVER['QUERY_STRING'] = '';
        }
    }
}
