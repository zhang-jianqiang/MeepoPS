# 守护进程模式启动:
如果遇到没有权限的问题, 请使用sudo或切换到root账户

- 启动: 命令行输入
```bash
php demo-text-chat.php start -d
```
- 状态: 命令行输入
```bash
php demo-text-chat.php status
```
- 平滑结束: 命令行输入
```bash
php demo-text-chat.php stop
```
- 强行结束: 命令行输入
```bash
php demo-text-chat.php kill
```
- 强行结束: 命令行输入
```bash
kill -INT `cat /var/run/fast_ws/fast_ws_master.pid`
```