<?php
/**
 * DEMO文件. 展示基于WebSocket协议的机器人聊天
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

//使用WebSocket协议传输的Api类
$webSocket = new \MeepoPS\Api\Websocket('0.0.0.0', '19910');
$webSocket->callbackStartInstance = 'callbackStartInstance';
$webSocket->callbackNewData = 'callbackNewData';
$mysql1 = '';
$mysql2 = '';
//启动MeepoPS
\MeepoPS\runMeepoPS();

function callbackStartInstance($instance){
    global $mysql1;
    $mysql1 = mysqli_connect('127.0.0.1', 'meepops', 'MeepoPS', 'databases1');
    mysqli_query($mysql1, 'set names utf8');
    global $mysql2;
    $mysql2 = mysqli_connect('127.0.0.1', 'meepops', 'MeepoPS', 'databases2');
    mysqli_query($mysql2, 'set names utf8');
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
    global $mysql2;
    $sql = 'SELECT * FROM order' . date('md', strtotime($date)) . ' WHERE mobile="' . $mobile . '"' ;
    $result = mysqli_query($mysql2, $sql);
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
    global $mysql1;
    $sql = 'SELECT * FROM robot_question WHERE id=' . $id;
    $result = mysqli_query($mysql1, $sql);
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

function curl($param){
    $url = 'http://www.lanecn.com:22777/xunsearch.php';
    if (!empty($param)) {
        $url .= (strpos($url, '?') === false) ? '?' : '&';
        $url .= is_array($param) ? http_build_query($param) : $param;
    }
    $ch = curl_init($url) ;
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ; // 获取数据返回
    return curl_exec($ch);
}
