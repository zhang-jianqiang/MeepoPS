<?php
/**
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/3/23
 * Time: 下午2:10
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */

// WebServer
$web = new \FastWS\Core\WebServer("http://0.0.0.0:55151");
// WebServer数量
$web->count = 2;
// 设置站点根目录
$web->addRoot('www.your_domain.com', __DIR__.'/Web');

// 如果不是在根目录启动，则运行runAll方法
//if(!defined('GLOBAL_START'))
//{
//    Worker::runAll();
//}