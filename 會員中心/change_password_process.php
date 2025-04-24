<?php
session_start();
include "../config.php";

// 設定資料庫編碼
$db_link->query("SET NAMES 'utf8'");

// 檢查是否已登入
if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
    echo "<script>alert('請先登入');location.href='index.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// 獲取並驗證 POST 資料
$old_pwd = isset($_POST['old_pwd']) ? $_POST['old_pwd'] : '';
$new_pwd = isset($_POST['new_pwd']) ? $_POST['new_pwd'] : '';
$confirm_pwd = isset($_POST['confirm_pwd']) ? $_POST['confirm_pwd'] : '';

// 檢查新密碼與確認密碼是否一致
if ($new_pwd !== $confirm_pwd) {
    echo "<script>alert('新密碼不一致');location.href='change_password.php';</script>";
    exit();
}

// 獲取使用者現有密碼
$sql = "SELECT `Upwd` FROM `user` WHERE `Uid` = ?";
$stmt = $db_link->prepare($sql);
if ($stmt === false) {
    echo "錯誤準備語句: " . $db_link->error;
    exit();
}
$stmt->bind_param("s", $user_id);
$stmt->execute();
$stmt->bind_result($current_pwd);
$stmt->fetch();
$stmt->close();

// 驗證原密碼
if ($old_pwd === $current_pwd) {
    // 更新密碼
    $update_sql = "UPDATE `user` SET `Upwd` = ? WHERE `Uid` = ?";
    $update_stmt = $db_link->prepare($update_sql);
    if ($update_stmt === false) {
        echo "錯誤準備語句: " . $db_link->error;
        exit();
    }
    $update_stmt->bind_param("ss", $new_pwd, $user_id);
    if ($update_stmt->execute()) {
        echo "<script>alert('密碼修改成功');location.href='user.php';</script>";
        exit();
    } else {
        echo "<script>alert('密碼修改失敗，請稍後再試。');location.href='change_password.php';</script>";
        exit();
    }
    $update_stmt->close();
} else {
    // 原密碼不正確
    echo "<script>alert('原密碼不正確');location.href='change_password.php';</script>";
    exit();
}

$db_link->close();
?>
