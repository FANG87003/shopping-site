<?php
session_start();
include "../config.php";

// 檢查是否已登入
if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
    echo "<script>alert('請先登入');location.href='../index.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$upload_error = '';

// 使用預備語句獲取使用者資料，包括 Uhead
$sql = "SELECT `Uhead` FROM `user` WHERE `Uid` = ?";
$stmt = $db_link->prepare($sql);
if ($stmt === false) {
    die("錯誤準備語句: " . $db_link->error);
}
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['avatar']['tmp_name'];
        $file_name = $_FILES['avatar']['name'];
        $file_size = $_FILES['avatar']['size'];
        $file_type = mime_content_type($file_tmp_path);
        $file_name_cmps = explode(".", $file_name);
        $file_extension = strtolower(end($file_name_cmps));

        // 設定允許的檔案類型
        $allowedfileExtensions = array('jpg', 'jpeg', 'png', 'gif');

        if (in_array($file_extension, $allowedfileExtensions)) {
            // 檢查檔案大小（例如限制為10MB）
            if ($file_size < 10 * 1024 * 1024) { // 10MB
                // 讀取檔案內容
                $img_data = file_get_contents($file_tmp_path);

                // 創建圖像資源
                $image = imagecreatefromstring($img_data);
                if ($image === false) {
                    $upload_error = "無法處理上傳的圖像。";
                } else {
                    // 轉換為 JPEG 格式
                    ob_start();
                    imagejpeg($image, null, 90); // 轉換並壓縮品質為 90
                    $jpeg_data = ob_get_clean();
                    imagedestroy($image); // 釋放圖像資源

                    // 使用預備語句更新 Uhead 欄位
                    $sql = "UPDATE `user` SET `Uhead` = ? WHERE `Uid` = ?";
                    $stmt = $db_link->prepare($sql);
                    if ($stmt === false) {
                        $upload_error = "錯誤準備語句: " . $db_link->error;
                    } else {
                        // 因為 Uhead 是 BLOB，需要使用 bind_param 的 'b' type
                        $stmt->bind_param("bs", $null, $user_id);

                        // 送出資料
                        $null = NULL;
                        $stmt->send_long_data(0, $jpeg_data);

                        if ($stmt->execute()) {
                            // 成功更新
                            echo "<script>alert('頭像上傳成功');location.href='user.php';</script>";
                            exit();
                        } else {
                            $upload_error = "更新頭像時出錯: " . $stmt->error;
                        }
                        $stmt->close();
                    }
                }
            } else {
                $upload_error = "檔案大小超過限制（10MB）。";
            }
        } else {
            $upload_error = "不允許的檔案類型。請上傳 JPG、JPEG、PNG 或 GIF 檔案。";
        }
    } else {
        $upload_error = "檔案上傳失敗。請稍後再試。";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>上傳頭像</title>
    <style>
        body {
            font-family: 'Microsoft JhengHei', sans-serif;
            background-color: #E3F2FD;
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
            width: 400px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center; /* 水平置中 */
        }

        h1 {
            color: #1976D2;
            margin-bottom: 20px;
        }

        input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #BDBDBD;
            border-radius: 5px;
            box-sizing: border-box;
        }

        input[type="submit"], input[type="button"] {
            background-color: #FF5252;
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

        .error {
            color: red;
            margin-bottom: 15px;
        }

        .preview-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin-bottom: 15px;
            object-fit: cover;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            /* 移除 display: none; 以確保預覽圖片顯示 */
            display: block;
        }
        
    </style>
    <script>
        function previewImage(event) {
            var output = document.getElementById('preview');
            var file = event.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    output.src = e.target.result;
                    // 確保圖片顯示
                    output.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                // 如果沒有選擇檔案，顯示現有頭像或預設頭像
                output.src = "<?php echo (!empty($row['Uhead'])) ? 'data:image/jpeg;base64,' . base64_encode($row['Uhead']) : 'default_avatar.jpg'; ?>";
                output.style.display = 'block';
            }
        }
    </script>
</head>
<body>
    <div class="form-container">
        <h1>上傳頭像</h1>
        <?php
            // 顯示使用者當前頭像
            if (!empty($row['Uhead'])) {
                echo "<img id='preview' class='preview-img' src='data:image/jpeg;base64," . base64_encode($row['Uhead']) . "' alt='頭像'>";
            } else {
                echo "<img id='preview' class='preview-img' src='default_avatar.jpg' alt='頭像'>"; // 預設頭像
            }

            if (!empty($upload_error)) {
                echo "<div class='error'>" . htmlspecialchars($upload_error, ENT_QUOTES, 'UTF-8') . "</div>";
            }
        ?>
        <form method="POST" action="upload_avatar.php" enctype="multipart/form-data">
            <input type="file" name="avatar" accept="image/*" required onchange="previewImage(event)"><br>
            <div>
                <input type="submit" value="上傳頭像">
                <input type="button" value="回會員中心" onclick="location.href='user.php';">
            </div>
        </form>
    </div>
</body>
</html>
