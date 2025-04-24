<?php
session_start();
include "config.php";

// 檢查是否已登入
if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_access = isset($_SESSION['user_access']) ? $_SESSION['user_access'] : 0;

// 獲取商品 ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    die("無效的商品 ID。");
}

// 根據使用者權限調整 SQL 查詢
if ($user_access == 1) {
    // 管理員：無需檢查 owner
    $sql_product = "SELECT * FROM `products` WHERE `id` = ?";
    $stmt_product = $db_link->prepare($sql_product);
    if (!$stmt_product) {
        die("商品查詢準備語句錯誤: " . $db_link->error);
    }
    $stmt_product->bind_param("i", $product_id);
} else {
    // 普通使用者：檢查商品是否屬於自己
    $sql_product = "SELECT * FROM `products` WHERE `id` = ? AND `owner` = ?";
    $stmt_product = $db_link->prepare($sql_product);
    if (!$stmt_product) {
        die("商品查詢準備語句錯誤: " . $db_link->error);
    }
    $stmt_product->bind_param("is", $product_id, $user_id);
}

$stmt_product->execute();
$result_product = $stmt_product->get_result();
$product = $result_product->fetch_assoc();
$stmt_product->close();

if (!$product) {
    die("找不到該商品或您無權編輯此商品。");
}

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

// 獲取標籤名稱
$tags = [];
if (!empty($product['tag_ids'])) {
    $tag_names = array_filter(array_map('trim', explode(',', $product['tag_ids'])), function($tag) {
        return !empty($tag);
    });
    $tags = $tag_names;
}

// 獲取現有圖片
$existing_images = [];
$sql_existing_images = "SELECT * FROM `product_images` WHERE `product_id` = ?";
$stmt_existing_images = $db_link->prepare($sql_existing_images);
if ($stmt_existing_images) {
    $stmt_existing_images->bind_param("i", $product_id);
    $stmt_existing_images->execute();
    $result_existing_images = $stmt_existing_images->get_result();
    if ($row_images = $result_existing_images->fetch_assoc()) {
        $existing_images = $row_images;
    }
    $stmt_existing_images->close();
}

// 處理表單提交
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 獲取並驗證表單資料
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price_new = trim($_POST['price_new'] ?? '');
    $availability = $_POST['availability'] ?? '';
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
        $errors[] = "新價格為必填項。";
    } elseif (!is_numeric($price_new) || $price_new < 0) {
        $errors[] = "新價格必須是有效的正數。";
    }

    // 驗證庫存數量
    if ($availability === '') {
        $errors[] = "庫存數量為必填項。";
    } elseif (!ctype_digit($availability) || (int)$availability < 0) {
        $errors[] = "庫存數量必須是有效的非負整數。";
    }

    // 將 'availability' 轉換為整數
    $availability_int = (int)$availability;

    // 驗證分類
    $valid_categories = array_column($categories, 'id');
    if (empty($category) || !in_array($category, $valid_categories)) {
        $errors[] = "請選擇有效的分類。";
    } else {
        // 根據選取的分類 ID 獲取分類名稱
        $selected_category = '';
        foreach ($categories as $cat) {
            if ($cat['id'] == $category) {
                $selected_category = $cat['name']; // 使用名稱而非ID
                break;
            }
        }
        if (empty($selected_category)) {
            $errors[] = "選取的分類不存在。";
        }
    }

    // 處理標籤
    $new_tags = [];
    if (!empty($tags_input)) {
        // 分割以#開頭的標籤，支援Unicode字元
        preg_match_all('/#([\p{L}\p{N}_]+)/u', $tags_input, $matches);
        $new_tags = array_unique(array_map('trim', $matches[1])); // 去除重複並修剪空白
    }

    // 處理圖片上傳（可選）
    $images = [];
    $max_file_size = 5 * 1024 * 1024; // 5MB

    for ($i = 1; $i <= 4; $i++) {
        if (isset($_FILES["image$i"]) && $_FILES["image$i"]['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES["image$i"]['tmp_name'];

            // 檢查檔案是否為有效圖片
            $image_info = getimagesize($tmp_name);
            if ($image_info === false) {
                $errors[] = "圖片$i 的內容無效。";
                continue;
            }

            $mime_type = $image_info['mime'];
            $allowed_types = ['image/jpeg', 'image/pjpeg', 'image/png', 'image/gif'];

            if (!in_array($mime_type, $allowed_types)) {
                $errors[] = "圖片$i 的格式不正確。僅允許 JPG、PNG、GIF 格式。";
            } elseif ($_FILES["image$i"]['size'] > $max_file_size) {
                $errors[] = "圖片$i 的大小超過限制。每張圖片不得超過5MB。";
            } else {
                // 讀取圖片內容
                $image_data = file_get_contents($tmp_name);
                if ($image_data === false) {
                    $errors[] = "無法讀取圖片$i 的內容。";
                    continue;
                }
                $images[$i] = $image_data;
            }
        }
    }

    // 如果無錯誤，進行資料庫更新
    if (empty($errors)) {
        // 開始事務
        $db_link->begin_transaction();

        try {
            // 處理標籤
            $final_tags = [];
            foreach ($new_tags as $tag_name) {
                // 檢查是否已存在於 tags 資料表
                $sql_check_tag = "SELECT `name` FROM `tags` WHERE `name` = ?";
                $stmt_check_tag = $db_link->prepare($sql_check_tag);
                if (!$stmt_check_tag) {
                    throw new Exception("標籤查詢準備語句錯誤: " . $db_link->error);
                }
                $stmt_check_tag->bind_param("s", $tag_name);
                $stmt_check_tag->execute();
                $result_tag = $stmt_check_tag->get_result();
                if ($result_tag->num_rows === 0) {
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
                    $stmt_insert_tag->close();
                }
                $stmt_check_tag->close();

                // 添加到最終標籤陣列
                $final_tags[] = $tag_name;
            }

            // 將標籤名稱轉換為逗號分隔字串
            $tag_ids_str = implode(',', $final_tags);

            // 更新產品資料
            if ($user_access == 1) {
                // 管理員：不檢查 owner
                $sql_update_product = "UPDATE `products` 
                                        SET `name` = ?, `description` = ?, `price_new` = ?, `availability` = ?, `category_ids` = ?, `tag_ids` = ?
                                        WHERE `id` = ?";
                $stmt_update_product = $db_link->prepare($sql_update_product);
                if (!$stmt_update_product) {
                    throw new Exception("產品更新準備語句錯誤: " . $db_link->error);
                }

                // 綁定參數
                // "ssdsssi" 對應: name (s), description (s), price_new (d), availability_int (i), selected_category (s), tag_ids_str (s), product_id (i)
                $stmt_update_product->bind_param("ssdsssi", $name, $description, $price_new, $availability_int, $selected_category, $tag_ids_str, $product_id);
            } else {
                // 普通使用者：檢查 owner
                $sql_update_product = "UPDATE `products` 
                                        SET `name` = ?, `description` = ?, `price_new` = ?, `availability` = ?, `category_ids` = ?, `tag_ids` = ?
                                        WHERE `id` = ? AND `owner` = ?";
                $stmt_update_product = $db_link->prepare($sql_update_product);
                if (!$stmt_update_product) {
                    throw new Exception("產品更新準備語句錯誤: " . $db_link->error);
                }

                // 綁定參數
                // "ssdissis" 對應: name (s), description (s), price_new (d), availability_int (i), selected_category (s), tag_ids_str (s), product_id (i), user_id (s)
                $stmt_update_product->bind_param("ssdissis", $name, $description, $price_new, $availability_int, $selected_category, $tag_ids_str, $product_id, $user_id);
            }

            if (!$stmt_update_product->execute()) {
                throw new Exception("產品更新執行錯誤: " . $stmt_update_product->error);
            }
            $stmt_update_product->close();

            // 處理圖片更新
            if (!empty($images)) {
                // 檢查是否已有圖片
                $sql_check_images = "SELECT `id` FROM `product_images` WHERE `product_id` = ?";
                $stmt_check_images = $db_link->prepare($sql_check_images);
                if (!$stmt_check_images) {
                    throw new Exception("產品圖片查詢準備語句錯誤: " . $db_link->error);
                }
                $stmt_check_images->bind_param("i", $product_id);
                $stmt_check_images->execute();
                $result_check_images = $stmt_check_images->get_result();
                $has_images = $result_check_images->num_rows > 0;
                $stmt_check_images->close();

                if ($has_images) {
                    // 更新現有的圖片
                    $sql_update_images = "UPDATE `product_images` SET `image1` = ?, `image2` = ?, `image3` = ?, `image4` = ? WHERE `product_id` = ?";
                    $stmt_update_images = $db_link->prepare($sql_update_images);
                    if (!$stmt_update_images) {
                        throw new Exception("產品圖片更新準備語句錯誤: " . $db_link->error);
                    }

                    // 保留未更新的圖片
                    $current_images = [
                        'image1' => $existing_images['image1'] ?? null,
                        'image2' => $existing_images['image2'] ?? null,
                        'image3' => $existing_images['image3'] ?? null,
                        'image4' => $existing_images['image4'] ?? null,
                    ];

                    // 如果有新圖片上傳，替換相應的圖片
                    foreach ($images as $i => $image_data) {
                        $current_images["image$i"] = $image_data;
                    }

                    $stmt_update_images->bind_param(
                        "ssssi",
                        $current_images['image1'],
                        $current_images['image2'],
                        $current_images['image3'],
                        $current_images['image4'],
                        $product_id
                    );

                    if (!$stmt_update_images->execute()) {
                        throw new Exception("產品圖片更新執行錯誤: " . $stmt_update_images->error);
                    }
                    $stmt_update_images->close();
                } else {
                    // 插入新的圖片
                    $sql_insert_images = "INSERT INTO `product_images` (`product_id`, `image1`, `image2`, `image3`, `image4`)
                                          VALUES (?, ?, ?, ?, ?)";
                    $stmt_insert_images = $db_link->prepare($sql_insert_images);
                    if (!$stmt_insert_images) {
                        throw new Exception("產品圖片插入準備語句錯誤: " . $db_link->error);
                    }

                    $image1 = $images[1] ?? null;
                    $image2 = $images[2] ?? null;
                    $image3 = $images[3] ?? null;
                    $image4 = $images[4] ?? null;

                    $stmt_insert_images->bind_param("issss", $product_id, $image1, $image2, $image3, $image4);
                    if (!$stmt_insert_images->execute()) {
                        throw new Exception("產品圖片插入執行錯誤: " . $stmt_insert_images->error);
                    }
                    $stmt_insert_images->close();
                }
            }

            // 提交事務
            $db_link->commit();
            $success = true;

            // 根據使用者權限重定向
            if ($user_access == 1) {
                // 管理員重定向到管理中心
                header("Location: admin_mana.php?message=updated");
            } else {
                // 普通使用者重定向到我的賣場
                header("Location: my_store.php?message=updated");
            }
            exit();

        } catch (Exception $e) {
            // 回滾事務
            $db_link->rollback();
            $errors[] = "更新商品失敗: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>編輯商品 - 我的賣場</title>
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
            overflow-y: auto;
            max-height: 90vh;
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

        .current-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .current-images img {
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
                    if (input.files[0].size > 5 * 1024 * 1024) { // 5MB
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
        <h2>編輯商品 - 我的賣場</h2>

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
                商品已成功更新！
            </div>
        <?php endif; ?>

        <form action="edit_product.php?id=<?= htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>" method="POST" enctype="multipart/form-data">
            <label for="name">商品名稱：</label>
            <input type="text" id="name" name="name" maxlength="50" required value="<?= htmlspecialchars($_POST['name'] ?? $product['name'], ENT_QUOTES, 'UTF-8'); ?>">

            <label for="description">商品介紹：</label>
            <textarea id="description" name="description" maxlength="150" required><?= htmlspecialchars($_POST['description'] ?? $product['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>

            <label for="price_new">價格：</label>
            <input type="number" id="price_new" name="price_new" min="0" step="0.01" required value="<?= htmlspecialchars($_POST['price_new'] ?? $product['price_new'], ENT_QUOTES, 'UTF-8'); ?>">

            <label for="availability">庫存：</label>
            <input type="number" id="availability" name="availability" min="0" step="1" required value="<?= htmlspecialchars($_POST['availability'] ?? $product['availability'], ENT_QUOTES, 'UTF-8'); ?>">

            <label for="category">分類：</label>
            <select id="category" name="category" required>
                <option value="">-- 請選擇分類 --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['id'], ENT_QUOTES, 'UTF-8'); ?>" <?= (
                        (isset($_POST['category']) && $_POST['category'] == $cat['id']) || 
                        ($product['category_ids'] === $cat['name'])
                    ) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="tags">標籤：</label>
            <input type="text" id="tags" name="tags" class="tags-input" placeholder="#標籤1 #標籤2" value="<?= htmlspecialchars($_POST['tags'] ?? implode(' ', array_map(function($tag) { return '#' . $tag; }, $tags)), ENT_QUOTES, 'UTF-8'); ?>">

            <label>現有商品圖片：</label>
            <?php if (!empty($existing_images)): ?>
                <div class="current-images">
                    <?php for ($i = 1; $i <=4; $i++): ?>
                        <?php if (!empty($existing_images["image$i"])): ?>
                            <img src="data:image/jpeg;base64,<?= base64_encode($existing_images["image$i"]); ?>" alt="商品圖片<?= $i; ?>">
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php else: ?>
                <p>無現有商品圖片。</p>
            <?php endif; ?>

            <label>更新商品圖片：</label>
            <div class="image-upload">
                <p>若要更新圖片，請選擇新圖片。留空則保留現有圖片。</p>
                <input type="file" name="image1" accept="image/*" onchange="previewImages()">
                <input type="file" name="image2" accept="image/*" onchange="previewImages()">
                <input type="file" name="image3" accept="image/*" onchange="previewImages()">
                <input type="file" name="image4" accept="image/*" onchange="previewImages()">
            </div>

            <!-- 圖片預覽區 -->
            <div id="imagePreview" class="image-preview"></div>

            <div class="button-container">
                <button type="submit" class="submit-button">更新商品</button>
                <?php if ($user_access == 1): ?>
                    <button type="button" class="back-button" onclick="location.href='admin_mana.php';">返回管理中心</button>
                <?php else: ?>
                    <button type="button" class="back-button" onclick="location.href='my_store.php';">返回我的賣場</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</body>
</html>
