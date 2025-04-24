<?php
session_start();
include "../config.php";

// 設定資料庫編碼
$db_link->query("SET NAMES 'utf8'");

// 檢查是否已登入
if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 獲取並驗證 POST 資料
$id = isset($_POST['id']) ? $_POST['id'] : '';
$name = isset($_POST['name']) ? $_POST['name'] : '';
$phone = isset($_POST['phone']) ? $_POST['phone'] : '';
$email = isset($_POST['email']) ? $_POST['email'] : '';
$birth = isset($_POST['birth']) ? $_POST['birth'] : '';
$access = isset($_POST['access']) ? $_POST['access'] : '';

// 使用預備語句更新資料（不包含密碼）
$sql = "UPDATE `user` 
        SET `Uname` = ?, 
            `Uphone` = ?, 
            `Uemail` = ?, 
            `Ubirth` = ?, 
            `Uaccess` = ? 
        WHERE `Uid` = ?";

$stmt = $db_link->prepare($sql);
if ($stmt === false) {
    echo "錯誤準備語句: " . $db_link->error;
    exit();
}

$stmt->bind_param("ssssss", $name, $phone, $email, $birth, $access, $id);
$result = $stmt->execute();

if ($result) {
    echo "<script>alert('修改成功');location.href='user.php';</script>";
    exit();
} else {
    echo "更新記錄時出錯: " . $db_link->error;
}

$stmt->close();
$db_link->close();
?>
