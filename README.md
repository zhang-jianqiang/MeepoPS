# FastWS-PHP
FastWS是Fast WebSocke的缩写，志在提供稳定的WebSocket服务。本项目为PHP的语言实现。可以轻松构建在线实时聊天、即时游戏、视频流媒体播放等。

版本V0.0.1 目前可用.(Master分支和Tagv0.0.1)

dev分支为开发分支, 不推荐下载.

使用方法请参考demo-text-chat.php

客户端使用Telnet


使用Select轮训机制
一个实例，都是单进程。对Text协议进行测试。使用PHP模拟Telnet。
测试时间共76314秒。
两个进程模拟客户端，每个链接发送"hello world\n"100次。在测试期间内，每个进程分别链接了6555971次和7218005次。QPS为18049