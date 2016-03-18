<?php
/**
 * FastWS框架核心入口文件
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/3/17
 * Time: 下午2:50
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */

//自动载入函数
require 'autoload.php';
\Core\FastWS\Autoloader::register();

//错误报告是否开启
if(FAST_WS_DEBUG){
    error_reporting(E_ALL);
}else{
    error_reporting(0);
}

//开启立即刷新输出
if(FAST_WS_IMPLICIT_FLUSH){
    ob_implicit_flush();
}else{
    ob_implicit_flush(false);
}

//设置脚本执行时间为永不超时
set_time_limit(0);

//初始化WebSocket类
$fastWS = new \FastWS\Core\WebSocket();

return $fastWS;