<?php
/**
 * Created by lixuan-it@360.cn
 * User: lane
 * Date: 16/4/26
 * Time: 下午2:07
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */
if(empty($_SESSION['user']) || empty($_SESSION['user']['uid'])){
    echo '<script language="JavaScript">
            alert("请登陆");
            location.href="./login.php";
            document.onmousedown=click
        </script>';
}else{
    echo '登陆成功!';
    var_dump($_SESSION);

}