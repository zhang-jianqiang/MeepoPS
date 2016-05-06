# FastWS-PHP

## FastWS是Fast WebService的缩写，旨在提供高校稳定的WebService。本项目所有代码由最好的语言PHP实现。可以轻松构建在线实时聊天、即时游戏、视频流媒体播放等。

#### 综述:

1. 目前版本为V0.0.2, 可小规模使用.<br>
2. 正在进行大规模\高并发\分布式的测试, 请在V1.0版本开始进行商用.<br>
3. 正式可用分支为Master分支. 其余分支请不要部署在生产环境.<br>
4. 数据交互方式目前仅支持Telnet协议. FastWS计划支持Telnet, HTTP, HTTPS, WebSocket等应用层协议. 事实上HTTP已经在dev分支中,正在调试.<br>
5. PHP不仅仅能依靠Nginx/Apache来构建Web应用, 同时, 也可以构建即时通讯.<br>

#### 服务端使用方法:
###### 普通终端启动:
    1. 启动: 命令行输入"php demo-text-chat.php start".<br>
    2. 状态: 命令行输入"php demo-text-chat.php status".<br>
    3. 平滑结束: 启动后按下"ctrl + c"即可.<br>
    4. 强行结束: 命令行输入"kill -INT `cat /var/run/fast_ws/fast_ws_master.pid`".<br>
###### 守护进程模式启动:
    1. 启动: 命令行输入"php demo-text-chat.php start -d".<br>
    2. 状态: 命令行输入"php demo-text-chat.php status".<br>
    3. 平滑结束: 命令行输入"php demo-text-chat.php stop".<br>
    4. 强行结束: 命令行输入"php demo-text-chat.php kill".<br>
    5. 强行结束: 命令行输入"kill -INT `cat /var/run/fast_ws/fast_ws_master.pid`".<br>
###### DEMO:
    1. 基于Telnet协议的服务端使用方法请参考demo-text-chat.php.<br>
    2. 如果服务端启动的是HOST是0.0.0.0, 那么客户端可以是外机,可以是本机.本机可以.<br>
    3. 如果服务端启动的是HOST是127.0.0.1/localhost, 那么客户端是不能外机,只能是本机.<br>

#### 客户端使用方法:

1. 客户端可使用telnet客户端.如: telnet 127.0.0.1 19910
2. 客户端可借助编程语言的Socket来实现 可参考Test/test_client.php

#### 测试案例:

###### 测试案例一:<br>
使用Select轮训机制, 服务端和客户端同一台服务器, 服务器配置为家用HP Gen8.<br>
一个实例，单进程。对Text协议进行测试。使用PHP模拟Telnet。<br>
测试时间共76314秒。<br>
客户端两个进程,用脚本模拟，一共2个客户端。每个客户端发送100次"hello world\n"。发送完毕后断开连接。<br>
客户端重新链接，然后继续发送100次hello world。再次断开。一直循环。<br>
测试时间共76314秒。每个进程分别链接了6555971次和7218005次。QPS为18049<br>