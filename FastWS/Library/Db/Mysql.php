<?php
/**
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/5/25
 * Time: 上午11:10
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
namespace FastWS\Library\Db;

class Mysql{
    public static $conn = null;
    public function __construct($host='', $username='', $password='', $dbName='', $port='3306')
    {
        if(is_null(self::$conn)){
            $conn = mysqli_connect($host, $username, $password, $dbName, $port);
            self::$conn = ($conn && is_object($conn)) ? $conn : null;
        }
    }

    public function query($sql){
        echo $sql;
        return mysqli_query(self::$conn, $sql);
    }

    public function add(){

    }

    public function select(){

    }
}