<?php
/**
 * 业务功能
 * Created by lixuan868686@163.com
 * User: lane
 * Date: 16/7/10
 * Time: 下午10:42
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace MeepoPS\Core\ThreeLayerMould;

class AppBusiness{
    public static $clientList = array();
    public static $businessList = array();
    public static $transferList = array();

    public static function sendToAll($message){
        $message = BusinessAndTransferService::formatMessageToTransfer($message);
        $message['msg_type'] = MsgTypeConst::MSG_TYPE_SEND_ALL;
        foreach(BusinessAndTransferService::$transferList as $transfer){
            $transfer->send($message);
        }
        return true;
    }

    public static function sendToOne($message, $clientId){
        $clientId = Tool::decodeClientId($clientId);
        //选择Transfer
        $transferKey = Tool::encodeTransferAddress($clientId['transfer_ip'], $clientId['transfer_port']);
        if(!isset(BusinessAndTransferService::$transferList[$transferKey])){
            return false;
        }
        $transfer = BusinessAndTransferService::$transferList[$transferKey];
        //整理消息格式
        $message = BusinessAndTransferService::formatMessageToTransfer($message);
        $message['msg_type'] = MsgTypeConst::MSG_TYPE_SEND_ONE;
        $message['to_client_connect_id'] = $clientId['connect_id'];
        $transfer->send($message);
        return true;
    }


}