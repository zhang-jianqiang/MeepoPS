# 采集数据并实时制图(SSH)
采集数据并实时制图是指, 一台服务器作为服务端, 启动real_time_monitor_ssh_server.php. 此时, 同时启动了WebSocket实例和WebServer实例.

WebSocket实例监听19910端口, 使用SSH的方式, 用ssh用户名和密码自动登陆到服务器上, 获取想要监控的指标。本例是仅获取空闲内存(MemFree)。

WebServer实例监听19911端口, 充当Web服务器(替代Apache/Nginx), 访问Web页面时, JS脚本使用WebSocket协议链接MeepoPS的WebSocket实例, 实时获取数据, 并绘制成折线图。

### real_time_monitor_ssh_server.php 是服务端.
使用:
```bash
sudo php real_time_monitor_ssh_server.php start
```
启动. 

守护进程模式启动使用:
```bash
sudo php real_time_monitor_ssh_server.php start -d
```

### WebServer配置
请在config.ini中配置域名和根目录, 示例如下:
```
http_domain_document_list = 'localhost:19910 & /var/www/MeepoPS/Example/Real_Time_Monitor_Ssh/Web/'
```

打开浏览器, 访问http://localhost:19911/?ip=10.115.132.149&ssh_username=lane(请替换)&ssh_password=lane(请替换)

### 友情提示: 
前端断线重链的库:  [ReconnectingWebSocket.js](https://github.com/joewalnes/reconnecting-websocket)

引入后, 只需要将WebSocket("ws://")替换位ReconnectingWebSocket("ws://")即可。