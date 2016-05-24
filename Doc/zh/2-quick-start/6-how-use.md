# 如何使用

- FastWS/config.ini是FastWS的配置文件. 采用和php.ini同样的格式, ";"为注释.
- 必须引入FastWS/index.php文件. 使用FastWS都是从 require_once 'FastWS/index.php' 开始的.
- /FastWS/Api/目录下的文件为暴露给用户的接口. 需要实例化接口类文件, FastWS的使用都是围绕实例化接口文件后的对象来操作的. 实例化的时候传入监听的HOST和端口即可.
- FastWS会以回调函数的方式来触发您设置的业务逻辑. 比如新链接加入时会回调您设置的"Hello world", 再比如某个链接发送了消息"PING"时, 会触发您设置回调函数来返回消息"PONG".
- FastWS可以启动多个实例, 每一次的new接口类文件都是一次实例化.
- FastWS不但可以实例化多个接口类文件, 也可以实例化同一个接口类文件多次. 比如启动了三个实例, 分别监听了19910, 19911, 19912端口.
- 实例化接口类文件并进行了相关设置后, 调用\FastWS\runFastWS()即可启动FastWS.
- \FastWS\runFastWS()之后的所有代码都将不会执行.