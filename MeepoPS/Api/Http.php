<?php
/**
 * API - HTTP协议
 * Created by Lane
 * User: lane
 * Date: 16/3/23
 * Time: 下午2:12
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace MeepoPS\Api;

use MeepoPS\Core\MeepoPS;
use MeepoPS\Core\Log;

class Http extends MeepoPS
{

    //默认页文件名
    public $defaultIndex = array('index.html');

    //代码文件根目录
    private $_documentRoot = array();

    //错误页 array('404' => '页面不在', '503' => 'tpl/err_503.html')
    private $_errorPage = array();
    //后缀和MimeType的对应关系 array('ext1'=>'mime_type1', 'ext2'=>'mime_type2')
    private static $mimeTypeMap = array();

    /**
     * WebServer constructor.
     * @param string $host string 需要监听的地址
     * @param string $port string 需要监听的端口
     * @param array $contextOptionList
     */
    public function __construct($host, $port, $contextOptionList = array())
    {
        if (!$host || !$port) {
            return;
        }
        self::$mimeTypeMap = $this->_getMimeTypesFile();
        parent::__construct('http', $host, $port, $contextOptionList);
    }

    /**
     * 运行一个WebService实例
     */
    public function run()
    {
        if (empty($this->_documentRoot)) {
            Log::write('not set document root.', 'ERROR');
        }
        //设置MeepoPS的回调.
        $this->callbackStartInstance = array($this, 'callbackStartInstance');
        $this->callbackNewData = array($this, 'callbackNewData');
        //运行MeepoPS
        parent::run();
    }

    /**
     * 设置网站代码目录
     * @param $domain string 域名
     * @param $documentPath string 路径
     */
    public function setRoot($domain, $documentPath)
    {
        if (!$domain) {
            Log::write('domain incorrect');
            return;
        }
        if (!$documentPath) {
            Log::write('documentPath incorrect');
            return;
        }
        if (!file_exists($documentPath)) {
            Log::write($documentPath . ' no exists');
            return;
        }
        $this->_documentRoot[$domain] = $documentPath;
    }

    /**
     * 设置http头
     * @param string $string 头字符串
     * @param bool $replace 是否用后面的头替换前面相同类型的头.即相同的多个头存在时,后来的会覆盖先来的.
     * @param int $httpResponseCode 头字符串
     * @return bool
     */
    public function setHeader($string, $replace = true, $httpResponseCode = 0){
        return \MeepoPS\Core\Protocol\Http::setHeader($string, $replace, $httpResponseCode);
    }

    /**
     * 删除header()设置的HTTP头信息
     * @param string $name 删除指定的头信息
     */
    public function delHttpHeader($name){
        \MeepoPS\Core\Protocol\Http::delHttpHeader($name);
    }

    /**
     * 设置Cookie
     * 参数意义请参考setcookie()
     * @param string $name
     * @param string $value
     * @param integer $maxage
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @return bool
     */
    public function setCookie($name, $value = '', $maxage = 0, $path = '', $domain = '', $secure = false, $httpOnly = false){
        return \MeepoPS\Core\Protocol\Http::setCookie($name, $value, $maxage, $path, $domain, $secure, $httpOnly);
    }

    /**
     * 开启SESSION
     * @return bool
     */
    public function startSession(){
        return \MeepoPS\Core\Protocol\Http::startSession();
    }

    /**
     * 获取HTTP状态码及对应说明
     * @return array
     */
    public function getHttpCode(){
        return \MeepoPS\Core\Protocol\Http::getHttpCode();
    }

    /**
     * 收到新消息的回调
     * @param $connect
     * @param $data
     */
    public function callbackNewData($connect, $data)
    {
        //解析来访的URL
        $requestUri = parse_url($_SERVER['REQUEST_URI']);
        if (!$requestUri) {
            $this->setHeader('HTTP/1.1 400 Bad Request');
            $connect->close('HTTP 400 Bad Request');
            return;
        }
        $urlPath = $requestUri['path'];
        $urlPathLength = strlen($urlPath);
        $urlPath = $urlPath[$urlPathLength - 1] === '/' ? substr($urlPath, 0, -1) : $urlPath;
        $documentRoot = isset($this->_documentRoot[$_SERVER['HTTP_HOST']]) ? $this->_documentRoot[$_SERVER['HTTP_HOST']] : current($this->_documentRoot);
        $filename = $documentRoot . $urlPath;
        //清楚文件状态缓存
        clearstatcache();
        //如果是目录
        if (is_dir($filename)) {
            //如果缺省首页存在
            if ($this->defaultIndex) {
                foreach ($this->defaultIndex as $index) {
                    $file = $filename . '/' . $index;
                    if (is_file($file)) {
                        $filename = $file;
                        break;
                    }
                }
            } else {
                $this->setHeader("HTTP/1.1 403 Forbidden");
                $connect->close('<html><head><title>403 Forbidden</title></head><body><center><h3>403 Forbidden</h3><br>Not allowed access to the directory!</center></body></html>');
                return;
            }
        }
        //文件是否有效
        if (!is_file($filename)) {
            $this->setHeader("HTTP/1.1 404 Not Found");
            $connect->close('<html><head><title>404 File not found</title></head><body><center><h3>404 Not Found</h3></center></body></html>');
            return;
        }
        //文件是否可读
        if (!is_readable($filename)) {
            $this->setHeader("HTTP/1.1 403 Forbidden");
            $connect->close('<html><head><title>403 Forbidden</title></head><body><center><h3>403 Forbidden</h3><br>File not readable</center></body></html>');
            return;
        }
        //获取文件后缀
        $urlPathInfo = pathinfo($filename);
        $urlExt = isset($urlPathInfo['extension']) ? $urlPathInfo['extension'] : '';
        //访问的路径是否是指定根目录的子目录
        $realFilename = realpath($filename);
        $documentRootRealPath = realpath($documentRoot) . '/';
        if (!$realFilename || !$documentRootRealPath || strpos($realFilename, $documentRootRealPath) !== 0) {
            $this->setHeader("HTTP/1.1 403 Forbidden");
            $connect->close('<html><head><title>403 Forbidden</title></head><body><center><h3>403 Forbidden</h3><br>The directory is not authorized!</center></body></html>');
            return;
        }
        //如果请求的是PHP文件
        if ($urlExt === 'php') {
            ob_start();
            include $realFilename;
            $content = ob_get_clean();
            $connect->close($content);
            return;
        }
        //静态文件
        if ($urlExt && isset(self::$mimeTypeMap[$urlExt])) {
            $this->setHeader('Content-Type: ' . self::$mimeTypeMap[$urlExt]);
        } else {
            $this->setHeader('Content-Type: ' . 'text/html; charset=utf-8');
        }
        //获取文件状态
        $info = stat($filename);
        $modifiedTime = $info ? date('D, d M Y H:i:s', $info['mtime']) . ' GMT' : '';
        //静态文件未改变.则返回304
        if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $info && $modifiedTime === $_SERVER['HTTP_IF_MODIFIED_SINCE']) {
            $this->setHeader('HTTP/1.1 304 Not Modified');
            $connect->close();
            return;
        }
        if ($modifiedTime) {
            $this->setHeader('Last-Modified: ' . $modifiedTime);
        }
        //给客户端发送消息,并且断开连接.
        $connect->close(file_get_contents($realFilename));
        return;
    }

    /**
     * 获取MIME TYPE
     * @return string
     */
    private function _getMimeTypesFile()
    {
        //从nginx1.10.0的mime.types中复制的, 然后转换成数组
        return array ('html' => 'text/html', 'htm' => 'text/html', 'shtml' => 'text/html', 'css' => 'text/css', 'xml' => 'text/xml', 'gif' => 'image/gif', 'jpeg' => 'image/jpeg', 'jpg' => 'image/jpeg', 'js' => 'application/javascript', 'atom' => 'application/atom+xml', 'rss' => 'application/rss+xml', 'mml' => 'text/mathml', 'txt' => 'text/plain', 'jad' => 'text/vnd.sun.j2me.app-descriptor', 'wml' => 'text/vnd.wap.wml', 'htc' => 'text/x-component', 'png' => 'image/png', 'tif' => 'image/tiff', 'tiff' => 'image/tiff', 'wbmp' => 'image/vnd.wap.wbmp', 'ico' => 'image/x-icon', 'jng' => 'image/x-jng', 'bmp' => 'image/x-ms-bmp', 'svg' => 'image/svg+xml', 'svgz' => 'image/svg+xml', 'webp' => 'image/webp', 'woff' => 'application/font-woff', 'jar' => 'application/java-archive', 'war' => 'application/java-archive', 'ear' => 'application/java-archive', 'json' => 'application/json', 'hqx' => 'application/mac-binhex40', 'doc' => 'application/msword', 'pdf' => 'application/pdf', 'ps' => 'application/postscript', 'eps' => 'application/postscript', 'ai' => 'application/postscript', 'rtf' => 'application/rtf', 'm3u8' => 'application/vnd.apple.mpegurl', 'xls' => 'application/vnd.ms-excel', 'eot' => 'application/vnd.ms-fontobject', 'ppt' => 'application/vnd.ms-powerpoint', 'wmlc' => 'application/vnd.wap.wmlc', 'kml' => 'application/vnd.google-earth.kml+xml', 'kmz' => 'application/vnd.google-earth.kmz', '7z' => 'application/x-7z-compressed', 'cco' => 'application/x-cocoa', 'jardiff' => 'application/x-java-archive-diff', 'jnlp' => 'application/x-java-jnlp-file', 'run' => 'application/x-makeself', 'pl' => 'application/x-perl', 'pm' => 'application/x-perl', 'prc' => 'application/x-pilot', 'pdb' => 'application/x-pilot', 'rar' => 'application/x-rar-compressed', 'rpm' => 'application/x-redhat-package-manager', 'sea' => 'application/x-sea', 'swf' => 'application/x-shockwave-flash', 'sit' => 'application/x-stuffit', 'tcl' => 'application/x-tcl', 'tk' => 'application/x-tcl', 'der' => 'application/x-x509-ca-cert', 'pem' => 'application/x-x509-ca-cert', 'crt' => 'application/x-x509-ca-cert', 'xpi' => 'application/x-xpinstall', 'xhtml' => 'application/xhtml+xml', 'xspf' => 'application/xspf+xml', 'zip' => 'application/zip', 'bin' => 'application/octet-stream', 'exe' => 'application/octet-stream', 'dll' => 'application/octet-stream', 'deb' => 'application/octet-stream', 'dmg' => 'application/octet-stream', 'iso' => 'application/octet-stream', 'img' => 'application/octet-stream', 'msi' => 'application/octet-stream', 'msp' => 'application/octet-stream', 'msm' => 'application/octet-stream', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'mid' => 'audio/midi', 'midi' => 'audio/midi', 'kar' => 'audio/midi', 'mp3' => 'audio/mpeg', 'ogg' => 'audio/ogg', 'm4a' => 'audio/x-m4a', 'ra' => 'audio/x-realaudio', '3gpp' => 'video/3gpp', '3gp' => 'video/3gpp', 'ts' => 'video/mp2t', 'mp4' => 'video/mp4', 'mpeg' => 'video/mpeg', 'mpg' => 'video/mpeg', 'mov' => 'video/quicktime', 'webm' => 'video/webm', 'flv' => 'video/x-flv', 'm4v' => 'video/x-m4v', 'mng' => 'video/x-mng', 'asx' => 'video/x-ms-asf', 'asf' => 'video/x-ms-asf', 'wmv' => 'video/x-ms-wmv', 'avi' => 'video/x-msvideo');
    }

    public function setErrorPage($httpCode, $description){
        $this->_errorPage[$httpCode] = $description;
    }

    public function getErrorPage($httpCode, $message='', $description=''){
        if(!$httpCode){
            return false;
        }
        if(!isset($this->_errorPage[$httpCode])){
            $httpCodeArray = $this->getHttpCode();
            $message = $message ? $message : '';
            $description = $description ? $description : ( isset($httpCodeArray[$httpCode]) ? $httpCodeArray[$httpCode] : '' );
            $display = '<html><head><title>%s %s</title></head><body><center><h3>%s %s</h3><br>%s</center></body></html>';
            $display = sprintf($display, $httpCode, $message, $httpCode, $message, $description);
        }else{
            //如果是文件
            if(file_exists($this->_errorPage[$httpCode])){
                ob_start();
                include $this->_errorPage[$httpCode];
                $display = ob_get_clean();
            }else{
                $display = '<html><head><title>%s</title></head><body><center><h3>%</h3><br>%s</center></body></html>';
                $display = sprintf($display, $httpCode, $httpCode, $this->_errorPage[$httpCode]);
            }
        }
        return $display;
    }
}
