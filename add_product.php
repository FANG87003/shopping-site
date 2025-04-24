<?php
session_start();
include "config.php";

// 檢查是否已登入
if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; // 假設這是完整的 Uid，例如 '3B032053'

// 獲取所有分類
$sql_categories = "SELECT `id`, `name` FROM `categories`";
$result_categories = $db_link->query($sql_categories);
$categories = [];
if ($result_categories) {
    while ($row = $result_categories->fetch_assoc()) {
        $categories[] = $row;
    }
} else {
    die("無法取得分類資料: " . $db_link->error);
}

// 處理表單提交
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 獲取並驗證表單資料
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price_new = trim($_POST['price_new'] ?? '');
    $availability = trim($_POST['availability'] ?? '');
    $category = $_POST['category'] ?? '';
    $tags_input = trim($_POST['tags'] ?? '');

    // 驗證名稱
    if (empty($name)) {
        $errors[] = "商品名稱為必填項。";
    } elseif (mb_strlen($name) > 50) {
        $errors[] = "商品名稱不得超過50個字元。";
    }

    // 驗證描述
    if (empty($description)) {
        $errors[] = "商品描述為必填項。";
    } elseif (mb_strlen($description) > 150) {
        $errors[] = "商品描述不得超過150個字元。";
    }

    // 驗證價格
    if (empty($price_new)) {
        $errors[] = "價格為必填項。";
    } elseif (!is_numeric($price_new) || $price_new < 0) {
        $errors[] = "價格必須是有效的正數。";
    }

    // 驗證庫存數量
    if ($availability === '') { // 允許 '0' 作為有效輸入
        $errors[] = "庫存數量為必填項。";
    } elseif (!ctype_digit($availability) || (int)$availability < 0) {
        $errors[] = "庫存數量必須是有效的非負整數。";
    }

    // 驗證分類
    $valid_categories = array_column($categories, 'id');
    if (empty($category) || !in_array($category, $valid_categories)) {
        $errors[] = "請選擇有效的分類。";
    } else {
        // 根據選取的分類 ID 獲取分類名稱
        $selected_category = '';
        foreach ($categories as $cat) {
            if ($cat['id'] == $category) {
                $selected_category = $cat['name'];
                break;
            }
        }
        if (empty($selected_category)) {
            $errors[] = "選取的分類不存在。";
        }
    }

    // 處理標籤
    $tags = [];
    if (!empty($tags_input)) {
        // 分割以#開頭的標籤，支援Unicode字元
        preg_match_all('/#([\p{L}\p{N}_]+)/u', $tags_input, $matches);
        $tags = array_unique($matches[1]); // 去除重複
    }

    // 處理圖片上傳
    $images = [];
    $uploaded_image_count = 0;
    $max_file_size = 20 * 1024 * 1024; // 5MB

    for ($i = 1; $i <= 4; $i++) {
        if (isset($_FILES["image$i"]) && $_FILES["image$i"]['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES["image$i"]['tmp_name'];
            $file_size = $_FILES["image$i"]['size'];
            $allowed_types = ['image/jpeg', 'image/pjpeg', 'image/png', 'image/gif'];

            // 檢查檔案是否為有效圖片
            $image_info = getimagesize($tmp_name);
            if ($image_info === false) {
                $errors[] = "圖片$i 的內容無效。";
                continue;
            }

            $mime_type = $image_info['mime'];

            if (!in_array($mime_type, $allowed_types)) {
                $errors[] = "圖片$i 的格式不正確。僅允許 JPG、PNG、GIF 格式。";
            } elseif ($file_size > $max_file_size) {
                $errors[] = "圖片$i 的大小超過限制。每張圖片不得超過5MB。";
            } else {
                // 讀取圖片內容
                $image_data = file_get_contents($tmp_name);
                if ($image_data === false) {
                    $errors[] = "無法讀取圖片$i 的內容。";
                    continue;
                }
                $images[$i] = $image_data;
                $uploaded_image_count++;
            }
        }
    }

    // 驗證至少上傳兩張圖片
    if ($uploaded_image_count < 2) {
        $errors[] = "請至少上傳兩張商品圖片。";
    }

    // 如果無錯誤，進行資料庫插入
    if (empty($errors)) {
        // 開始事務
        $db_link->begin_transaction();

        try {
            // 處理標籤
            $tag_names = [];
            foreach ($tags as $tag_name) {
                // 檢查標籤是否已存在
                $sql_check_tag = "SELECT `name` FROM `tags` WHERE `name` = ?";
                $stmt_check_tag = $db_link->prepare($sql_check_tag);
                if (!$stmt_check_tag) {
                    throw new Exception("標籤查詢準備語句錯誤: " . $db_link->error);
                }
                $stmt_check_tag->bind_param("s", $tag_name);
                $stmt_check_tag->execute();
                $result_tag = $stmt_check_tag->get_result();
                if ($row_tag = $result_tag->fetch_assoc()) {
                    // 如果標籤已存在，直接使用名稱
                    $tag_names[] = $row_tag['name'];
                } else {
                    // 插入新標籤
                    $sql_insert_tag = "INSERT INTO `tags` (`name`) VALUES (?)";
                    $stmt_insert_tag = $db_link->prepare($sql_insert_tag);
                    if (!$stmt_insert_tag) {
                        throw new Exception("標籤插入準備語句錯誤: " . $db_link->error);
                    }
                    $stmt_insert_tag->bind_param("s", $tag_name);
                    if (!$stmt_insert_tag->execute()) {
                        throw new Exception("標籤插入執行錯誤: " . $stmt_insert_tag->error);
                    }
                    $tag_names[] = $tag_name; // 使用名稱而非 ID
                    $stmt_insert_tag->close();
                }
                $stmt_check_tag->close();
            }

            // 將標籤名稱轉換為逗號分隔的字串
            $tag_ids = implode(',', $tag_names);

            // 插入產品資料
            $sql_insert_product = "INSERT INTO `products` (`name`, `description`, `price_new`, `availability`, `Psales`, `category_ids`, `tag_ids`, `owner`)
                                   VALUES (?, ?, ?, ?, 0, ?, ?, ?)";
            $stmt_product = $db_link->prepare($sql_insert_product);
            if (!$stmt_product) {
                throw new Exception("產品插入準備語句錯誤: " . $db_link->error);
            }

            // 插入產品
            // 修改 bind_param 類型：
            // s - name (string)
            // s - description (string)
            // d - price_new (double)
            // i - availability (integer)
            // s - category_ids (string, category name)
            // s - tag_ids (string, comma-separated tag names)
            // s - owner (string, full UID)
            $stmt_product->bind_param("ssdssss", $name, $description, $price_new, $availability, $selected_category, $tag_ids, $user_id);
            if (!$stmt_product->execute()) {
                throw new Exception("產品插入執行錯誤: " . $stmt_product->error);
            }
            $product_id = $stmt_product->insert_id;
            $stmt_product->close();

            // 插入產品圖片
            if (!empty($images)) {
                $sql_insert_images = "INSERT INTO `product_images` (`product_id`, `image1`, `image2`, `image3`, `image4`)
                                      VALUES (?, ?, ?, ?, ?)";
                $stmt_images = $db_link->prepare($sql_insert_images);
                if (!$stmt_images) {
                    throw new Exception("產品圖片插入準備語句錯誤: " . $db_link->error);
                }

                // 根據上傳的圖片填充
                // 如果圖片未上傳，存入 NULL
                $image1 = isset($images[1]) ? $images[1] : null;
                $image2 = isset($images[2]) ? $images[2] : null;
                $image3 = isset($images[3]) ? $images[3] : null;
                $image4 = isset($images[4]) ? $images[4] : null;

                // bind_param 無法直接綁定 NULL，需要使用特殊處理
                // 這裡假設資料庫允許 NULL，因此使用 null

                $stmt_images->bind_param(
                    "issss",
                    $product_id,
                    $image1,
                    $image2,
                    $image3,
                    $image4
                );

                if (!$stmt_images->execute()) {
                    throw new Exception("產品圖片插入執行錯誤: " . $stmt_images->error);
                }
                $stmt_images->close();
            }

            // 提交事務
            $db_link->commit();
            $success = true;

            // 清除表單資料
            $_POST = [];
        } catch (Exception $e) {
            // 回滾事務
            $db_link->rollback();
            $errors[] = "上架商品失敗: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>上架商品 - 我的賣場</title>
    <style>
        body {
            font-family: 'Microsoft JhengHei', sans-serif;
            background-color: #f0f4f8;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .form-container {
            background: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 90%;
            max-width: 600px;
        }

        h2 {
            text-align: center;
            color: #1976D2;
            margin-bottom: 30px;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        input[type="text"],
        input[type="number"],
        textarea,
        select {
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #90CAF9;
            border-radius: 8px;
            font-size: 16px;
            resize: vertical;
        }

        textarea {
            height: 100px;
        }

        .tags-input {
            padding: 10px;
            border: 1px solid #90CAF9;
            border-radius: 8px;
            font-size: 16px;
            margin-bottom: 20px;
        }

        .image-upload {
            margin-bottom: 20px;
        }

        .image-upload input[type="file"] {
            display: block;
            margin-top: 5px;
        }

        .image-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .image-preview img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .button-container {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .submit-button {
            padding: 12px 25px;
            background-color: #1976D2;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            min-width: 120px;
        }

        .submit-button:hover {
            background-color: #0D47A1;
        }

        .back-button {
            padding: 12px 25px;
            background-color: #757575;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            min-width: 120px;
        }

        .back-button:hover {
            background-color: #424242;
        }

        .error-messages {
            background-color: #FFCDD2;
            color: #B71C1C;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .success-message {
            background-color: #C8E6C9;
            color: #2E7D32;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
    <!-- 添加 JavaScript 來預覽圖片 -->
    <script>
        function previewImages() {
            const imageInputs = document.querySelectorAll('input[type="file"]');
            const previewContainer = document.getElementById('imagePreview');
            previewContainer.innerHTML = ''; // 清除之前的預覽

            imageInputs.forEach(input => {
                if (input.files && input.files[0]) {
                    // 檢查檔案大小（例如，限制為 5MB）
                    if (input.files[0].size > 20 * 1024 * 1024) { // 5MB
                        alert('檔案太大，請選擇小於5MB的圖片。');
                        input.value = ''; // 清除選擇
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        previewContainer.appendChild(img);
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            });
        }
    </script>
</head>
<body>
    <div class="form-container">
        <h2>上架商品 - 我的賣場</h2>

        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                商品已成功上架！
            </div>
        <?php endif; ?>

        <!-- 移除 oninput="previewImages()" -->
        <form action="add_product.php" method="POST" enctype="multipart/form-data">
            <label for="name">商品名稱：</label>
            <input type="text" id="name" name="name" maxlength="50" required value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

            <label for="description">商品描述：</label>
            <textarea id="description" name="description" maxlength="150" required><?= htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>

            <label for="price_new">價格：</label>
            <input type="number" id="price_new" name="price_new" min="0" step="0.01" required value="<?= htmlspecialchars($_POST['price_new'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

            <label for="availability">庫存數量：</label>
            <input type="number" id="availability" name="availability" min="0" step="1" required value="<?= htmlspecialchars($_POST['availability'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

            <label for="category">分類：</label>
            <select id="category" name="category" required>
                <option value="">-- 請選擇分類 --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['id'], ENT_QUOTES, 'UTF-8'); ?>" <?= (isset($_POST['category']) && $_POST['category'] == $cat['id']) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="tags">標籤：</label>
            <input type="text" id="tags" name="tags" class="tags-input" placeholder="以#開頭，EX:#真香#得來速#小貓" value="<?= htmlspecialchars($_POST['tags'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

            <label>商品圖片：</label>
            <div class="image-upload">
                <input type="file" name="image1" accept="image/*" onchange="previewImages()" required>
                <input type="file" name="image2" accept="image/*" onchange="previewImages()" required>
                <input type="file" name="image3" accept="image/*" onchange="previewImages()">
                <input type="file" name="image4" accept="image/*" onchange="previewImages()">
            </div>

            <!-- 圖片預覽區 -->
            <div id="imagePreview" class="image-preview"></div>

            <div class="button-container">
                <button type="submit" class="submit-button">上架商品</button>
                <button type="button" class="back-button" onclick="location.href='my_store.php';">返回我的賣場</button>
            </div>
        </form>
    </div>
</body>
</html>
