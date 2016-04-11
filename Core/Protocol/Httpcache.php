<?php
/**
 * HTTP协议cache相关,如SESSION等
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/3/26
 * Time: 下午10:27
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Core\Protocol;

/**
 * HTTPcache
 * Class Httpcache
 * Created By Lane
 * Mail: lixuan868686@163.com
 * Blog: http://www.lanecn.com
 * @packet FastWS\Core\Protocol
 */
class Httpcache
{
    /**
     * HTTP协议状态码
     * @var array
     */
    public static $httpCodeList = array(
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
    public static $instance = null;
    public static $header = array();
    //SESSION文件保存路径
    public static $sessionPath = '';
    //SESSION ID
    public static $sessionName = 'FastWS_SESSION_ID';
    public $isSessionStart = false;
    public $sessionFile = '';

    /**
     * 初始化
     */
    public static function init(){
        self::$sessionPath = session_save_path() ? session_save_path() : sys_get_temp_dir();
        @session_start();
    }
}
