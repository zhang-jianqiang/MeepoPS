<?php
/**
 * 从TCP数据流中解析WebSocket协议
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/3/26
 * Time: 下午10:27
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Core\Protocol;

use FastWS\Core\Connect\ConnectInterface;

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
        $dataLength = strlen($data);
        //数据长度小于规定的最小长度
        if($dataLength < self::MIN_HEAD_LENGTH){
            return 0;
        }
        //是否已经握手 - websocket底层仍旧是HTTP协议.首次建立链接仍旧需要握手
        if(empty($connect->isHandshake)){
            return self::_handshake($data, $connect);
        }
        if($connect->websocketCurrentFrameLength){
            //如果帧长度大于本次数据长度.返回0,等待更多的数据.因为数据长度不明确
            if($connect->websocketCurrentFrameLength > $dataLength){
                return 0;
            }
        }else{
            $data_len = ord($data[1]) & 127;
            $firstbyte = ord($data[0]);
            $is_fin_frame = $firstbyte>>7;
            $opcode = $firstbyte & 0xf;
            switch($opcode)
            {
                case 0x0:
                    break;
                // Blob type.
                case 0x1:
                    break;
                // Arraybuffer type.
                case 0x2:
                    break;
                // Close package.
                case 0x8:
                    // Try to emit onWebSocketClose callback.
                    if(isset($connect->onWebSocketClose))
                    {
                        try
                        {
                            call_user_func($connect->onWebSocketClose, $connect);
                        }
                        catch(\Exception $e)
                        {
                            echo $e;
                            exit(250);
                        }
                    }
                    // Close connection.
                    else
                    {
                        $connect->close();
                    }
                    return 0;
                // Ping package.
                case 0x9:
                    // Try to emit onWebSocketPing callback.
                    if(isset($connect->onWebSocketPing))
                    {
                        try
                        {
                            call_user_func($connect->onWebSocketPing, $connect);
                        }
                        catch(\Exception $e)
                        {
                            echo $e;
                            exit(250);
                        }
                    }
                    // Send pong package to client.
                    else
                    {
                        $connect->send(pack('H*', '8a00'), true);
                    }
                    // Consume data from receive buffer.
                    if(!$data_len)
                    {
                        $connect->consumeRecvBuffer(self::MIN_HEAD_LEN);
                        return 0;
                    }
                    break;
                // Pong package.
                case 0xa:
                    // Try to emit onWebSocketPong callback.
                    if(isset($connect->onWebSocketPong))
                    {
                        try
                        {
                            call_user_func($connect->onWebSocketPong, $connect);
                        }
                        catch(\Exception $e)
                        {
                            echo $e;
                            exit(250);
                        }
                    }
                    //  Consume data from receive buffer.
                    if(!$data_len)
                    {
                        $connect->consumeRecvBuffer(self::MIN_HEAD_LEN);
                        return 0;
                    }
                    break;
                // Wrong opcode.
                default :
                    echo "error opcode $opcode and close websocket connection\n";
                    $connect->close();
                    return 0;
            }

            // Calculate packet length.
            $head_len = self::MIN_HEAD_LEN;
            if ($data_len === 126) {
                $head_len = 8;
                if($head_len > $recv_len)
                {
                    return 0;
                }
                $pack = unpack('ntotal_len', substr($data, 2, 2));
                $data_len = $pack['total_len'];
            } else if ($data_len === 127) {
                $head_len = 14;
                if($head_len > $recv_len)
                {
                    return 0;
                }
                $arr = unpack('N2', substr($data, 2, 8));
                $data_len = $arr[1]*4294967296 + $arr[2];
            }
            $current_frame_length = $head_len + $data_len;
            if($is_fin_frame)
            {
                return $current_frame_length;
            }
            else
            {
                $connect->websocketCurrentFrameLength = $current_frame_length;
            }
        }



        // Received just a frame length data.
        if($connect->websocketCurrentFrameLength == $recv_len)
        {
            self::decode($data, $connect);
            $connect->consumeRecvBuffer($connect->websocketCurrentFrameLength);
            $connect->websocketCurrentFrameLength = 0;
            return 0;
        }
        // The length of the received data is greater than the length of a frame.
        elseif($connect->websocketCurrentFrameLength < $recv_len)
        {
            self::decode(substr($data, 0, $connect->websocketCurrentFrameLength), $connect);
            $connect->consumeRecvBuffer($connect->websocketCurrentFrameLength);
            $current_frame_length = $connect->websocketCurrentFrameLength;
            $connect->websocketCurrentFrameLength = 0;
            // Continue to read next frame.
            return self::input(substr($data, $current_frame_length), $connect);
        }
        // The length of the received data is less than the length of a frame.
        else
        {
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
        return $data."\n";
    }

    /**
     * 数据解码.在接收数据前调用此方法
     * @param $data
     * @param ConnectInterface $connect
     * @return string
     */
    public static function decode($data, ConnectInterface $connect)
    {
        return trim($data);
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
