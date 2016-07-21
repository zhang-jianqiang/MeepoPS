# ThinkPHP和MeepoPS
本页用来说明MeepoPS是如何集成到ThinkPHP中的。以ThinkPHP3.2.3为例。

### MeepoPS如何集成到ThinkPHP

##### 环境说明

- 使用ThinkPHP的项目目录: /var/www/appName/
- MeepoPS目录: /var/www/appName/ThinkPHP/Library/Vendor/MeepoPS
- 使用MeepoPS的机器人客服项目目录: /var/www/appName/RobotChat/
- 启动MeepoPS的代码路径: /var/www/appName/RobotChat/Controller/MeepoPSController.class.php

##### MeepoPSController的代码
```php
<?php
namespace RobotChat\Controller;
use Think\Controller;
class MeepoPSController extends Controller{
    public function start() {
        //这里为什么要处理一下$argv, 请看后面的详细解释
        global $argv;
        $argv[0] = !empty($argv[0]) ? $argv[0] : '';
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

##### 启动MeepoPS

不一定。MeepoPS是命令行启动的, 启动命令类似于`php demo-telnet.php start`。MeepoPS需要解析这条命令, 来提取关键信息, 比如"start"、"stop"、"restart"等。

提取命令中的信息使用的$argv/$_SERVER['argv']、在这个例子中, 命令是"start", 通过$argv[1]来获取。

在有些框架中, $argv是一个NULL。这就会导致MeepoPS无法启动。

比如在ThinkPHP3.2.3中, 项目部署在/var/www/AppName/Application/目录下有一个目录为"MeepoPSDemo",  有一个类文件是"Application/MeepoPSDemo/MeepoPSController.class.php", 方法名是start()。

此时在命令行启动MeepoPS的命令是`php /var/www/AppName/index.php `