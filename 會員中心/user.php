<?php
session_start();
include "../config.php";

// 檢查是否已登入
if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 使用預備語句獲取使用者資料，包括 Uaddress
$sql = "SELECT `Unum`, `Uid`, `Uname`, `Uphone`, `Uemail`, `Usex`, `Uhead`, `Ubirth`, `Uaccess` FROM `user` WHERE `Uid` = ?";
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

// 根據 Uaccess 判斷使用者類型
$user_type = ($row['Uaccess'] === '1') ? '管理員' : '一般使用者';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>會員中心</title>
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
            min-height: 100vh;
        }

        .form-container {
            background: linear-gradient(to bottom, #FFFFFF, #BBDEFB); /* 添加漸層效果 */
            border-radius: 20px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
            padding: 40px;
            width: 90%;
            max-width: 600px; /* 增加最大寬度 */
            text-align: center;
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        h1 {
            color: #1976D2;
            margin-bottom: 20px;
            font-size: 28px; /* 增加字體大小 */
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        input[type="text"], input[type="email"], input[type="date"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #90CAF9;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 16px; /* 增加字體大小 */
        }

        input[readonly] {
            background-color: #F0F0F0;
            cursor: not-allowed;
        }

        .button-container {
            display: flex; /* 啟用 Flexbox */
            justify-content: center; /* 水平置中 */
            flex-wrap: wrap; /* 允許換行 */
            gap: 15px; /* 按鈕之間的間距 */
            margin-top: 20px; /* 與上方內容的間距 */
        }

        .back-button {
            padding: 12px 25px; /* 增加按鈕寬度 */
            background-color: #1976D2;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px; /* 增加字體大小 */
            transition: background-color 0.3s; /* 添加過渡效果 */
            text-decoration: none; /* 移除下劃線（若為 <a>） */
            text-align: center;
            display: inline-block; /* 讓按鈕橫向排列 */
            min-width: 120px; /* 設定最小寬度，確保一致性 */
        }

        .back-button:hover {
            background-color: #0D47A1;
        }

        .form-container img {
            width: 150px; /* 增加頭像大小 */
            height: 150px;
            border-radius: 50%;
            margin-bottom: 20px;
            object-fit: cover; /* 確保圖片不變形 */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .form-container input {
            transition: box-shadow 0.3s;
        }

        .form-container input:focus {
            outline: none;
            box-shadow: 0 0 8px #42A5F5;
        }

        /* 響應式設計：在小螢幕上調整按鈕排列 */
        @media (max-width: 500px) {
            .button-container {
                flex-direction: column;
                gap: 10px;
            }

            .back-button {
                width: 100%;
                max-width: 300px;
            }

            .form-container img {
                width: 120px;
                height: 120px;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>會員中心</h1>
        <?php
            // 顯示使用者頭像
            if (!empty($row['Uhead'])) {
                echo "<img src='data:image/jpeg;base64," . base64_encode($row['Uhead']) . "' alt='頭像'>";
            } else {
                echo "<img src='default_avatar.jpg' alt='頭像'>"; // 預設頭像
            }

            // 顯示會員資料
            echo "
                <form>
                    <label for='name'>姓名：</label>
                    <input type='text' id='name' name='name' value='" . htmlspecialchars($row['Uname'], ENT_QUOTES, 'UTF-8') . "' readonly><br>

                    <label for='id'>帳號：</label>
                    <input type='text' id='id' name='id' value='" . htmlspecialchars($row['Uid'], ENT_QUOTES, 'UTF-8') . "' readonly><br>

                    <label for='phone'>手機：</label>
                    <input type='text' id='phone' name='phone' value='" . htmlspecialchars($row['Uphone'], ENT_QUOTES, 'UTF-8') . "' readonly><br>

                    <label for='email'>Email：</label>
                    <input type='email' id='email' name='email' value='" . htmlspecialchars($row['Uemail'], ENT_QUOTES, 'UTF-8') . "' readonly><br>

                    <label for='birth'>生日：</label>
                    <input type='date' id='birth' name='birth' value='" . htmlspecialchars($row['Ubirth'], ENT_QUOTES, 'UTF-8') . "' readonly><br>

                    <label for='user_type'>使用者類型：</label>
                    <input type='text' id='user_type' name='user_type' value='" . htmlspecialchars($user_type, ENT_QUOTES, 'UTF-8') . "' readonly><br>

                    <div class='button-container'>
                        <input type='button' class='back-button' value='修改資料' onclick=\"location.href='user01.php';\">
                        <input type='button' class='back-button' value='修改密碼' onclick=\"location.href='change_password.php';\">
                        <input type='button' class='back-button' value='上傳頭像' onclick=\"location.href='upload_avatar.php';\">
                        <input type='button' class='back-button' value='我的訂單' onclick=\"location.href='../view_orders.php';\">
                        <input type='button' class='back-button' value='回首頁' onclick=\"location.href='../index.php';\">
                    </div>
                </form>
            ";
        ?>
    </div>
</body>
</html>
