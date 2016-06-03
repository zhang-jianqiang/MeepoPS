<?php
/**
 * HTTP协议cache相关,如SESSION等
 * Created by Lane
 * User: lane
 * Date: 16/3/26
 * Time: 下午10:27
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace MeepoPS\Core\Protocol;

/**
 * HTTPcache
 * Class HttpCache
 * Created By Lane
 * Mail: lixuan868686@163.com
 * Blog: http://www.lanecn.com
 * @packet MeepoPS\Core\Protocol
 */
class HttpCache
{
    public static $instance = null;
    public static $header = array();
    //SESSION文件保存路径
    public static $sessionPath = '';
    //SESSION ID
    public $isSessionStart = false;
    public $sessionFile = '';

    /**
     * 初始化
     */
    public static function init()
    {
        self::$sessionPath = session_save_path() ? session_save_path() : sys_get_temp_dir();
        @session_start();
    }
}
