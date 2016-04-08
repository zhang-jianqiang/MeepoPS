<?php
/**
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/3/23
 * Time: 下午2:10
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
return;
// WebServer
$telnet = new \FastWS\Core\TelnetServer("text://0.0.0.0:19910");
// WebServer数量
$telnet->workerCount = 1;
// 设置站点根目录
//$web->setRoot('www.lanecn.com', __DIR__.'/Web');
$telnet->name = 'FastWS-Text';

//$web->user = 'lane';
//$web->group = 'staff';

// 如果不是在根目录启动，则运行runAll方法
//if(!defined('GLOBAL_START'))
//{
//    Worker::runAll();
//}
