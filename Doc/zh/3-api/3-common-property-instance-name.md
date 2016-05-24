# 接口的通用属性: instanceName

- 名称: instanceName.
- 类型: string.
- 描述: instanceName用来设置这个实例(接口类的对象)的名称.
- 示例: $telnetApi->instanceName = 'FastWS-Telnet'.
- 提示: 设置后在查看运行状态时会比较方便. 比如实例一监听19910端口用来接收游戏聊天数据, 实例二监听19911端口用来接收服务器负载信息, 那么在查看状态的时候我们可能会混淆不同实例的作用.
