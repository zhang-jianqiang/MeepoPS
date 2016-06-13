<?php
/**
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/6/13
 * Time: 下午6:01
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace MeepoPS\Library;

class Session{
    //Session文件保存路径
    private $_savePath;

    /**
     * session_start()时自动调用
     * @param $savePath
     * @param $sessionName
     * @return bool
     */
    function open($savePath, $sessionName){
        //命令行调用时返回false
        if(PHP_SAPI === 'cli'){
            //日志
//            return false;
        }
        $this->_savePath = !empty($savePath) ? $savePath : sys_get_temp_dir();
        if(!$this->_savePath){
            //日志
            return false;
        }
        if(!is_dir($this->_savePath)){
            $result = @mkdir($this->_savePath, 0777);
            if($result !== true){
                //日志
                return false;
            }
        }
        return true;
    }

    /**
     * 关闭Session
     * @return bool
     */
    function close(){
        return true;
    }

    /**
     * 读取Session时调用
     * @param int $sessionId
     * @return string
     */
    function read($sessionId){
        var_dump($sessionId);
        return @file_get_contents($this->_savePath . '/sess_' . $sessionId);
    }

    /**
     * 脚本结束时调用
     * 本方法结束时会PHP内部自动调用close()
     * @param $sessionId
     * @param $data
     * @return bool or int
     */
    function write($sessionId, $data){
        var_dump($sessionId);
        var_dump($data);
        $result = file_put_contents($this->_savePath . '/sess_' . $sessionId, $data);
        var_dump($result);
        return $result;
    }

    /**
     * 调用session_destroy()时会自动调用本方法
     * @param $sessionId
     * @return bool
     */
    function destroy($sessionId){
        $file = "$this->savePath/sess_$sessionId";
        if (file_exists($file)) {
            unlink($file);
        }

        return true;
    }

    /**
     * 资源回收。
     * 调用周期由 session.gc_probability 和 session.gc_divisor 参数控制
     * 传入到此回调函数的 $maxLifeTime 参数由 session.gc_maxlifetime 设置
     * @param $maxLifeTime
     * @return bool
     */
    function gc($maxLifeTime){
        foreach (glob($this->_savePath . '/sess_*') as $file) {
            if (filemtime($file) + $maxLifeTime < time()) {
                @unlink($file);
            }
        }
        return true;
    }
}