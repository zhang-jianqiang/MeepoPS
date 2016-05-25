# 接口类的通用静态属性: globalEvent

- 名称: globalEvent.
- 类型: object.
- 描述: globalEvent是事件监听所需要的对象, 默认为Select轮询机制的对象, 如果安装了Libevent, 则是Libevent事件机制的对象. 在所有的接口对象类中, 它都是一个静态属性
- 备注: static::$globalEvent所代表的对象, 我们这里不做探讨. 在关于事件的章节中会有事件监听对象的详细用法. 这里只要知道有这么一个东西就ok了.
- 警告: 此属性只能使用, 不能修改!

### 示例: 
这是我们自行编写的代码: demo.php
添加一个定时任务, 每一秒打印一个"hello world\n"
```php
<?php
//引入FastWS
require_once 'FastWS/index.php';

//使用文本传输的Telnet接口类
$telnet = new \FastWS\Api\Telnet('0.0.0.0', '19910');

//设置回调函数 - 这是所有应用的业务代码入口 - 您的所有业务代码都编写在这里
$telnet->callbackNewData = 'callbackNewData';

\FastWS\Api\Telnet::$globalEvent->add(function(){
    var_dump("hello\n");
}, array(), 1, \FastWS\Core\Event\EventInterface::EVENT_TYPE_TIMER);.
        
//启动FastWS
\FastWS\runFastWS();
```