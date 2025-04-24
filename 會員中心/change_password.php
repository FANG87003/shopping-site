<?php
session_start();
include "../config.php";

// 檢查是否已登入
if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
    echo "<script>alert('請先登入');location.href='../index.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>修改密碼</title>
    <style>
        body {
            font-family: 'Microsoft JhengHei', sans-serif;
            background-color: #E3F2FD; /* 哆啦A夢的淺藍色 */
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .form-container {
            background: #FFFFFF;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            padding: 30px;
            width: 350px;
            text-align: center;
        }

        h1 {
            color: #1976D2;
            margin-bottom: 20px;
        }

        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #BDBDBD;
            border-radius: 5px;
            box-sizing: border-box;
        }

        input[type="submit"], input[type="button"] {
            background-color: #FF5252; /* 鈴鐺紅色 */
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 10px;
            margin-right: 10px;
        }

        input[type="submit"]:hover, input[type="button"]:hover {
            background-color: #E53935;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>修改密碼</h1>
        <form method="POST" action="change_password_process.php">
            原密碼：<input type="password" name="old_pwd" required><br>
            新密碼：<input type="password" name="new_pwd" required><br>
            確認新密碼：<input type="password" name="confirm_pwd" required><br>
            <input type="submit" value="確定">
            <input type="button" value="回會員中心" onclick="location.href='user.php';">
        </form>
    </div>
</body>
</html>
