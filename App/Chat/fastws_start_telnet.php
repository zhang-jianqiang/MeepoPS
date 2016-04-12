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
// TelnetServer
$telnet = new \FastWS\Core\TelnetServer("text://0.0.0.0:19910");
// TelnetServer数量
$telnet->workerCount = 1;
$telnet->name = 'FastWS-Text';

//$web->user = 'lane';
//$web->group = 'staff';