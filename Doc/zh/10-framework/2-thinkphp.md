# ThinkPHP和MeepoPS
本页用来说明MeepoPS是如何集成到ThinkPHP中的。以ThinkPHP3.2.3为例。

### MeepoPS如何集成到ThinkPHP

#### 环境说明

- 使用ThinkPHP的项目目录: /var/www/appName/
- MeepoPS目录: /var/www/appName/ThinkPHP/Library/Vendor/MeepoPS
- 使用MeepoPS的机器人客服项目目录: /var/www/appName/RobotChat/
- 启动MeepoPS的代码路径: /var/www/appName/RobotChat/Controller/MeepoPSController.class.php

#### MeepoPSController的代码
```php
<?php
namespace RobotChat\Controller;
use Think\Controller;
class MeepoPSController extends Controller{
    public function start() {
        //这里为什么要处理一下$argv, 请看后面的详细解释
        global $argv;
        $argv[0] = !empty($argv[0]) ? $argv[0].'/'.$argv[1] : '';
        $argv[1] = !empty($argv[2]) ? $argv[2] : '';
        $argv[2] = !empty($argv[3]) ? $argv[3] : '';
        
        //引入MeepoPS/index.php
        vendor("MeepoPS.index");
        
        //-----下面就是大家熟悉的MeepoPS启动代码了-------
        
        //使用WebSocket传输的Api类
        $webSocket = new \MeepoPS\Api\Websocket('0.0.0.0', '19910');
        $webSocket->callbackStartInstance = array('\RobotChat\Service\CallbackService', 'startInstance');
        $webSocket->callbackConnect = array('\RobotChat\Service\CallbackService', 'startInstance');
        $webSocket->callbackNewData = array('\RobotChat\Service\CallbackService', 'newData');
        //启动MeepoPS
        \MeepoPS\runMeepoPS();
    }
}
```

#### 启动MeepoPS

- 进入项目目录: `cd /var/www/appName/`
- 启动MeepoPS: `sudo php index.php RobotChat/MeepoPS/start start`

#### 代码里为什么要处理$argv

$argv是系统变量, 为什么要特殊处理一下?

在MeepoPSController.class.php中, 我们有如下的代码
```php
global $argv;
$argv[0] = !empty($argv[0]) ? $argv[0].'/'.$argv[1] : '';
$argv[1] = !empty($argv[2]) ? $argv[2] : '';
$argv[2] = !empty($argv[3]) ? $argv[3] : '';
```

普通启动时, 启动命令类似于`php demo-telnet.php start`。 MeepoPS需要解析这条命令, 来提取关键信息, 比如"start"、"stop"、"restart"等。

此时MeepoPS获取到的关键词是"start", 通过$argv[1]可以得到。 启动文件名是"demo-telnet.php"。 通过$argv[0]可以得到。

而在ThinkPHP中, 启动命令是`sudo php index.php RobotChat/MeepoPS/start start`, 这时的关键字"start"是$argv[2], 启动文件名"index.php"是$argv[0]和$argv[1]共同获得。

因此, 就需要进行特殊处理了。否则MeepoPS会拿$argv[1], 也就是"RobotChat/MeepoPS/start"去选择执行的命令, 然而MeepoPS发现, 命令不是"start|stop|restart|kill", 就会提示错误。