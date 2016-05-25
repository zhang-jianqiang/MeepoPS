# 接口对象的回调函数: callbackNewData

- 名称: callbackNewData.
- 类型: function | string | array.
- 参数1: $connect, 类型: object. 传输层协议类的对象, 每个链接都不相同. 例如一个TCP协议类的对象.
- 参数2: $data, 类型: string. 经过协议解析后的数据.
- 描述: 收到新数据时触发该回调函数.       
        
### 示例:
这是我们自行编写的代码: demo.php
```php
<?php
//引入FastWS
require_once 'FastWS/index.php';

//使用文本传输的Telnet接口类
$telnet = new \FastWS\Api\Telnet('0.0.0.0', '19910');

//设置回调函数 - 这是所有应用的业务代码入口 - 您的所有业务代码都编写在这里
//$telnet实例收到新消息时触发callbackNewData所设置的回调函数
$telnetApi->callbackNewData = function($connect, $data){
    var_dump('收到新消息. 链接ID为'.$connect->id.'的用户说'.$data."\n");
};

//启动FastWS
\FastWS\runFastWS();
```