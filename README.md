# FastWS-PHP

## FastWS是Fast WebService的缩写, 旨在提供高校稳定的纯PHP构建的多进程WebService. 可以轻松构建在线实时聊天, 即时游戏, 视频流媒体播放等.

#### 综述:
1. 目前版本为V0.0.2, 可小规模使用.<br>
2. 正在进行大规模\高并发\分布式的测试, 请在V1.0版本开始进行商用.<br>
3. 正式可用分支为Master分支. 其余分支请不要部署在生产环境.<br>
4. 数据交互方式目前仅支持Telnet协议. FastWS计划支持Telnet, HTTP, HTTPS, WebSocket等应用层协议. 事实上HTTP已经在dev分支中,正在调试.<br>
5. PHP不仅仅能依靠Nginx/Apache来构建Web应用, 同时, 也可以构建即时通讯.<br>
6. FastWS的最低运行要求是安装了PHP的PCNTL库.

#### 声明:
1. 绝大多数的PHP应用都部署在Linux服务器. 你可以使用Apple Mac(OS X), CentOS, Ubuntu, Red Hat, Fedora, FreeBSD等类Unix操作系统来启动FastWS.<br>
2. 不支持非Unix操作系统, 如Windows.<br>
3. Windows用户可以安装VirtualBox, Vmware等虚拟机软件来运行FastWS.<br>
4. FastWS需要PHP的POSIX库. POSIX是默认安装的, 通常情况下你不需要手动安装. 如何安装: [This link](http://php.net/manual/zh/posix.installation.php) PHP手册-POSIX安装<br>
5. 多进程及信号处理需要依赖PHP的PCNTL库. FastWS深度依赖PCNTL, 因此PCNTL库是必须安装的, 即使只启动一个进程的FastWS, 仍然需要安装PCNTL. 如何安装: [This link](http://php.net/manual/zh/pcntl.installation.php) PHP手册-PCNTL安装<br>
6. 在大规模访问下, 我们建议安装PHP的PECL扩展Libevent, 但这不是必须的. 在高链接数的场景下, Libevent表现优异. 如何安装: [This link](http://php.net/manual/zh/libevent.installation.php) PHP手册-Libevent安装<br>

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
    2. 如果服务端启动的是HOST是0.0.0.0, 那么客户端可以是外机,可以是本机.本机可以.
    3. 如果服务端启动的是HOST是127.0.0.1/localhost, 那么客户端是不能外机,只能是本机.

#### 客户端使用方法:
###### Telnet:
    客户端可使用telnet客户端.如: telnet 127.0.0.1 19910
###### 编写代码:
    客户端可借助编程语言的Socket来实现. 可参考Test/test_client.php

#### 测试案例:
###### 测试案例一:<br>
使用Select轮训机制, 服务端和客户端同一台服务器, 服务器配置为家用HP Gen8.<br>
一个实例，单进程。对Text协议进行测试。使用PHP模拟Telnet。<br>
测试时间共76314秒。<br>
客户端两个进程,用脚本模拟，一共2个客户端。每个客户端发送100次"hello world\n"。发送完毕后断开连接。<br>
客户端重新链接，然后继续发送100次hello world。再次断开。一直循环。<br>
测试时间共76314秒。每个进程分别链接了6555971次和7218005次。QPS为18049<br>