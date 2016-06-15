<html>
<form action="" method="post">
    <input name="username" type="text" value="u"><br>
    <input name="password" type="text" value="p"><br>
    <input type="submit">
</form>
</html>
<?php
$result = \Meepops\Api\Http::sessionStart();

if (!empty($_POST['username']) && !empty($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $userInfo = array('username' => $username, 'password' => $password);
    $_SESSION['user_info'] = $userInfo;
    if(!empty($_SESSION['user_info']['username']) && !empty($_SESSION['user_info']['password'])){
        \MeepoPS\Api\Http::setHeader('Location: index.php');
    }
}