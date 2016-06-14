<?php
\MeepoPS\Api\Http::sessionStart();


//if (empty($_SESSION['user_info']) || empty($_SESSION['user_info']['username'])) {
//    echo '<script language="JavaScript">
//            alert("请登陆");
//            location.href="login.php";
//            document.onmousedown=click
//        </script>';
//} else {
//    echo '登陆成功!';
//    var_dump($_SESSION);
//}