<?php
/**
 * 从TCP数据流中解析HTTP协议
 * Created by Lane
 * User: lane
 * Date: 16/3/26
 * Time: 下午10:27
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace MeepoPS\Core\Protocol;

use MeepoPS\Core\Transfer\TransferInterface;
use MeepoPS\Core\Log;

class Http implements ProtocolInterface
{

    private static $_sessionPath = '';
    private static $_sessionName = 'MeepoPS_SESSION_ID';
    //每一个链接对应一个实例, 每一个示例都是一个HTTP协议类
    private static $_instance = null;
    private $_httpHeader = array();
    private $_isStartSession = false;
    private $_sessionFilename = '';

    public static function input($data)
    {
        $position = strpos($data, "\r\n\r\n");
        //如果数据是\r\n\r\n开头,或者如果数据没有找到\r\n\r\n,表示数据未完.则不处理
        if (!$position) {
            return 0;
        }
        //将数据按照\r\n\r\n分割为两部分.第一部分是http头,第二部分是http body
        $http = explode("\r\n\r\n", $data, 2);
        //如果长度大于所能接收的Tcp所限制的最大数据量,则抛弃数据部分, 只发请求
        if(strlen($data) > MEEPO_PS_TCP_CONNECT_READ_MAX_PACKET_SIZE){
            $http[1] = '';
            $http[0] = preg_replace("/\r\nContent-Length: ?(\d+)/", "\r\nContent-Length: 0", $http[0]);
            Log::write('Http protocol: The received data size exceeds the maximum set of size', 'WARNING');
        }
        //POST请求
        if (strpos($http[0], "POST") === 0) {
            if (preg_match("/\r\nContent-Length: ?(\d+)/", $http[0], $match)) {
                //返回数据长度+头长度+4(\r\n\r\n)
                return strlen($http[0]) + $match[1] + 4;
            } else {
                return 0;
            }
            //非POST请求
        } else {
            //返回头长度+4(\r\n\r\n)
            return strlen($http[0]) + 4;
        }
    }

    /**
     * 将数据封装为HTTP协议数据
     * @param $data
     * @return string
     */
    public static function encode($data)
    {
        //状态码
        $header = isset(self::$_instance->_httpHeader['Http-Code']) ? self::$_instance->_httpHeader['Http-Code'] : 'HTTP/1.1 200 OK';
        $header .= "\r\n";
        //Connection
        $header .= isset(self::$_instance->_httpHeader['Connection']) ? self::$_instance->_httpHeader['Connection'] : 'Connection: keep-alive';
        $header .= "\r\n";
        //Content-Type
        $header .= isset(self::$_instance->_httpHeader['Content-Type']) ? self::$_instance->_httpHeader['Content-Type'] : 'Content-Type: text/html; charset=utf-8';
        $header .= "\r\n";
        //其他部分
        foreach (self::$_instance->_httpHeader as $name => $value) {
            if ($name === 'Set-Cookie' && is_array($value)) {
                foreach ($value as $v) {
                    $header .= $v . "\r\n";
                }
                continue;
            }
            $header .= $value . "\r\n";
        }
        //完善HTTP头的固定信息
        $header .= 'Server: MeepoPS' . MEEPO_PS_VERSION . "\r\n";
        $header .= 'Content-Length: ' . strlen($data) . "\r\n\r\n";
        //保存SESSION
        self::_saveSession();
        unset(self::$_instance->_httpHeader);
        //返回一个完整的数据包(头 + 数据)
        return $header . $data;
    }

    /**
     * 将数据包根据HTTP协议解码
     * @param string $data 待解码的数据
     * @param TransferInterface $connect 基于传输层协议的链接
     * @return array
     */
    public static function decode($data, TransferInterface $connect)
    {
        //实例化链接
        self::$_instance = new Http();
        //将超全局变量设为空.
        $_POST = $_GET = $_COOKIE = $_REQUEST = $_SESSION = $_FILES = $GLOBALS['HTTP_RAW_POST_DATA'] = array();
        $_SERVER = array();
        //解析HTTP头
        $http = explode("\r\n\r\n", $data, 2);
        $headerList = explode("\r\n", $http[0]);

        // ---------- 填充$_SERVER ----------
        //HTTP协议开头第一行
        list($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER['SERVER_PROTOCOL']) = explode(' ', $headerList[0]);
        //客户端IP和端口
        $clientAddress = $connect->getClientAddress();
        $_SERVER['REMOTE_ADDR'] = $clientAddress[0];
        $_SERVER['REMOTE_PORT'] = $clientAddress[1];
        $_SERVER['SERVER_SOFTWARE'] = 'MeepoPS/' . MEEPO_PS_VERSION . '( ' . PHP_OS . ' ) PHP/' . PHP_VERSION;
        $_SERVER['HTTPS'] = 'OFF';
        $_SERVER['REQUEST_SCHEME'] = 'http';
        $_SERVER['QUERY_STRING'] = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
        unset($headerList[0]);
        //循环剩下的HTTP头信息
        foreach ($headerList as $header) {
            if (empty($header)) {
                continue;
            }
            //将一条头分割为名字和值
            $header = explode(':', $header, 2);
            $name = strtolower($header[0]);
            $value = trim($header[1]);
            switch ($name) {
                case 'host':
                    $_SERVER['HTTP_HOST'] = $value;
                    $value = explode(':', $value);
                    $_SERVER['SERVER_NAME'] = $value[0];
                    if (isset($value[1])) {
                        $_SERVER['SERVER_PORT'] = $value[1];
                    }
                    break;
                case 'user-agent':
                    $_SERVER['HTTP_USER_AGENT'] = $value;
                    break;
                case 'accept':
                    $_SERVER['HTTP_ACCEPT'] = $value;
                    break;
                case 'content-length':
                    $_SERVER['Content-Length'] = strlen($data);
                    break;
                case 'content-type':
                    if (!preg_match('/boundary="?(\S+)"?/', $value, $match)) {
                        $_SERVER['CONTENT_TYPE'] = $value;
                    } else {
                        $_SERVER['CONTENT_TYPE'] = 'multipart/form-data';
                        $httpPostBoundary = '--' . $match[1];
                    }
                    break;
                case 'accept-charset':
                    $_SERVER['HTTP_ACCEPT_CHARSET'] = $value;
                    break;
                case 'accept-language':
                    $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $value;
                    break;
                case 'accept-encoding':
                    $_SERVER['HTTP_ACCEPT_ENCODING'] = $value;
                    break;
                case 'connection':
                    $_SERVER['HTTP_CONNECTION'] = $value;
                    break;
                case 'referer':
                    $_SERVER['HTTP_REFERER'] = $value;
                    break;
                case 'cookie':
                    $_SERVER['HTTP_COOKIE'] = $value;
                    parse_str(str_replace('; ', '&', $_SERVER['HTTP_COOKIE']), $_COOKIE);
                    break;
                case 'if-modified-since':
                    $_SERVER['HTTP_IF_MODIFIED_SINCE'] = $value;
                    break;
                case 'if-none-match':
                    $_SERVER['HTTP_IF_NONE_MATCH'] = $value;
                    break;
            }
        }
        unset($name, $value, $header, $headerList);
        //GET
        parse_str($_SERVER['QUERY_STRING'], $_GET);

        //POST请求
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'multipart/form-data') {
                self::parseUploadFiles($http[1], $httpPostBoundary);
            } else {
                parse_str($http[1], $_POST);
                $GLOBALS['HTTP_RAW_POST_DATA'] = $http[1];
            }
        }
        unset($http);
        //REQUEST
        $_REQUEST = array_merge($_GET, $_POST);
        return array('get' => $_GET, 'post' => $_POST, 'cookie' => $_COOKIE, 'server' => $_SERVER, 'files' => $_FILES);
    }

    /**
     * 设置http头
     * @param string $string 头字符串
     * @param bool $replace 是否用后面的头替换前面相同类型的头.即相同的多个头存在时,后来的会覆盖先来的.
     * @param int $httpResponseCode 头字符串
     * @return bool
     */
    public static function setHeader($string, $replace = true, $httpResponseCode = 0)
    {
        if (PHP_SAPI !== 'cli') {
            $httpResponseCode ? header($string, $replace, $httpResponseCode) : header($string, $replace);
            return true;
        }
        //第一种以“HTTP/”开头的 (case is not significant)，将会被用来计算出将要发送的HTTP状态码。
        //第二种特殊情况是“Location:”的头信息。它不仅把报文发送给浏览器，而且还将返回给浏览器一个 REDIRECT（302）的状态码，除非状态码已经事先被设置为了201或者3xx。
        if (strpos($string, 'HTTP') === 0) {
            $key = 'Http-Code';
        } else {
            $key = strstr($string, ':', true);
            if (empty($key)) {
                return false;
            }
        }
        //如果是302跳转
        if (strtolower($key) === 'location' && !$httpResponseCode) {
            return self::setHeader($string, true, 302);
        }
        $httpCodeList = self::getHttpCode();
        if (isset($httpCodeList[$httpResponseCode])) {
            self::$_instance->_httpHeader['Http-Code'] = 'HTTP/1.1 ' . $httpResponseCode . ' ' . $httpCodeList[$httpResponseCode];
            if ($key === 'Http-Code') {
                return true;
            }
        }
        $key === 'Set-Cookie' ? (self::$_instance->_httpHeader[$key][] = $string) : (self::$_instance->_httpHeader[$key] = $string);
        return true;
    }

    /**
     * 删除header()设置的HTTP头信息
     * @param string $name 删除指定的头信息
     */
    public static function removeHttpHeader($name)
    {
        if (PHP_SAPI != 'cli') {
            header_remove();
        } else {
            unset(self::$_instance->_httpHeader[$name]);
        }

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
     * @param bool $HTTPOnly
     * @return bool
     */
    public static function setcookie($name, $value = '', $maxage = 0, $path = '', $domain = '', $secure = false, $HTTPOnly = false)
    {
        if (PHP_SAPI != 'cli') {
            return setcookie($name, $value, $maxage, $path, $domain, $secure, $HTTPOnly);
        }
        return self::setHeader(
            'Set-Cookie: ' . $name . '=' . rawurlencode($value)
            . (empty($domain) ? '' : '; Domain=' . $domain)
            . (empty($maxage) ? '' : '; Max-Age=' . $maxage)
            . (empty($path) ? '' : '; Path=' . $path)
            . (!$secure ? '' : '; Secure')
            . (!$HTTPOnly ? '' : '; HttpOnly'), false);
    }

    /**
     * 开启SESSION
     * @return bool
     */
    public static function sessionStart()
    {
        self::$_sessionPath = session_save_path() ? session_save_path() : sys_get_temp_dir();
        if (PHP_SAPI != 'cli') {
            return session_start();
        }
        if (self::$_instance->_isStartSession) {
            return true;
        }
        self::$_instance->_isStartSession = true;
        //生成SID
        if (!isset($_COOKIE[self::$_sessionName]) || !is_file(self::$_sessionPath . '/session_' . $_COOKIE[self::$_sessionName])) {
            $sessionFilename = tempnam(HttpCache::$sessionPath, 'session_');
            if (!$sessionFilename) {
                return false;
            }
            self::$_instance->_sessionFilename = $sessionFilename;
            $session_id = substr(basename($sessionFilename), strlen('session_'));
            return self::setcookie(
                self::$_sessionName, $session_id, ini_get('session.cookie_lifetime'), ini_get('session.cookie_path'),
                ini_get('session.cookie_domain'), ini_get('session.cookie_secure'), ini_get('session.cookie_httponly')
            );
        }
        if (!self::$_sessionName) {
            self::$_instance->_sessionFilename = self::$_sessionPath . '/session_' . $_COOKIE[self::$_sessionName];
        }
        //读取SESSION文件,填充到$_SESSION中
        if (self::$_instance->_sessionFilename) {
            $sessionContent = file_get_contents(self::$_instance->_sessionFilename);
            if ($sessionContent) {
                session_decode($sessionContent);
            }
        }
        return true;
    }

    /**
     * 保存SESSION
     */
    private static function _saveSession()
    {
        //不是命令行模式则写入SESSION并关闭文件
        if (PHP_SAPI !== 'cli') {
            session_write_close();
            return false;
        }
        //如果SESSION已经开启,并且$_SESSION有值
        if (self::$_instance->_isStartSession && $_SESSION) {
            $session = session_encode();
            if ($session && self::$_sessionFilename) {
                return file_put_contents(self::$_sessionFilename, $session);
            }
        }
        return empty($_SESSION);
    }

    /**
     * 退出.
     * @param string $msg
     * @throws \Exception
     */
    public static function end($msg = '')
    {
        if ($msg) {
            echo $msg;
        }
        if (PHP_SAPI !== 'cli') {
            exit();
        }
        throw new \Exception('end');
    }

    /**
     * 解析$_FILES
     * @param $httpBody string HTTP主体数据
     * @param $httpPostBoundary string HTTP POST 请求的边界
     * @return void
     */
    protected static function parseUploadFiles($httpBody, $httpPostBoundary)
    {
        $httpBody = substr($httpBody, 0, strlen($httpBody) - (strlen($httpPostBoundary) + 4));
        $boundaryDataList = explode($httpPostBoundary . "\r\n", $httpBody);
        if ($boundaryDataList[0] === '') {
            unset($boundaryDataList[0]);
        }
        foreach ($boundaryDataList as $boundaryData) {
            //分割为描述信息和数据
            list($boundaryHeaderBuffer, $boundaryValue) = explode("\r\n\r\n", $boundaryData, 2);
            //移除数据结尾的\r\n
            $boundaryValue = substr($boundaryValue, 0, -2);
            foreach (explode("\r\n", $boundaryHeaderBuffer) as $item) {
                list($headerName, $headerValue) = explode(": ", $item);
                $headerName = strtolower($headerName);
                switch ($headerName) {
                    case "content-disposition":
                        //是上传文件
                        if (preg_match('/name=".*?"; filename="(.*?)"$/', $headerValue, $match)) {
                            //将文件数据写入$_FILES
                            $_FILES[] = array(
                                'file_name' => $match[1],
                                'file_data' => $boundaryValue,
                                'file_size' => strlen($boundaryValue),
                            );
                            continue;
                            //POST数据
                        } else {
                            //将POST数据写入$_POST
                            if (preg_match('/name="(.*?)"$/', $headerValue, $match)) {
                                $_POST[$match[1]] = $boundaryValue;
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * 获取MIME TYPE
     * @return string
     */
    public static function getMimeTypesFile()
    {
        //从nginx1.10.0的mime.types中复制的, 然后转换成数组
        return array ('html' => 'text/html', 'htm' => 'text/html', 'shtml' => 'text/html', 'css' => 'text/css', 'xml' => 'text/xml', 'gif' => 'image/gif', 'jpeg' => 'image/jpeg', 'jpg' => 'image/jpeg', 'js' => 'application/javascript', 'atom' => 'application/atom+xml', 'rss' => 'application/rss+xml', 'mml' => 'text/mathml', 'txt' => 'text/plain', 'jad' => 'text/vnd.sun.j2me.app-descriptor', 'wml' => 'text/vnd.wap.wml', 'htc' => 'text/x-component', 'png' => 'image/png', 'tif' => 'image/tiff', 'tiff' => 'image/tiff', 'wbmp' => 'image/vnd.wap.wbmp', 'ico' => 'image/x-icon', 'jng' => 'image/x-jng', 'bmp' => 'image/x-ms-bmp', 'svg' => 'image/svg+xml', 'svgz' => 'image/svg+xml', 'webp' => 'image/webp', 'woff' => 'application/font-woff', 'jar' => 'application/java-archive', 'war' => 'application/java-archive', 'ear' => 'application/java-archive', 'json' => 'application/json', 'hqx' => 'application/mac-binhex40', 'doc' => 'application/msword', 'pdf' => 'application/pdf', 'ps' => 'application/postscript', 'eps' => 'application/postscript', 'ai' => 'application/postscript', 'rtf' => 'application/rtf', 'm3u8' => 'application/vnd.apple.mpegurl', 'xls' => 'application/vnd.ms-excel', 'eot' => 'application/vnd.ms-fontobject', 'ppt' => 'application/vnd.ms-powerpoint', 'wmlc' => 'application/vnd.wap.wmlc', 'kml' => 'application/vnd.google-earth.kml+xml', 'kmz' => 'application/vnd.google-earth.kmz', '7z' => 'application/x-7z-compressed', 'cco' => 'application/x-cocoa', 'jardiff' => 'application/x-java-archive-diff', 'jnlp' => 'application/x-java-jnlp-file', 'run' => 'application/x-makeself', 'pl' => 'application/x-perl', 'pm' => 'application/x-perl', 'prc' => 'application/x-pilot', 'pdb' => 'application/x-pilot', 'rar' => 'application/x-rar-compressed', 'rpm' => 'application/x-redhat-package-manager', 'sea' => 'application/x-sea', 'swf' => 'application/x-shockwave-flash', 'sit' => 'application/x-stuffit', 'tcl' => 'application/x-tcl', 'tk' => 'application/x-tcl', 'der' => 'application/x-x509-ca-cert', 'pem' => 'application/x-x509-ca-cert', 'crt' => 'application/x-x509-ca-cert', 'xpi' => 'application/x-xpinstall', 'xhtml' => 'application/xhtml+xml', 'xspf' => 'application/xspf+xml', 'zip' => 'application/zip', 'bin' => 'application/octet-stream', 'exe' => 'application/octet-stream', 'dll' => 'application/octet-stream', 'deb' => 'application/octet-stream', 'dmg' => 'application/octet-stream', 'iso' => 'application/octet-stream', 'img' => 'application/octet-stream', 'msi' => 'application/octet-stream', 'msp' => 'application/octet-stream', 'msm' => 'application/octet-stream', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'mid' => 'audio/midi', 'midi' => 'audio/midi', 'kar' => 'audio/midi', 'mp3' => 'audio/mpeg', 'ogg' => 'audio/ogg', 'm4a' => 'audio/x-m4a', 'ra' => 'audio/x-realaudio', '3gpp' => 'video/3gpp', '3gp' => 'video/3gpp', 'ts' => 'video/mp2t', 'mp4' => 'video/mp4', 'mpeg' => 'video/mpeg', 'mpg' => 'video/mpeg', 'mov' => 'video/quicktime', 'webm' => 'video/webm', 'flv' => 'video/x-flv', 'm4v' => 'video/x-m4v', 'mng' => 'video/x-mng', 'asx' => 'video/x-ms-asf', 'asf' => 'video/x-ms-asf', 'wmv' => 'video/x-ms-wmv', 'avi' => 'video/x-msvideo');
    }

    /**
     * 获取HTTP状态码
     * @return array
     */
    public static function getHttpCode(){
        return array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => '(Unused)',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
        );
    }
}
