<?php
/**
 * run with command 
 * php index.php start
 */
namespace FastWS;

use FastWS\Core\FastWS;

//xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);

//载入FastWS配置文件
require_once __DIR__ . '/Core/Config.php';

//环境检测
require_once __DIR__ . '/Core/CheckEnv.php';

//载入FastWS核心文件
require_once __DIR__ . '/Core/Init.php';

// 加载所有Applications/*/start.php，以便启动所有服务
foreach(glob(FASTWS_ROOT_PATH.'/App/*/fastws_start_*.php') as $appStartFile)
{
    require_once $appStartFile;
}

// 运行所有服务
FastWS::runAll();
