<?php
session_start();
include "config.php";

// 檢查是否已登入且是管理員
if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true || !isset($_SESSION['user_access']) || $_SESSION['user_access'] != 1) {
    echo "<script>alert('您沒有權限訪問此頁面');location.href='index.php';</script>";
    exit();
}

// 獲取所有商品
$sql_products = "SELECT * FROM `products` ORDER BY `id` DESC";
$result_products = $db_link->query($sql_products);
if (!$result_products) {
    die("商品查詢錯誤: " . $db_link->error);
}
$products = $result_products->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>管理中心</title>
    <style>
        body {
            font-family: 'Microsoft JhengHei', sans-serif;
            background-color: #f0f4f8;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: #D32F2F;
            margin-bottom: 30px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .product-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            background-color: #fff;
        }

        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .product-info {
            padding: 15px;
            flex-grow: 1;
        }

        .product-info h3 {
            margin: 0 0 10px 0;
            color: #D32F2F;
            font-size: 20px;
        }

        .product-info p {
            margin: 5px 0;
            color: #555;
            font-size: 14px;
        }

        .product-info .price {
            font-size: 1.2em;
            color: #D32F2F;
            margin: 10px 0;
        }

        .product-info .availability {
            color: #388E3C;
        }

        .product-info .tags {
            margin-top: 10px;
        }

        .tag {
            display: inline-block;
            background-color: #E3F2FD;
            color: #1976D2;
            padding: 5px 10px;
            border-radius: 15px;
            margin-right: 5px;
            font-size: 0.9em;
        }

        .product-actions {
            display: flex;
            justify-content: space-between;
            padding: 15px;
            border-top: 1px solid #ddd;
        }

        .action-button {
            padding: 8px 12px;
            background-color: #1976D2;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }

        .action-button:hover {
            background-color: #0D47A1;
        }

        /* 新增的樣式：按鈕群組 */
        .button-group {
            display: flex;
            justify-content: center;
            gap: 20px; /* 按鈕之間的間距 */
            margin: 30px 0 0 0; /* 上方間距 */
        }

        .add-button {
            padding: 12px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            text-align: center;
            text-decoration: none;
            width: 200px; /* 固定寬度 */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .add-button:hover {
            background-color: #388E3C;
        }

        @media (max-width: 600px) {
            .product-card img {
                height: 150px;
            }

            .button-group {
                flex-direction: column;
                gap: 10px;
            }

            .add-button {
                width: 100%;
            }
        }

        /* 管理員專用的額外樣式 */
        .delete-button {
            background-color: #D32F2F;
        }

        .delete-button:hover {
            background-color: #B71C1C;
        }

        .edit-button {
            background-color: #1976D2;
        }

        .edit-button:hover {
            background-color: #0D47A1;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>管理中心</h2>

        <div class="products-grid">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <?php
                        // 獲取產品圖片
                        $sql_image = "SELECT * FROM `product_images` WHERE `product_id` = ?";
                        $stmt_image = $db_link->prepare($sql_image);
                        if ($stmt_image) {
                            $stmt_image->bind_param("i", $product['id']);
                            $stmt_image->execute();
                            $result_image = $stmt_image->get_result();
                            $image = $result_image->fetch_assoc();
                            $stmt_image->close();
                        }

                        // 取得第一張圖片作為展示
                        $image_src = 'default_product.jpg'; // 預設圖片
                        if (isset($image) && !empty($image['image1'])) {
                            $image_src = 'data:image/jpeg;base64,' . base64_encode($image['image1']);
                        }
                    ?>
                    <div class="product-card">
                        <!-- 將圖片包裹在 <a> 標籤中，連結到 product.php?id=PRODUCT_ID -->
                        <a href="product.php?id=<?= htmlspecialchars($product['id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <img src="<?= htmlspecialchars($image_src); ?>" alt="商品圖片">
                        </a>
                        <div class="product-info">
                            <!-- 將標題包裹在 <a> 標籤中，連結到 product.php?id=PRODUCT_ID -->
                            <h3>
                                <a href="product.php?id=<?= htmlspecialchars($product['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?= htmlspecialchars($product['name']); ?>
                                </a>
                            </h3>
                            <p><strong>商品介紹：</strong><?= htmlspecialchars($product['description']); ?></p>
                            <p><strong>價格：</strong>NT <?= number_format($product['price_new']); ?></p>
                            <p><strong>庫存：</strong><?= htmlspecialchars($product['availability']); ?></p>
                            <p><strong>銷量：</strong><?= htmlspecialchars($product['Psales']); ?></p>
                            <p><strong>分類：</strong>
                                <?php
                                    if (!empty($product['category_ids'])) {
                                        echo htmlspecialchars($product['category_ids'], ENT_QUOTES, 'UTF-8');
                                    } else {
                                        echo '未分類';
                                    }
                                ?>
                            </p>
                            <p><strong>標籤：</strong>
                                <?php
                                    if (!empty($product['tag_ids'])) {
                                        $tags = explode(',', $product['tag_ids']);
                                        $formatted_tags = array_map(function($tag) {
                                            return '#' . htmlspecialchars($tag, ENT_QUOTES, 'UTF-8');
                                        }, $tags);
                                        echo implode(' ', $formatted_tags);
                                    } else {
                                        echo '無標籤';
                                    }
                                ?>
                            </p>
                        </div>
                        <div class="product-actions">
                            <a class="action-button edit-button" href="edit_product.php?id=<?= htmlspecialchars($product['id']); ?>">編輯</a>
                            <a class="action-button delete-button" href="delete_product.php?id=<?= htmlspecialchars($product['id']); ?>" onclick="return confirm('確定要刪除此商品嗎？');">刪除</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>目前沒有任何商品。</p>
            <?php endif; ?>
        </div>

        <!-- 管理員專用的新增商品按鈕 -->
        <div class="button-group">
            <a href="add_product.php" class="add-button">新增商品</a>
            <a href="index.php" class="add-button">回首頁</a>
        </div>
    </div>
</body>
</html>
