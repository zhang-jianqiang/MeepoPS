# 接口

### 接口是什么
- 接口就是提供给您来继承的类. 我们的主要开发和使用都是围绕着这个类实例化后的对象来使用的. 
- 接口和协议有关, 例如Api/Telnet.php就是使用Telnet协议的接口, Api/WebServer.php就是使用HTTP协议的接口.
- 所有的接口类文件都存放在FastWS/Api/目录下.
- 所有的接口类文件都是FastWS/Core/FastWS.php的子类.
- 所有的接口文件都可以使用FastWS/Core/FastWS.php所提供的非private属性.
- 所有的接口文件的对象都应该(当然, 也可以不)去注册一个回调函数, 我们的所有业务逻辑代码都写在回调函数中.

### 如何使用
- 例如, Telnet.php 接口类文件是收发数据时使用了Telnet协议来解析(提取)数据.
- Telnet.php 由FastWS自动引入, 无需手动引入.
- 使用时只需要new \FastWS\Api\Telnet('0.0.0.0', '19910')即可. 传入的两个参数分别为需要监听的HOST和端口.
- 具体怎么写代码, 请看下面的示例.

### 使用示例:
这是我们自行编写的代码: demo.php
```php
<?php
//引入FastWS
require_once 'FastWS/index.php';

//使用文本传输的Telnet接口类
$telnet19910 = new \FastWS\Api\Telnet('0.0.0.0', '19910');
$telnet19911 = new \FastWS\Api\Telnet('0.0.0.0', '19911');
$telnet19912 = new \FastWS\Api\Telnet('0.0.0.0', '19912');

//启动的子进程数量. 通常为CPU核心数
$telnet19910->childProcessCount = 1;
$telnet19911->childProcessCount = 4;
$telnet19912->childProcessCount = 8;

//设置FastWS实例名称
$telnet19910->instanceName = 'FastWS-Telnet-19910';
$telnet19911->instanceName = 'FastWS-Telnet-19911';
$telnet19912->instanceName = 'FastWS-Telnet-19912';

//设置回调函数 - 这是所有应用的业务代码入口 - 您的所有业务代码都编写在这里
//$telnet19910实例的每个进程在启动完毕时都会触发callbackStartInstance所设置的回调函数
$telnet19910->callbackStartInstance = function($instance){
    var_dump('实例'.$instance->instanceName.'已经启动');
};
//有新链接加入$telnet19910实例的时候会触发callbackConnect所设置的回调函数
$telnet19910->callbackConnect = function($connect){
    var_dump('收到新链接. 链接ID为'.$connect->id."\n");
};
//$telnet19910实例收到新消息的时候会触发callbackNewData所设置的回调函数
$telnet19910->callbackNewData = function($connect, $data){
    var_dump('收到新消息. 链接ID为'.$connect->id.'的用户说'.$data."\n");
};

//启动FastWS, 我们实例化后的三个进程都会启动
\FastWS\runFastWS();

//后面的所有代码都不会执行哦
```
启动FastWS
```bash
php demo.php start
```
启动后查看一下进程吧.
```bash
ps aux | grep php
```