<?php
/**
 * API - HTTP协议
 * Created by lixuan868686@163.com
 * User: lane
 * Date: 16/3/23
 * Time: 下午2:12
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Api;

use FastWS\Core\FastWS;
use FastWS\Core\Log;
use FastWS\Core\Protocol\Http;
use FastWS\Core\Protocol\HttpCache;

class WebServer extends FastWS{

    //默认页文件名
    public $defaultIndex = array('index.html', 'index.php');

    //MIME TYPE
    private static $_defaultMimeType = 'text/html; charset=utf-8';
    //代码文件根目录
    private $_documentRoot = array();
    //后缀和MimeType的对应关系 array('ext1'=>'mime_type1', 'ext2'=>'mime_type2')
    private static $mimeTypeMap = array();

    private $_callerCallbackStart = '';
    private $_callerCallbackNewData = '';

    /**
     * WebServer constructor.
     * @param string $protocol string 协议,默认为Telnet
     * @param string $host string 需要监听的地址
     * @param string $port string 需要监听的端口
     * @param array $contextOptionList
     */
    public function __construct($protocol, $host, $port, $contextOptionList=array())
    {
        if(!$protocol || !$host || !$port){
            return;
        }
        parent::__construct($protocol, $host, $port, $contextOptionList);
    }

    /**
     * 运行一个WebService实例
     */
    public function run(){
        if(empty($this->_documentRoot)){
            Log::write('not set document root.', 'ERROR');
        }
        //调用者所设置的回调放置在_callerCallback系列的属性中
        $this->_callerCallbackNewData = $this->callbackNewData;
        $this->_callerCallbackStart = $this->callbackStart;
        //设置FastWS的回调.
        $this->callbackStart = array($this, 'callbackStart');
        $this->callbackNewData = array($this, 'callbackNewData');
        //运行FastWS
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
            return;
        }
        if(!$documentPath){
            Log::write('documentPath incorrect');
            return;
        }
        if(!file_exists($documentPath)){
            Log::write($documentPath.' no exists');
            return;
        }
        $this->_documentRoot[$domain] = $documentPath;
    }

    /**
     * 回调.开始运行时
     */
    public function callbackStart(){
        // Init HttpCache.
        HttpCache::init();
        // Init mimeMap.
        $this->_initMimeTypeMap();
    }

    /**
     * 收到新消息的回调
     * @param $connect
     * @param $data
     */
    public function callbackNewData($connect, $data){
        //解析来访的URL
        $requestUri = parse_url($_SERVER['REQUEST_URI']);
        if(!$requestUri){
            Http::setHeader('HTTP/1.1 400 Bad Request');
            $connect->close('HTTP 400 Bad Request');
            return;
        }
        $urlPath = $requestUri['path'];
        $urlPathLength = strlen($urlPath);
        $urlPath = $urlPath[$urlPathLength-1] === '/' ? substr($urlPath, 0, -1) : $urlPath;
        $documentRoot = isset($this->_documentRoot[$_SERVER['HTTP_HOST']]) ? $this->_documentRoot[$_SERVER['HTTP_HOST']] : current($this->_documentRoot);
        $filename = $documentRoot . $urlPath;
        //清楚文件状态缓存
        clearstatcache();
        //如果是目录
        if(is_dir($filename)){
            //如果缺省首页存在
            if($this->defaultIndex){
                foreach($this->defaultIndex as $index){
                    $file = $filename . '/' . $index;
                    if(is_file($file)){
                        $filename = $file;
                        break;
                    }
                }
            }else{
                Http::setHeader("HTTP/1.1 403 Forbidden");
                $connect->close('<html><head><title>403 Forbidden</title></head><body><center><h3>403 Forbidden</h3><br>Not allowed access to the directory!</center></body></html>');
                return;
            }
        }
        //文件是否有效
        if(!is_file($filename)){
            Http::setHeader("HTTP/1.1 404 Not Found");
            $connect->close('<html><head><title>404 File not found</title></head><body><center><h3>404 Not Found</h3></center></body></html>');
            return;
        }
        //文件是否可读
        if(!is_readable($filename)){
            Http::setHeader("HTTP/1.1 403 Forbidden");
            $connect->close('<html><head><title>403 Forbidden</title></head><body><center><h3>403 Forbidden</h3><br>File not readable</center></body></html>');
            return;
        }
        //获取文件后缀
        $urlPathInfo = pathinfo($filename);
        $urlExt = isset($urlPathInfo['extension']) ? $urlPathInfo['extension'] : '' ;
        //访问的路径是否是指定根目录的子目录
        $realFilename = realpath($filename);
        $documentRootRealPath = realpath($documentRoot) .'/';
        if(!$realFilename || !$documentRootRealPath || strpos($realFilename, $documentRootRealPath) !== 0){
            Http::setHeader("HTTP/1.1 403 Forbidden");
            $connect->close('<html><head><title>403 Forbidden</title></head><body><center><h3>403 Forbidden</h3><br>The directory is not authorized!</center></body></html>');
            return ;
        }
        //如果请求的是PHP文件
        if($urlExt === 'php'){
            ob_start();
            include $realFilename;
            $content = ob_get_clean();
            $connect->close($content);
            return;
        }
        //静态文件
        if($urlExt && isset(self::$mimeTypeMap[$urlExt])){
            Http::setHeader('Content-Type: '. self::$mimeTypeMap[$urlExt]);
        }else{
            Http::setHeader('Content-Type: '. self::$_defaultMimeType);
        }
        //获取文件状态
        $info = stat($filename);
        $modifiedTime = $info ? date('D, d M Y H:i:s', $info['mtime']) . ' GMT' : '';
        //静态文件未改变.则返回304
        if(!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $info && $modifiedTime === $_SERVER['HTTP_IF_MODIFIED_SINCE']){
            Http::setHeader('HTTP/1.1 304 Not Modified');
            $connect->close();
            return;
        }
        if($modifiedTime){
            Http::setHeader('Last-Modified: ' . $modifiedTime);
        }
        //给客户端发送消息,并且断开连接.
        $connect->close(file_get_contents($realFilename));
        return;
    }

    /**
     * 初始化MimeType
     * @return void
     */
    private function _initMimeTypeMap()
    {
        $mimeTypeFilePath = Http::getMimeTypesFile();
        if(!is_file($mimeTypeFilePath)){
            Log::write('mime type file not exists: '.$mimeTypeFilePath);
            return;
        }
        $mimeTypeList = file($mimeTypeFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if(!is_array($mimeTypeList)){
            Log::write('mime type file content incorrect.');
            return;
        }
        foreach($mimeTypeList as $mimeType){
            if(preg_match("/\s*(\S+)\s+(\S.+)/", $mimeType, $match)){
                //mimetype
                $type = $match[1];
                //后缀
                $extList = explode(' ', substr($match[2], 0, -1));
                foreach($extList as $ext)
                {
                    self::$mimeTypeMap[$ext] = $type;
                }
            }
        }
    }
}
