<?php
/**
 * Created by lixuan868686@163.com
 * User: lane
 * Date: 16/3/23
 * Time: 下午2:19
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Core;

$fatalErrorList = array();
$warningErrorList = array();

//Fast要求PHP环境必须大于PHP5.3
if(!substr(PHP_VERSION, 0, 3) >= '5.3') {
    $fatalErrorList[] = "Fatal error: FastWS requires PHP version must be greater than 5.3(contain 5.3). Because FastWS used php-namespace";
}

//FastWS不支持在Windows下运行
if(strpos(strtolower(PHP_OS), 'win') === 0) {
    $fatalErrorList[] = "Fatal error: FastWS not support Windows. Because the required extension is supported only by Linux, such as php-pcntl, php-posix";
}

//FastWS必须运行在命令行下
if(php_sapi_name() != 'cli') {
    $fatalErrorList[] = "Fatal error: FastWS must run in command line!";
}

//是否已经安装PHP-pcntl 扩展
if(!extension_loaded('pcntl')) {
    $fatalErrorList[] = "Fatal error: FastWS must require php-pcntl extension. Because the signal monitor, multi process needs php-pcntl\nPHP manual: http://php.net/manual/zh/intro.pcntl.php";
}

//是否已经安装PHP-posix 扩展
if(!extension_loaded('posix')) {
    $fatalErrorList[] = "Fatal error: FastWS must require php-posix extension. Because send a signal to a process, get the real user ID of the current process needs php-posix\nPHP manual: http://php.net/manual/zh/intro.posix.php";
}

//启动参数是否正确
if(!isset($argv[1]) || !in_array($argv[1], array('start', 'stop', 'restart', 'status', 'kill'))){
    $fatalErrorList[] = "Fatal error: FastWS needs to receive the execution of the operation.\nUsage: php index.php start|stop|restart|status|kill\n\"";
}

//是否设置了启动用户和用户组
if(!FASTWS_START_USER || !FASTWS_START_GROUP){
    $fatalErrorList[] = 'Fatal error: You must set up a startup user and user group.';
}

//如果设置的启动用户不是当前用户,则提示需要root权限
//$currentUser = posix_getpwuid(posix_getuid());
//var_dump($currentUser);
//var_dump(FASTWS_START_USER);
//if ($currentUser !== 'root' && FASTWS_START_USER != $currentUser['name']) {
//    $fatalErrorList[] = 'You must have the root permission to change uid and gid.';
//}

//日志路径是否已经配置
if(!defined('FASTWS_LOG_PATH')){
    $fatalErrorList[] = "Fatal error: Log file path is not defined. Please define FASTWS_LOG_PATH in Config.php";
}else{
    //日志目录是否存在
    if(!file_exists(dirname(FASTWS_LOG_PATH))){
        if(@!mkdir(dirname(FASTWS_LOG_PATH), 0777, true)){
            $fatalErrorList[] = "Fatal error: Log file directory creation failed: " . dirname(FASTWS_LOG_PATH);
        }
    }
    //日志目录是否可写
    if(!is_writable(dirname(FASTWS_LOG_PATH))){
        $fatalErrorList[] = "Fatal error: Log file path not to be written: " . dirname(FASTWS_LOG_PATH);
    }
}

//FastWS主进程Pid文件路径是否已经配置
if(!defined('FASTWS_MASTER_PID_PATH')){
    $fatalErrorList[] = "Fatal error: master pid file path is not defined. Please define FASTWS_MASTER_PID_PATH in Config.php";
}else{
    //FastWS主进程Pid文件目录是否存在
    if(!file_exists(dirname(FASTWS_MASTER_PID_PATH))){
        if(@!mkdir(dirname(FASTWS_MASTER_PID_PATH), 0777, true)){
            $fatalErrorList[] = "Fatal error: master pid file directory creation failed: " . dirname(FASTWS_MASTER_PID_PATH);
        }
    }
    //FastWS主进程Pid文件目录是否可写
    if(!is_writable(dirname(FASTWS_MASTER_PID_PATH))){
        $fatalErrorList[] = "Fatal error: master pid file path not to be written: " . dirname(FASTWS_MASTER_PID_PATH);
    }
}

//标准输出路径是否已经配置
if(!defined('FASTWS_STDOUT_PATH')){
    $warningErrorList[] = "Warning error: standard output file path is not defined. Please define FASTWS_STDOUT_PATH in Config.php";
}else if(FASTWS_STDOUT_PATH !== '/dev/null'){
    //标准输出目录是否存在
    if(!file_exists(dirname(FASTWS_STDOUT_PATH))){
        if(@!mkdir(dirname(FASTWS_STDOUT_PATH), 0777, true)){
            $warningErrorList[] = "Warning error: standard output file directory creation failed: " . dirname(FASTWS_STDOUT_PATH);
        }
    }
    //标准输出目录是否可写
    if(!is_writable(dirname(FASTWS_STDOUT_PATH))){
        $warningErrorList[] = "Warning error: standard output file path not to be written: " . dirname(FASTWS_STDOUT_PATH);
    }
}

//统计信息存储文件路径是否已经配置
if(!defined('FASTWS_STATISTICS_PATH')){
    $warningErrorList[] = "Warning error: statistics file path is not defined. Please define FASTWS_STATISTICS_PATH in Config.php";
}else{
    //统计信息存储文件目录是否存在
    if(!file_exists(dirname(FASTWS_STATISTICS_PATH))){
        if(@!mkdir(dirname(FASTWS_STATISTICS_PATH), 0777, true)){
            $warningErrorList[] = "Warning error: statistics file directory creation failed: " . dirname(FASTWS_STATISTICS_PATH);
        }
    }
    //统计信息存储文件目录是否可写
    if(!is_writable(dirname(FASTWS_STATISTICS_PATH))){
        $warningErrorList[] = "Warning error: statistics file path not to be written: " . dirname(FASTWS_STATISTICS_PATH);
    }
}

if($fatalErrorList){
    $fatalErrorList = implode("\n\n", $fatalErrorList);
    exit($fatalErrorList);
}

if($warningErrorList){
    $warningErrorList = implode("\n\n", $warningErrorList);
    echo $warningErrorList."\n\n";
}

unset($fatalErrorList);
unset($warningErrorList);