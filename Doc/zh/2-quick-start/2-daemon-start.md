# 守护进程模式启动:
如果遇到没有权限的问题, 请使用sudo或切换到root账户

- 启动: 命令行输入
```bash
php demo-telnet.php start -d
```
- 查看状态: 命令行输入
```bash
php demo-telnet.php status
```
- 平滑重启: 命令行输入
```bash
php demo-telnet.php restart
```
- 平滑结束: 命令行输入
```bash
php demo-telnet.php stop
```
- 强行结束: 命令行输入
```bash
php demo-telnet.php kill
```
- 强行结束: 命令行输入
```bash
kill -INT `cat /var/run/fast_ws/fast_ws_master.pid`
```