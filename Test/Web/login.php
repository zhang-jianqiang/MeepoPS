<html>
<form action="" method="post">
    <input name="username" type="text"><br>
    <input name="password" type="text"><br>
    <input type="submit">
</form>
</html>
<?php
var_dump($_SESSION);
if (!empty($_POST['username']) && !empty($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $userInfo = array('username' => $username, 'password' => $password);
    $_SESSION['user_info'] = $userInfo;
}