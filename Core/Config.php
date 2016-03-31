<?php
/**
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/3/23
 * Time: 下午2:15
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Core;

//时区设置
date_default_timezone_set('PRC');

//Debug true为开启Debug模式
define('FASTWS_DEBUG', true);

//是否立即刷送输出.调用方若没有ob_系列函数则不需要修改此值
define('FASTWS_IMPLICIT_FLUSH', true);

//WebSocket IP
//define('FASTWS_SOCKET_HOST', '127.0.0.1');
//WebSocket 端口
//define('FASTWS_SOCKET_PORT', '19910');
//WebSocket 子进程数  建议使用CPU核数,最大值不超过CPU核数*2
//define('FASTWS_SOCKET_NUM', 4);
//WebSocket 允许的挤压量
//define('FASTWS_SOCKET_BACKLOG', 10);

//读取数据的方式 是否优先读取一个请求.默认为False.更加均衡
//define('FASTWS_PRIORITY_ONE_QUERY', false);

//Log路径
define('FASTWS_LOG_PATH', '/tmp/fast_ws_'.date('Y-m-d').'.log');

//标准输出路径
define('FASTWS_STDOUT_PATH', '/dev/null');

//Pid文件路径
define('FASTWS_MASTER_PID_PATH', '/var/run/fast_ws/fast_ws_master.pid');

//统计信息存储文件路径
define('FASTWS_STATISTICS_PATH', '/var/run/fast_ws/fast_ws_statistics');

//结束正在运行的多个进程时,间隔时间,单位秒
define('FASTWS_KILL_WORKER_TIME_INTERVAL', '2');

//TCP链接中默认最大的待发送缓冲区
define('FASTWS_TCP_CONNECT_DEFAULT_MAX_SEND_BUFFER_SIZE', '1048576');
//TCP链接中所能接收的最大的数据包
define('FASTWS_TCP_CONNECT_MAX_PACKAGE_SIZE', '10485760');