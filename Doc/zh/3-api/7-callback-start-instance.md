# 接口对象的回调函数: callbackStartInstance

- 名称: callbackStartInstance.
- 类型: function | string | array.
- 参数1: $instance, 类型: object. 是刚刚启动的这个实例(接口类的对象).
- 描述: FastWS在启动这个实例时触发该回调函数.

### 示例:
这是我们自行编写的代码: demo.php
```php
<?php
//引入FastWS
require_once 'FastWS/index.php';

//使用文本传输的Telnet接口类
$telnet = new \FastWS\Api\Telnet('0.0.0.0', '19910');

//设置回调函数 - 这是所有应用的业务代码入口 - 您的所有业务代码都编写在这里
//$telnet实例的所有进程启动完毕后会触发callbackStartInstance所设置的回调函数
$telnet->callbackStartInstance = function($instance){
    var_dump('实例'.$instance->instanceName.'已经启动');
};

//启动FastWS
\FastWS\runFastWS();
```