<?php
/**
 * 错误码
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/3/24
 * Time: 下午6:18
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Core;

//FastWS错误码 - 发送失败
define('FASTWS_ERROR_CODE_SEND_FAILED', 1);

//FastWS错误码 - 待发送缓冲区已满
define('FASTWS_ERROR_CODE_SEND_BUFFER_FULL', 2);

//FastWS错误码 - 发送链接的socket资源无效
define('FASTWS_ERROR_CODE_SEND_SOCKET_INVALID', 3);