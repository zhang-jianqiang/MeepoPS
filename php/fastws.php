<?php
/**
 * 服务端程序 常住内存
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/3/17
 * Time: 下午2:50
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */

//时区设置
date_default_timezone_set('PRC');

//Debug true为开启Debug模式
define('FAST_WS_DEBUG', true);

//是否立即刷送输出.调用方若没有ob_系列函数则不需要修改此值
define('FAST_WS_IMPLICIT_FLUSH', true);

//WebSocket IP
define('FAST_WS_SOCKET_HOST', '127.0.0.1');
//WebSocket 端口
define('FAST_WS_SOCKET_PORT', '19910');
//WebSocket 子进程数  建议使用CPU核数,最大值不超过CPU核数*2
define('FAST_WS_SOCKET_NUM', 4);
//WebSocket 允许的挤压量
define('FAST_WS_SOCKET_BACKLOG', 10);

//Log路径
define('FAST_WS_LOG_PATH', '/tmp/fast_ws_'.date('Y-m-d').'.log');

//载入FastWS核心文件
require_once './Core/fastws.php';