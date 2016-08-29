# Trident接口的属性
Trident接收传统接口的所有特性外, 还独有一些属性。

### Confluence层相关的属性
- $confluenceIp: Confluence层所监听的IP(0.0.0.0/127.0.0.1/内网IP/外网IP取其一)
- $confluencePort: Confluence层所监听的端口
- $confluenceInnerIp: Confluence层所部署在服务器后监听的IP(建议填写内网IP)
- $confluenceName: 给Confluence层起一个好听的名字

### Transfer层相关属性
- Transfer层实际上就是给传统的接口披了一层外衣。
- 除了下面的特定属性外, 给Transfer层的所有属性与方法的赋值/调用等操作都是在操作传统的接口。
- $transferChildProcessCount

    public $transferName = 'MeepoPS-Trident-Transfer';
    //Transfer回复数据给客户端的时候转码函数
    public $transferEncodeFunction = 'json_encode';
    //Transfer的内网IP和端口, Business要用这个IP和端口链接到Transfer
    public $transferInnerIp = '0.0.0.0';
    public $transferInnerPort = '19912';

    //Business层的相关配置
    public $businessChildProcessCount = 1;
    public $businessName = 'MeepoPS-Trident-Business';

    private $_contextOptionList = array();
    private $_transferApiName = '';
    private $_container = '';

    public static $callbackList = array();
    private $_transferApiPropertyAndMethod = array();

    public static $innerProtocol = 'telnetjson';