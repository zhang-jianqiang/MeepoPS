# 概述:
PHP作为最好的语言, 不仅仅能依靠Nginx/Apache来构建Web应用, 同时, 也可以构建高效稳定的即时通讯和Socket应用.

### 现状
- 目前版本为V0.0.2.
- 原则上要求PHP版本为5.3以上.
- 正式可用分支为Master分支. Master分支是至少经过7*24小时的高压力测试, 无任何报错后才会发布到Master分支, 其余分支请不要部署在生产环境.
- 数据交互协议目前仅支持Telnet协议. FastWS计划支持Telnet, HTTP, HTTPS, WebSocket等应用层协议. 事实上HTTP协议和WebSocket协议已经在dev分支中, 正在调试.
- FastWS的最低运行要求是安装了PHP的PCNTL库.
- FastWS的定位是一个插件. 不但可以独立运行, 也可以依附与ThinkPHP, CodeIgniter, YII等MVC框架中.

### 天地初开
FastWS的诞生是因为两个需求:
- 为了使用基于可靠链接TCP协议的Socket通信, 借用Socket长链接的特性, 我们可以实时监控服务器的CPU, 内存, 负载等状态.
- 服务端接口程序使用HTTP协议效率较低, 便采用Socket监听本地IP和端口, 接收客户端的数据. 这样可以将Mysql链接常驻内存, 不需要每个请求链接一次Mysql, 将接口业务逻辑从MVC笨重的身躯中剥离.
最后, 小巧精悍的FastWS最初版诞生了, 这是FastWS的最初版本.
  
### 重复的轮子
市面上, 有鸟哥在10年所写的Mpass, PHP的C扩展Swoole以及Workerman. 可是, 它们对我们来说，都或多或少有些不可接受的问题. 比如Mpass略显复古, 并且鸟哥也说你们别指望它=.=, Swoole过于简陋的文档和容错率较低, Workerman的代码不够优雅等等. 我们决定手写一套.
尽管我们重新开发也会有无数的问题和BUG, 甚至远不如他们. 但是程序猿就喜欢重构别人的项目, 然后再把自己的烂摊子留给后人, 这大概也能叫情怀吧.

### 巨人的肩膀
FastWS诞生后, 我们借鉴了很多优秀的开源项目, 比如之前提到的Mpass, Swoole, Workerman. 它们真的是非常优秀的项目, 尤其是Mpass, Workerman, 他们打开了PHP工程师的一扇新窗口, 原来PHP也可以做这些事情, 因此它们是先辈, 是巨人.
我们通读了Mpass和Workerman的源码之后, 深受启发, 因此在FatWS功能渐丰并且开源后的版本, 或多或少都有前辈们的影子.