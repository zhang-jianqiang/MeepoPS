<?php
/**
 * Created by lane
 * User: lane
 * Date: 16/4/26
 * Time: 下午2:07
 * E-mail: lixuan868686@163.com
 * WebSite: http://www.lanecn.com
 */

var_dump(\MeepoPS\Core\Protocol\Http::sessionStart());


return;


if (empty($_SESSION['user_info']) || empty($_SESSION['user_info']['username'])) {
    echo '<script language="JavaScript">
            alert("请登陆");
            location.href="login.php";
            document.onmousedown=click
        </script>';
} else {
    echo '登陆成功!';
    var_dump($_SESSION);
}