<?php
/**
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/3/23
 * Time: 下午2:12
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Core;

class WebServer extends FastWS{
    //MIME TYPE
    private static $_defaultMimeType = 'text/html; charset=utf-8';
    //代码文件根目录
    private $_documentRoot = array();
    //MIME TYPE MAP
    private static $mimeTypeMap = array();

    public function __construct($host, $contextOptionList=array())
    {
        if(!$host){
            return false;
        }
        $host = explode(':', $host, 2);
        if(!$host[1]){
            return false;
        }
        $host = 'http:' . $host[1];
        parent::__construct($host, $contextOptionList);

    }

    /**
     * 运行一个WebService实例
     */
    public function run(){
        echo 123;
        $this->callbackConnect = array($this, 'callbackConnect');
        $this->callbackNewData = array($this, 'callbackNewData');
        parent::run();
    }

    /**
     * 设置网站代码目录
     * @param $domain string 域名
     * @param $documentPath string 路径
     */
    public function setRoot($domain, $documentPath)
    {
        if(!$domain){
            Log::write('domain incorrect');
            return false;
        }
        if(!$documentPath){
            Log::write('documentPath incorrect');
            return false;
        }
        if(!file_exists($documentPath)){
            Log::write($documentPath.' no exists');
            return false;
        }
        $this->_documentRoot[$domain] = $documentPath;
    }
}