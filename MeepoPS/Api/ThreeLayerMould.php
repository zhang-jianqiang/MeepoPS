<?php
/**
 * API - 三层模型
 * Created by Lane
 * User: lane
 * Date: 16/3/23
 * Time: 下午2:12
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace MeepoPS\Api;

use MeepoPS\Core\Log;
use MeepoPS\Core\ThreeLayerMould\Confluence;
use MeepoPS\Core\ThreeLayerMould\Business;
use MeepoPS\Core\ThreeLayerMould\Transfer;

class ThreeLayerMould
{
    public $confluenceHost = '0.0.0.0';
    public $confluencePort = '19910';
    public $confluenceInnerIp = '127.0.0.1';
    public $confluenceChildProcessCount = 1;
    public $confluenceName = 'MeepoPS-ThreeLayerMould-Confluence';
    public $confluencePingInterval = 1;
    public $confluencePingNoResponseLimit = 2;
    private $_confluenceProtocol = 'telnetjson';

    public $transferHost = '0.0.0.0';
    public $transferPort = '19911';
    public $transferChildProcessCount = 1;
    public $transferName = 'MeepoPS-ThreeLayerMould-Transfer';
    public $transferInnerIp = '127.0.0.1';
    public $transferInnerPort = '19912';
    private $_transferInnerProtocol = 'telnetjson';

    public $businessChildProcessCount = 1;
    public $businessName = 'MeepoPS-ThreeLayerMould-Business';

    private $_contextOptionList = array();
    private $_apiName = '';
    private $_container = '';

    /**
     * ThreeLayerMould constructor.
     * @param string $apiName string Api类名
     * @param string $host string 需要监听的地址
     * @param string $port string 需要监听的端口
     * @param array $contextOptionList
     * @param string $container
     */
    public function __construct($apiName, $host, $port, $contextOptionList = array(), $container='')
    {
        $this->_apiName = !$apiName ? '' : '\MeepoPS\Api\\' . ucfirst($apiName);
        $this->transferHost = $host;
        $this->transferPort = $port;
        $this->_container = $container;
        $this->_contextOptionList = $contextOptionList;
        //如果是启动Transfer, 或者全部启动时, 需要判断参数
        if($container !== 'confluence' && $container !== 'business'){
            if (!$apiName || !$host || !$port) {
                Log::write('$apiName and $host and $port can not be empty.', 'FATAL');
            }
            //接口是否存在
            if(!class_exists($this->_apiName)){
                Log::write('Api class not exists. api=' . $apiName, 'FATAL');
            }
        }
        //启动
        $this->_run();
    }

    private function _run(){
        //根据容器选项启动, 如果为空, 则全部启动
        switch(strtolower($this->_container)){
            case 'confluence':
                $this->_initConfluence();
                break;
            case 'transfer':
                $this->_initTransfer();
                break;
            case 'business':
                $this->_initBusiness();
                break;
            default:
                $this->_initConfluence();
                $this->_initTransfer();
                $this->_initBusiness();
                break;
        }
    }

    private function _initConfluence(){
        $confluence = new Confluence($this->_confluenceProtocol, $this->confluenceHost, $this->confluencePort);
        $confluence->childProcessCount = $this->confluenceChildProcessCount;
        $confluence->pingInterval = $this->confluencePingInterval;
        $confluence->pingNoResponseLimit = $this->confluencePingNoResponseLimit;
        $confluence->instanceName = $this->confluenceName;
    }

    private function _initTransfer(){
        $transfer = new Transfer($this->_apiName, $this->transferHost, $this->transferPort, $this->_contextOptionList);
        $transfer->innerIp = $this->transferInnerIp;
        $transfer->innerPort = $this->transferInnerPort;
        $transfer->innerProtocol = $this->_transferInnerProtocol;
        $transfer->confluenceIp = $this->confluenceInnerIp;
        $transfer->confluencePort = $this->confluencePort;
        $transfer->confluenceProtocol = $this->_confluenceProtocol;
        $transfer->setApiClassProperty('childProcessCount', $this->transferChildProcessCount);
        $transfer->setApiClassProperty('instanceName', $this->transferName);
    }

    private function _initBusiness(){
        $business = new Business();
        $business->childProcessCount = $this->businessChildProcessCount;
        $business->instanceName = $this->businessName;
    }
}
