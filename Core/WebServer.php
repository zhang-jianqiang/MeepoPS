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

use FastWS\Core\Protocol\Http;
use FastWS\Core\Protocol\HttpCache;

class WebServer extends FastWS{
    //MIME TYPE
    private static $_defaultMimeType = 'text/html; charset=utf-8';
    //代码文件根目录
    private $_documentRoot = array();
    //后缀和MimeType的对应关系 array('ext1'=>'mime_type1', 'ext2'=>'mime_type2')
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
        $this->callbackStart = array($this, 'callbackStart');
        $this->callbackConnect = array($this, 'callbackConnect');
        $this->callbackNewData = array($this, 'callbackNewData');
        $this->callbackConnectClose = array($this, 'callbackConnectClose');
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
        if(empty($this->_documentRoot)){
            Log::write('not set document root.', 'FATAL');
        }
        // Init HttpCache.
        HttpCache::init();
        // Init mimeMap.
        $this->_initMimeTypeMap();
    }

    public function callbackConnect($connect){
        var_dump('收到新链接. UniqueId='.$connect->id);
    }

    /**
     * 收到新消息的回调
     * @param $connect
     * @param $data
     */
    public function callbackNewData($connect, $data){
        var_dump('UniqueId='.$connect->id.'说:');
        //解析来访的URL
        $requestUri = parse_url($_SERVER['REQUEST_URI']);
        if(!$requestUri){
            Http::setHeader('HTTP/1.1 400 Bad Request');
            $connect->close('HTTP 400 Bad Request');
            return;
        }

        $urlPath = $requestUri['path'];
        $urlPathInfo = pathinfo($urlPath);
        $urlExt = isset($urlPathInfo['extension']) ? $urlPathInfo['extension'] : '' ;
        if($urlExt === ''){
            $len = strlen($urlPath);
            $urlPath = ( $len && $urlPath[$len-1] === '/' ) ? $urlPath.'index.php' : $urlPath . '/index.php';
            $urlExt = 'php';
        }
        $documentRoot = isset($this->_documentRoot[$_SERVER['HTTP_HOST']]) ? $this->_documentRoot[$_SERVER['HTTP_HOST']] : current($this->_documentRoot);
        $filename = $documentRoot . '/' . $urlPath;
        if($urlExt === 'php' && !is_file($filename)){
            $filename = $documentRoot . '/index.php';
            if(!is_file($filename)){
                $filename = $documentRoot . '/index.html';
                $urlExt = 'html';
            }
        }
        //找不到文件,就404
        if(!is_file($filename)){
            Http::setHeader("HTTP/1.1 404 Not Found");
            $connect->close('<html><head><title>404 File not found</title></head><body><center><h3>404 Not Found</h3></center></body></html>');
            return;
        }
        //安全性检测
        $realPath = realpath($filename);
        $documentRootRealPath = realpath($documentRoot);
        if(!$realPath || !$documentRootRealPath || strpos($realPath, $documentRootRealPath) !== 0){
            Http::setHeader('HTTP/1.1 400 Bad Request');
            $connect->close('<h1>400 Bad Request</h1>');
            return ;
        }
        //如果请求的是PHP文件
        if($urlExt === 'php'){
            $cwd = getcwd();
            chdir($documentRoot);
            ob_start();
            try{
                $clientAddress = $connect->getClientAddress();
                $_SERVER['REMOTE_ADDR'] = $clientAddress[0];
                $_SERVER['REMOTE_PORT'] = $clientAddress[1];
                include $filename;
            }catch(\Exception $e){
                if($e->getMessage() != 'jump_exit'){
                    echo $e;
                }
            }
            $content = ob_get_clean();
            $connect->close($content);
            chdir($cwd);
            return;
        }
        //静态文件
        if(isset(self::$mimeTypeMap[$urlExt])){
            Http::header('Content-Type: '. self::$mimeTypeMap[$urlExt]);
        }else{
            Http::header('Content-Type: '. self::$_defaultMimeType);
        }

        //获取文件状态
        $info = stat($filename);
        $modifiedTime = $info ? date('D, d M Y H:i:s', $info['mtime']) . ' GMT' : '';

        if(!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $info){
            // Http 304.
            if($modifiedTime === $_SERVER['HTTP_IF_MODIFIED_SINCE']){
                Http::setHeader('HTTP/1.1 304 Not Modified');
                // Send nothing but http headers..
                $connect->close();
                return;
            }
        }
        if($modifiedTime){
            Http::setHeader('Last-Modified: ' . $modifiedTime);
        }
        //给客户端发送消息,并且断开连接.
        $connect->close(file_get_contents($filename));
        return;
    }

    public function callbackConnectClose($connect){
        var_dump('UniqueId='.$connect->id.'断开了');
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