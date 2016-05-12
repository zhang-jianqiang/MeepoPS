# FastWS-PHP
###### FastWS是Fast WebService的缩写. 旨在提供高校稳定的由纯PHP开发的多进程WebService.
###### FastWS可以轻松构建在线实时聊天, 即时游戏, 视频流媒体播放, RPC, 以及原本使用HTTP的接口/定时任务的场景中等. 在下个版本, FastWS的HTTP协议在简单场景下是可以取代Apache/Nginx的.

#### 综述:

1. 目前版本为V0.0.2.<br>
2. 正在进行大规模\高并发\分布式的测试, 可在V1.0版本开始进行商用.<br>
3. 正式可用分支为Master分支. Master分支是至少经过7*24小时的高压力测试, 无任何报错后才会发不到Master分支, 其余分支请不要部署在生产环境.<br>
4. 数据交互协议目前仅支持Telnet协议. FastWS计划支持Telnet, HTTP, HTTPS, WebSocket等应用层协议. 事实上HTTP协议和WebSocket协议已经在dev分支中, 正在调试.<br>
5. PHP作为最好的语言, 不仅仅能依靠Nginx/Apache来构建Web应用, 同时, 也可以构建高效稳定的即时通讯和Socket应用.<br>
6. FastWS的最低运行要求是安装了PHP的PCNTL库.<br>
7. FastWS的定位是一个插件. 不但可以独立运行, 也可以依附与ThinkPHP, CodeIgniter, YII等MVC框架中.<br>

#### 声明:

1. 绝大多数的PHP应用都部署在Linux服务器. 你可以使用Apple Mac(OS X), CentOS, Ubuntu, Red Hat, Fedora, FreeBSD等类Unix操作系统来启动FastWS.<br>
2. 不支持非Unix操作系统, 例如Windows.<br>
3. Windows用户可以安装VirtualBox, Vmware等虚拟机软件来运行FastWS.<br>
4. FastWS需要PHP的POSIX库. POSIX是PHP默认安装的, 通常情况下你不需要手动安装. 如何安装: [PHP手册-POSIX安装](http://php.net/manual/zh/posix.installation.php)<br>
5. 多进程及信号处理需要依赖PHP的PCNTL库. FastWS深度依赖PCNTL, 因此PCNTL库是必须安装的, 即使只启动一个进程的FastWS, 仍然需要安装PCNTL. 如何安装: [PHP手册-PCNTL安装](http://php.net/manual/zh/pcntl.installation.php)<br>
6. 在大规模访问下, 我们建议安装PHP的PECL扩展Libevent, 但这不是必须的. 在高链接数的场景下, Libevent表现优异. 如何安装: [PHP手册-Libevent安装](http://php.net/manual/zh/libevent.installation.php). 截止2016-05-06, PHP官方的Libevent扩展不支持PHP7, PHP7下的Libevent安装方法: [PHP7的Libevent分支](https://github.com/expressif/pecl-event-libevent)<br>
7. 默认监听链接的方式为Select轮询机制. PHP的Select轮询机制最多只能监听1024个链接. 想要突破这个限制, 要么安装Libevent, 要么使用--enable-fd-setsize=2048重新编译安装PHP.<br>

#### 多进程通信:
1. FastWS目前不支持进程间通信. 想要多进程通信需要您自行开发. 但很快, FastWS官方即将推出解决方案.<br>
2. 我们可以启动FastWS的多进程来处理高并发的请求, 但进程间的不能通信仅仅影响了多进程下的聊天应用.<br>
3. 就像Apache/Nginx一样, 使用多进程也只是为了提升单机的处理效率, 每个无状态的请求无论哪个进程来处理, 都是互不影响.<br>

#### 服务端使用方法:

###### 普通终端启动:

    1. 启动: 命令行输入"php demo-text-chat.php start".
    2. 状态: 命令行输入"php demo-text-chat.php status".
    3. 平滑结束: 启动后按下"ctrl + c"即可.
    4. 强行结束: 命令行输入"kill -INT `cat /var/run/fast_ws/fast_ws_master.pid`".

###### 守护进程模式启动:
    1. 启动: 命令行输入"php demo-text-chat.php start -d".
    2. 状态: 命令行输入"php demo-text-chat.php status".
    3. 平滑结束: 命令行输入"php demo-text-chat.php stop".
    4. 强行结束: 命令行输入"php demo-text-chat.php kill".
    5. 强行结束: 命令行输入"kill -INT `cat /var/run/fast_ws/fast_ws_master.pid`".

###### DEMO:
    1. 基于Telnet协议的服务端使用方法请参考demo-text-chat.php.
    2. 如果服务端启动的是HOST是0.0.0.0, 那么客户端可以是外机,可以是本机.本机可以是127.0.0.1, 也可以是localhost.
    3. 如果服务端启动的是HOST是127.0.0.1/localhost, 那么客户端是不能外机,只能是本机.

#### 客户端使用方法:

###### Telnet:
    客户端可使用telnet客户端.如: telnet 127.0.0.1 19910

###### 编写代码:
    客户端可借助编程语言的Socket来实现. 可参考Test/test_client.php

#### 快速入门:

##### 惊鸿一瞥:
  1. FastWS/config.ini是FastWS的配置文件. 采用和php.ini同样的格式, ";"为注释.
  2. 必须引入FastWS/index.php文件. 使用FastWS都是从 require_once 'FastWS/index.php' 开始的.
  3. FastWS/Api/目录下的文件为暴露给用户的接口. 需要实例化接口类文件, FastWS的使用都是围绕实例化接口文件后的对象来操作的. 实例化的时候传入监听的HOST和端口即可.
  4. FastWS会以回调函数的方式来触发您设置的业务逻辑. 比如新链接加入时会回调您设置的"Hello world", 再比如某个链接发送了消息"PING"时, 会回调您设置的返回消息"PONG".
  5. FastWS可以启动多个实例, 每一次的new接口类文件都是一次实例化.
  7. FastWS不但可以实例化多个接口类文件, 也可以实例化同一个接口类文件多次. 比如启动了三个实例, 分别监听了19910, 19911, 19912端口.
  6. 实例化接口类文件并进行了相关设置后, 调用\FastWS\runFastWS()即可启动FastWS.
  7. \FastWS\runFastWS()之后的所有代码都将不会执行.

##### 接口类文件
  1. 所有的接口类文件都存放在FastWS/Api/目录下.
  2. 所有的接口类文件都是FastWS/Core/FastWS.php的子类.
  3. 所有的接口文件都可以设置FastWS/Core/FastWS.php所提供的可设置的属性

##### 接口类的通用属性
  所谓的通用属性, 就是与接口类文件无关. 无论您实例化哪一个接口类文件, 都可以给实例化后的对象设置/调用这些通用属性. 也就是说, 无论已有的Telnet协议, 还是即将加入FastWS豪华套餐的HTTP协议, 在实例化后都可以拥有这些通用属性.

###### 1. 通用属性: childProcessCount
    名称: childProcessCount.
    类型: int.
    描述: childProcessCount用来表示需要多少个子进程来处理这个接口类文件.
    示例: $telnetApi->childProcessCount = 8.
    提示: 启动的子进程数量通常为CPU核心数. 在CPU使用率过高而内存占用较低时, 可适当降低子进程数量. 在内存占用过高而CPU使用率较低时, 可适当增加子进程数量.

###### 2. 通用属性: instanceName
    名称: instanceName.
    类型: string.
    描述: instanceName用来设置这个实例(接口类的对象)的名称.
    示例: $telnetApi->instanceName = 'FastWS-Telnet'.
    提示: 设置后在查看运行状态时会比较方便. 比如实例一监听19910端口用来接收游戏聊天数据, 实例二监听19911端口用来接收服务器负载信息, 那么在查看状态的时候我们可能会混淆不同实例的作用.

###### 3. 通用属性: clientList
    名称: clientList.
    类型: array.
    描述: clientList用来获取当前实例下的当前进程中的所有的客户端链接对象的列表. 数组中每个元素都是一个对象, 是传输层协议类的对象. 例如一个TCP协议类的对象.
    示例:
            global $telnetApi;
            foreach($telnetApi->clientList as $client){
                $client->send('hi');
            }
    提示: 设置后在查看运行状态时会比较方便. 比如实例一监听19910端口用来接收游戏聊天数据, 实例二监听19911端口用来接收服务器负载信息, 那么在查看状态的时候我们可能会混淆不同实例的作用.

###### 4. 通用属性: globalEvent
    名称: globalEvent.
    类型: object.
    描述: globalEvent是事件监听所需要的对象, 默认为Select轮询机制的对象, 如果安装了Libevent, 则是Libevent事件机制的对象.
    示例: 添加一个定时任务, 每一秒打印一个"hello world\n"
        \FastWS\Api\Telnet::$globalEvent->add(function(){
                var_dump("hello\n");
        }, array(), 1, \FastWS\Core\Event\EventInterface::EVENT_TYPE_TIMER);.
    提示: 设置后在查看运行状态时会比较方便. 比如实例一监听19910端口用来接收游戏聊天数据, 实例二监听19911端口用来接收服务器负载信息, 那么在查看状态的时候我们可能会混淆不同实例的作用.

##### 接口类的通用属性之回调函数

###### 1. 通用属性之回调函数: callbackStartInstance
    名称: callbackStartInstance.
    类型: function.
    参数1: $instance, 类型: object. 是刚刚启动的这个实例(接口类的对象).
    描述: FastWS在启动这个实例时触发该回调函数.
    示例:
        $telnetApi->callbackStartInstance = function($instance){
            var_dump($instance);
        };

###### 2. 通用属性之回调函数: callbackConnect
    名称: callbackConnect.
    类型: function.
    参数1: $connect, 类型: object. 传输层协议类的对象, 每个链接都不相同. 例如一个TCP协议类的对象.
    参数: 一个参数,
    描述: 有新的链接加入本实例时触发该回调函数.
    示例:
        $telnetApi->callbackConnect = function($connect){
            var_dump('收到新链接. 链接ID为'.$connect->id."\n");
        };

###### 3. 通用属性之回调函数: callbackNewData
    名称: callbackNewData.
    类型: function.
    参数1: $connect, 类型: object. 传输层协议类的对象, 每个链接都不相同. 例如一个TCP协议类的对象.
    参数2: $data, 类型: string. 经过协议解析后的数据.
    描述: 收到新数据时触发该回调函数.
    示例:
        $telnetApi->callbackNewData = function($connect, $data){
            var_dump('收到新消息. 链接ID为'.$connect->id.'的用户说'.$data."\n");
        };

###### 4. 通用属性之回调函数: callbackInstanceStop
    名称: callbackInstanceStop.
    类型: function.
    参数1: $instance, 类型: object. 是即将停止的这个实例(接口类的对象).
    描述: 实例停止时触发该回调函数.
    示例:
        $telnetApi->callbackInstanceStop = function($instance){
            foreach($instance->clientList as $client){
                $client->send("服务即将停止\n");
            }
        };

###### 5. 通用属性之回调函数: callbackConnectClose
    名称: callbackConnectClose.
    类型: function.
    参数1: $connect, 类型: object. 传输层协议类的对象, 每个链接都不相同. 例如一个TCP协议类的对象.
    描述: 链接断开时出发该回调函数.
    示例:
        $telnetApi->callbackConnectClose = function($connect){
            var_dump('链接ID为'.$connect->id."的用户断开了链接\n");
        };

###### 6. 通用属性之回调函数: callbackError
    名称: callbackError.
    类型: function.
    参数1: $connect, 类型: object. 传输层协议类的对象, 每个链接都不相同. 例如一个TCP协议类的对象.
    参数2: $errCode, 类型: int. 错误码.
    参数3: $errMsg, 类型: string. 错误描述.
    描述: 在链接有错误时触发该回调函数. 例如一个TCP链接, 在缓冲区已满或发送消息失败的时候会触发.
    示例:
        $telnetApi->callbackError = function($connect, $errCode, $errMsg){
            error_log('error code is ' . $errCode . '. error message: ' . $errMsg . '. connect is ' . serialize($connect));
        };

###### 7. 通用属性之回调函数: callbackSendBufferFull
    名称: callbackSendBufferFull.
    类型: function.
    参数1: $connect, 类型: object. 传输层协议类的对象, 每个链接都不相同. 例如一个TCP协议类的对象.
    描述: 待发送缓冲区已经塞满时触发该回调函数. 例如一个TCP链接, 它的待发送缓冲区已经塞满时触发该回调函数.
    示例:
        $telnetApi->callbackSendBufferFull = function($connect){
            error_log('Waiting to send the buffer is full, we should increase the processing efficiency of the. For example, add a server');
        };

###### 8. 通用属性之回调函数: callbackSendBufferEmpty
    名称: callbackSendBufferEmpty.
    类型: function.
    参数1: $connect, 类型: object. 传输层协议类的对象, 每个链接都不相同. 例如一个TCP协议类的对象.
    描述: 缓冲区没有积压时触发该回调函数. 例如一个TCP链接, 缓冲区没有积压时触发该回调函数.
    提示: 本回调函数不会每次缓冲区为空时都会出发. 仅仅会在一次没有发送完, 需要多次发送的时候, 当所有数据都发送完时才会触发.
    示例:
        $telnetApi->callbackSendBufferFull = function($connect){
            var_dump('用户'.$connect->id."的待发送队列已经为空\n");
        };

##### 传输层协议类的对象的属性
  每个链接为一个传输层协议类的对象. 这些属性在FastWS就已经赋好值, 您直接使用即可.

###### 1. 所属实例instance
    名称: instance.
    类型: object.
    描述: 该链接所属的实例对象. 例如一个TCP链接, 它属于Telnet接口类的对象.
    示例: 例如有新链接加入时, 回触发callbackConnect回调函数. 回调函数会接收到一个参数$connect. 直接$connect->instance使用即可.
        $telnetApi->callbackConnect = function($connect){
            foreach($connect->instance->clientList as $client){
                $client->send('新用户'.$client->id.'已经上线了.');
            }
        };

###### 2. 链接id
    名称: id.
    类型: int.
    描述: 每个实例的每个进程, 都有一个唯一的id. 从1开始, 每次增加1.
    示例: 例如有新链接加入时, 回触发callbackConnect回调函数. 回调函数会接收到一个参数$connect. 直接$connect->id使用即可.
        $telnetApi->callbackConnect = function($connect){
            foreach($connect->instance->clientList as $client){
                $client->send('新用户'.$client->id.'已经上线了.');
            }
        };

##### 接口类文件: Telnet
    1. Telnet.php 接口类文件是收发数据时使用了Telnet协议来解析(提取)数据.
    2. Telnet.php 由FastWS自动引入, 无需手动引入.
    3. 使用时只需要new \FastWS\Api\Telnet('0.0.0.0', '19910')即可. 传入的两个参数分别为需要监听的HOST和端口.

#### 测试案例:

##### 进程\Libevent\Select:

###### 测试案例一:<br>
    案例特性: 链接数极少, 但是每个链接的数据发送频率极快.<br>
    服务端和客户端同一台服务器. 服务器为一台物理机(F5). 内存: 64G. CPU: 2个物理CPU, 24个逻辑CPU, 6核心. CPU信息: Intel(R) Xeon(R) CPU E5-2630 v2 @ 2.60GHz. PHP版本为5.6.<br>
    服务端: 一个基于Telnet协议的实例, 监听0.0.0.0:19910. 使用Libevent事件机制.<br>
    客户端: 启动2个客户端, 使用PHP模拟Telnet. 每个客户端都是一个死循环. 每次循环创建一个链接, 执行100次发送"hello world\n"并接收服务器返回信息的任务, 最后断开连接. 测试脚本为Test/test_less_connect_quick_send1(2).php<br>
    1. 服务端一个进程, 测试时间共600秒. CPU占用3.3%, 基本是服务端一个进程所占, 客户端两个进程所耗费可忽略不计. 服务端每个进程占用0.75M内存. 每个客户端进程分别链接了94616次和93703次. QPS为31386. 创建链接,发送消息,接收消息,关闭链接全程无报错.<br>
    2. 服务端三十二个进程, 测试时间共600秒. CPU占用6.0%, 服务端每个进程占用0.75M内存, 每个客户端进程分别链接了130543次和130032次. QPS为43429. 因为客户端请求数较少, 所以只有2个进程属于运行中, 其他进程都是Sleep. 创建链接,发送消息,接收消息,关闭链接全程无报错.<br>

###### 测试案例二:<br>
    案例特性: 链接数较多, 并且每个链接的数据发送频率较快.<br>
    服务端和客户端同一台服务器. 服务器为一台物理机(F5). 内存: 64G. CPU: 2个物理CPU, 24个逻辑CPU, 6核心. CPU信息: Intel(R) Xeon(R) CPU E5-2630 v2 @ 2.60GHz. PHP版本为5.6.<br>
    服务端: 一个基于Telnet协议的实例, 监听0.0.0.0:19910. 使用Libevent事件机制.<br>
    客户端: 启动1000个客户端, 使用PHP模拟Telnet. PHP脚本先创建1000个链接, 随后进入死循环, 每个循环让1000个客户端像服务器发送"hello world\n", 并接收服务器的返回信息. 测试脚本为Test/test_more_connect_quick_send.php<br>
    1. 服务端一个进程, 测试时间共949秒. CPU占用3%, 服务端内存占用各11M. 共发送请求20804000个, 接收回复20804000个. QPS为21922. 创建链接,发送消息,接收消息,关闭链接全程无报错.<br>
    2. 服务端四个进程, 测试时间共670秒. CPU占用3%, 服务端内存占用各14M. 共发送请求13921000个, 接收回复13921000个. QPS为20777. 创建链接,发送消息,接收消息,关闭链接全程无报错.<br>
    2. 服务端三十二个进程, 测试时间共600秒. CPU占用2%,  服务端每个进程占用1M-1.25M内存. 共发送请求10874000个, 接收回复10874000个. QPS为18123. 创建链接,发送消息,接收消息,关闭链接全程无报错.<br>

###### 测试案例三:<br>
    案例特性: 链接数极少, 但是每个链接的数据发送频率极快.<br>
    服务端和客户端同一台服务器.服务器为家用HPGen8(CPU:Intel Celeron G1610T, 内存2G), 虚拟机CentOS7. PHP版本为5.6.<br>
    服务端: 一个基于Telnet协议的实例, 监听0.0.0.0:19910<br>
    客户端: 启动2个客户端, 使用PHP模拟Telnet. 每个客户端都是一个死循环. 每次循环创建一个链接, 执行100次发送"hello world\n"并接收服务器返回信息的任务, 最后断开连接. 测试脚本为Test/test_less_connect_quick_send1(2).php<br>
    1. 服务端一个进程, 使用Select轮询机制: 测试时间共76314秒. CPU一直100%, 服务端一个进程和客户端两个进程, 各占三分之一的CPU. 每个客户端进程分别链接了6555971次和7218005次. QPS为18049. 创建链接,发送消息,接收消息,关闭链接全程无报错.<br>
    2. 服务端一个进程, 使用Libevent事件机制: 测试时间共20136秒. CPU占用70%, 服务端一个进程占45%, 客户端两个进程所耗费各占13%. 每个客户端进程分别链接了1316448次和1329669次. QPS为13141. 创建链接,发送消息,接收消息,关闭链接全程无报错.<br>
    3. 服务端二个进程, 使用Select轮询机制: QPS略低于(1), 因为CPU已经满负荷, 多起进程并不能提升性能, 反而会成为拖累.<br>
    4. 服务端二个进程, 使用Libevent事件机制: 测试时间共42576秒. CPU占用75%, 服务端两个进程占48%, 客户端两个进程所耗费各占27%. 每个客户端进程分别链接了2345267次和2366066次. QPS为11065. 创建链接,发送消息,接收消息,关闭链接全程无报错.<br>

###### 测试案例四:<br>
    案例特性: 链接数较多, 并且每个链接的数据发送频率较快.<br>
    服务端和客户端同一台服务器.服务器为家用HPGen8(CPU:Intel Celeron G1610T, 内存2G), 虚拟机CentOS7. PHP版本为5.6.<br>
    服务端: 一个基于Telnet协议的实例, 监听0.0.0.0:19910.<br>
    客户端: 启动1000个客户端, 使用PHP模拟Telnet. PHP脚本先创建1000个链接, 随后进入死循环, 每个循环让1000个客户端像服务器发送"hello world\n", 并接收服务器的返回信息. 测试脚本为Test/test_more_connect_quick_send.php<br>
    1. 服务端一个进程, 使用Select轮询机制: 测试时间共19943秒. CPU占用53%, 服务端一个进程各占48%, 客户端一个进程所耗费各占5%. 服务端内存占用各11M. 共发送请求17701931个, 接收回复17701931个. QPS为887. 创建链接,发送消息,接收消息,关闭链接全程无报错.<br>
    2. 服务端一个进程, 使用Libevent事件机制: 测试时间共17231秒. CPU占用52%, 服务端一个进程占35%, 客户端一个进程所耗费各占27%. 服务端内存占用7.5M. 共发送请求121732543个, 接收回复121732543个. QPS为7064. 创建链接,发送消息,接收消息,关闭链接全程无报错.<br>
    3. 服务端二个进程, 使用Select轮询机制: 测试时间共32752秒. CPU占用65%, 服务端两个进程各占54%, 客户端一个进程所耗费各占11%. 服务端内存占用各6M. 共发送请求44236944个, 接收回复44236944个. QPS为1305. 创建链接,发送消息,接收消息,关闭链接全程无报错.<br>
    4. 服务端二个进程, 使用Libevent事件机制: 测试时间共10403秒. CPU占用54%, 服务端两个进程各占16%, 客户端一个进程所耗费各占22%. 服务端内存占用各6M. 共发送请求71648828个, 接收回复71648828个. QPS为6887. 创建链接,发送消息,接收消息,关闭链接全程无报错.<br>

结论1: 在CPU已经高负荷或满负荷运行下, 增加进程数并不能增加QPS, 并且可能起反作用.<br>
结论2: 链接数极少的情况下, Libevent的事件机制并不能有效的增加QPS, 系统自带的Select轮询机制可能会更好.<br>
结论3: 链接数极少的情况下, 内存占用也极少.<br>
结论4: 链接数越多, Libevent事件机制的优势越大, 链接数越少, Select轮询机制越占优势.<br>
结论5: 链接数的增加, 会提高内存的占用. 发送消息的频率越高, 会提高CPU的使用率.<br>
