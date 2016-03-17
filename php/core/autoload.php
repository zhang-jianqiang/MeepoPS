<?php
/**
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/3/17
 * Time: 下午2:54
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */

namespace Core\FastWS;

class Autoloader{

    const NAMESPACE_PREFIX = 'FastWS\\Core';

    /**
     * 向PHP注册在自动载入函数
     */
    public static function register(){
        spl_autoload_register(array(new self, 'autoload'));
    }

    /**
     * 根据类名载入所在文件
     */
    public static function autoload($className){
        $namespacePrefixStrlen = strlen(self::NAMESPACE_PREFIX);
        if(strncmp(self::NAMESPACE_PREFIX, $className, $namespacePrefixStrlen) === 0){
            $className = strtolower($className);
            $filePath = str_replace('\\', DIRECTORY_SEPARATOR, substr($className, $namespacePrefixStrlen));
            $filePath = realpath(__DIR__ . (empty($filePath) ? '' : DIRECTORY_SEPARATOR) . $filePath . '.php');
            if(file_exists($filePath)){
                require_once $filePath;
            }else{
                die('File Not Exists: ' . $filePath);
            }
        }
    }
}