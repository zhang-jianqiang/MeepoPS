<?php
/**
 * DEMO文件. 展示基于Telnet协议的数据传输
 * producer - consumer
 * Created by Lane
 * User: lane
 * Date: 16/4/16
 * Time: 下午10:05
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */

//引入MeepoPS
require_once '../../MeepoPS/index.php';

//使用文本传输的Api类
$webSocket = new \MeepoPS\Api\Websocket('0.0.0.0', '19910');
$webSocket->callbackStartInstance = 'callbackStartInstance';
$webSocket->callbackNewData = 'callbackNewData';
$misFaqMysql = '';
$smsMysql = '';
//启动MeepoPS
\MeepoPS\runMeepoPS();

function callbackStartInstance($instance){
    global $misFaqMysql;
    $misFaqMysql = mysqli_connect('10.115.132.149', 'mis', 'Qihoo360!', 'mis_faq');
    mysqli_query($misFaqMysql, 'set names utf8');
    global $smsMysql;
    $smsMysql = mysqli_connect('10.115.132.149', 'mis', 'Qihoo360!', 'sms');
    mysqli_query($smsMysql, 'set names utf8');
}

function callbackNewData($connect, $data){
    //如果输入手机号和日期, 直接查询记录
    if(preg_match('/^号码(.*)/', $data, $match)){
        list($mobile, $date) = explode('|', $match[1]);
        $message = getMobile($mobile, $date);
    }else if(intval($data)){
        $message = getById($data);
    }else{
        $message = getRelevant($data);
    }
    send($connect, $message);
}

function send($connect, $message){
    $data = array(
        'errcode' => 0,
        'errmsg' => '',
        'data' => array(
            'create_time' => date('Y-m-d H:i:s'),
            'content' => $message,
        ),
    );
    $connect->send(json_encode($data));
}

function getMobile($mobile, $date){
    $message = '没有查询到相关问题';
    global $smsMysql;
    $sql = 'SELECT * FROM sms_msg_' . date('md', strtotime($date)) . ' WHERE mobile="' . $mobile . '"' ;
    $result = mysqli_query($smsMysql, $sql);
    if($result){
        $message = array();
        while($row = mysqli_fetch_assoc($result)){
            if($row){
                $message[] = array('question' => $row['status'].'('.$row['mt_rpt_status'].')', 'question_id' => $row['created_date']);
            }
        }
    }
    return $message;
}

function getById($id){
    $message = array();
    global $misFaqMysql;
    $sql = 'SELECT * FROM robot_question WHERE id=' . $id;
    $result = mysqli_query($misFaqMysql, $sql);
    if($result){
        $row = mysqli_fetch_assoc($result);
        if($row){
            $message = $row['answer'];
        }
    }
    return $message;
}

function getRelevant($data, $fields='app_id,question,answer', $limit=10, $appId=4, $project='rebot-question'){
    $keyword = 'app_id:' . $appId . ' ' . $data;
    $param = array(
        'project' => $project,
        'keyword' => $keyword,
        'fields' => $fields,
        'limit' => $limit,
    );
    $search = curl($param);
    $search = json_decode($search, true);
    $message = array();
    if(!empty($search['data'])){
        if($search['data']){
            foreach($search['data'] as $record){
                $question = $record['field']['question'] . '(匹配度' . $record['percent'] . '%)';
                $questionId = $record['id'];
                $message[] = array('question' => $question, 'question_id' => $questionId);
            }

        }
    }
    return $message;
}
return;

//引入MeepoPS
require_once '../../MeepoPS/index.php';

//使用文本传输的Api类
$telnet = new \MeepoPS\Api\ThreeLayerMould('telnet', '0.0.0.0', '19911');

$telnet->confluenceIp = '0.0.0.0';
$telnet->confluencePort = '19910';
$telnet->confluenceInnerIp = '127.0.0.1';

$telnet->transferInnerIp = '0.0.0.0';
$telnet->transferInnerPort = '19912';
$telnet->transferChildProcessCount = 2;

$telnet->businessChildProcessCount = 3;

$telnet->callbackNewData = function($connect, $data){
    $data = json_decode($data, true);
    if(empty($data['type'])){
        return;
    }
    $data['type'] = strtoupper($data['type']);
     switch($data['type']){
         case 'SEND_ALL':
             if(empty($data['content'])){
                 return;
             }
             $message = '收到群发消息: ' . $data['content'];
             \MeepoPS\Core\ThreeLayerMould\AppBusiness::sendToAll($message);
             break;
         case 'SEND_ONE':
             $message = '收到私聊消息: ' . $data['content'] . '(From: ' . $_SERVER['MEEPO_PS_CLIENT_ID'] . ')';
             $clientId = $data['send_to_one'];
             \MeepoPS\Core\ThreeLayerMould\AppBusiness::sendToOne($message, $clientId);
             break;
         default:
             return;
     }
};

//启动三层模型
$telnet->run();


//Web端
//使用文本传输的Api类
$http = new \MeepoPS\Api\Http('0.0.0.0', '8080');
//启动的子进程数量. 通常为CPU核心数
$http->childProcessCount = 1;
//设置MeepoPS实例名称
$http->instanceName = 'MeepoPS-Http';
$http->setDocument('localhost:8080', '/var/www/MeepoPS/Example/Chat_Robot/layim');

//启动MeepoPS
\MeepoPS\runMeepoPS();



function curl($param){
    $url = 'http://mis.ops.corp.qihoo.net:22777/xunsearch.php';
    if (!empty($param)) {
        $url .= (strpos($url, '?') === false) ? '?' : '&';
        $url .= is_array($param) ? http_build_query($param) : $param;
    }
    $ch = curl_init($url) ;
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ; // 获取数据返回
    return curl_exec($ch);
}
