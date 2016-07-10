<?php
/**
 * 传输层
 * 只接受用户的链接, 不做任何的业务逻辑。
 * 接收用户发送的数据转发给Business层, 再将Business层返回的结果发送给用户
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/6/29
 * Time: 下午3:20
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace MeepoPS\Core\ThreeLayerMould;

class Transfer {
    //confluence的IP
    public $confluenceIp;
    //confluence的端口
    public $confluencePort;

    //和Business通讯的内部IP(Business链接到这个IP)
    public $innerIp = '0.0.0.0';
    //和Business通讯的内部端口(Business链接到这个端口)
    public $innerPort = 19912;
    
    //本类只操作API,不操作$this。因为本类并没有继承MeepoPS
    private $_apiClass;

    public function __construct($apiName, $host, $port, array $contextOptionList=array())
    {
        $this->_apiClass = new $apiName($host, $port, $contextOptionList);
        $this->_apiClass->callbackStartInstance = array($this, 'callbackTransferStartInstance');
        $this->_apiClass->callbackConnect = array($this, 'callbackTransferConnect');
        $this->_apiClass->callbackNewData = array($this, 'callbackTransferNewData');
        $this->_apiClass->callbackConnectClose = array($this, 'callbackTransferConnectClose');
    }

    public function setApiClassProperty($name, $value){
        $this->_apiClass->$name = $value;
    }

    /**
     * 进程启动时, 监听端口, 提供给Business, 同时, 链接到Confluence
     */
    public function callbackTransferStartInstance(){
        //监听一个端口, 用来做内部通讯(Business会链接这个端口)。
        $transferAndConfluence = new TransferAndBusinessService();
        $transferAndConfluence->transferIp = $this->innerIp;
        $transferAndConfluence->transferPort = $this->innerPort;
        $transferAndConfluence->listenBusiness();
        //向中心机(Confluence层)发送自己的地址和端口, 以便Business感知。
        $transferAndConfluence = new TransferAndConfluenceService();
        $transferAndConfluence->transferIp = $this->innerIp;
        $transferAndConfluence->transferPort = $this->innerPort;
        $transferAndConfluence->confluenceIp = $this->confluenceIp;
        $transferAndConfluence->confluencePort = $this->confluencePort;
        $transferAndConfluence->connectConfluence();
    }

    public function callbackTransferConnect(){}
    public function callbackTransferNewData($connect, $data){}
    public function callbackTransferConnectClose($connect){}
}