# 普通模式启动:
如果遇到没有权限的问题, 请使用sudo或切换到root账户

- 启动: 命令行输入
```bash
php demo-text-chat.php start
```
- 查看状态: 命令行输入
```bash
php demo-text-chat.php status
```
- 平滑重启: 命令行输入
```bash
php demo-text-chat.php restart
```
- 平滑结束: 启动后按下`ctrl + c`即可.
- 强行结束: 命令行输入
```bash
kill -INT `cat /var/run/fast_ws/fast_ws_master.pid`
```