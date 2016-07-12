<?php
/**
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/6/30
 * Time: 下午3:10
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace MeepoPS\Core\ThreeLayerMould;

class MsgTypeConst{
    const MSG_TYPE_PING = 'MEEPO_PS_SYS_INNER_PING';
    const MSG_TYPE_PONG = 'MEEPO_PS_SYS_INNER_PONG';
    const MSG_TYPE_ADD_TRANSFER = 'MEEPO_PS_SYS_INNER_ADD_TRANSFER';
    const MSG_TYPE_ADD_BUSINESS = 'MEEPO_PS_SYS_INNER_ADD_BUSINESS';
    const MSG_TYPE_RESET_TRANSFER_LIST = 'MEEPO_PS_SYS_INNER_RESET_TRANSFER_LIST';
    const MSG_TYPE_APP_MSG = 'MEEPO_PS_APP_MESSAGE';
    //业务功能相关
    const MSG_TYPE_SEND_ALL = 'MEEPO_PS_SEND_ALL';
    const MSG_TYPE_SEND_ONE = 'MEEPO_PS_SEND_ONE';
}