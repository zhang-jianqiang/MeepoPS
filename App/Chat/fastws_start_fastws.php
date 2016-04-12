<?php
/**
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/3/23
 * Time: 下午2:10
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
$fastws = new \FastWS\Core\FastWS();
// WebServer数量
$fastws->workerCount = 1;
$fastws->name = 'FastWS-NoProtocol';

//$web->user = 'lane';
//$web->group = 'staff';

// 如果不是在根目录启动，则运行runAll方法
//if(!defined('GLOBAL_START'))
//{
//    Worker::runAll();
//}
