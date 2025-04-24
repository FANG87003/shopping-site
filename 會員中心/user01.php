<?php
session_start();
include "../config.php";

// 檢查是否已登入
if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 使用預備語句獲取使用者資料
$sql = "SELECT `Unum`, `Uid`, `Upwd`, `Uname`, `Uphone`, `Uemail`, `Usex`, `Uhead`, `Ubirth`, `Uaccess` FROM `user` WHERE `Uid` = ?";
$stmt = $db_link->prepare($sql);
if ($stmt === false) {
    echo "錯誤準備語句: " . $db_link->error;
    exit();
}
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>會員資料修改</title>
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

        input[type="text"], input[type="email"], input[type="date"] {
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

        .form-container img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>修改基本資料</h1>
        <?php
            // 顯示使用者頭像
            if (!empty($row['Uhead'])) {
                echo "<img src='data:image/webp;base64," . base64_encode($row['Uhead']) . "' alt='頭像'>";
            } else {
                echo "<img src='default_avatar.jpg' alt='頭像'>"; // 預設頭像，確保格式為 WebP
            }

            // 顯示修改表單
            echo "
                <form method='POST' action='user02.php'>
                    <!-- 隱藏欄位傳遞帳號 (Uid) -->
                    <input type='hidden' name='id' value='" . htmlspecialchars($row['Uid'], ENT_QUOTES, 'UTF-8') . "'>

                    姓名：<input type='text' name='name' value='" . htmlspecialchars($row['Uname'], ENT_QUOTES, 'UTF-8') . "' required><br>
                    手機：<input type='text' name='phone' value='" . htmlspecialchars($row['Uphone'], ENT_QUOTES, 'UTF-8') . "' required><br>
                    Email：<input type='email' name='email' value='" . htmlspecialchars($row['Uemail'], ENT_QUOTES, 'UTF-8') . "' required><br>
                    生日：<input type='date' name='birth' value='" . htmlspecialchars($row['Ubirth'], ENT_QUOTES, 'UTF-8') . "'><br>
                    <input type='submit' value='確定'>
                    <input type='button' value='回會員中心' onclick=\"location.href='user.php';\">
                </form>
            ";
        ?>
    </div>
</body>
</html>
