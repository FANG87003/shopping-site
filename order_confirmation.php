<?php
session_start();
include "config.php";

// 檢查是否已登入
if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 獲取訂單 ID
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    echo "<script>alert('無效的訂單。');location.href='view_orders.php';</script>";
    exit();
}

// 獲取訂單資訊
$sql_order = "SELECT `order_id`, `order_date`, `total_amount`, `status`, `shipping_address`, `payment_method` 
              FROM `orders` 
              WHERE `order_id` = ? AND `user_id` = ?";
$stmt_order = $db_link->prepare($sql_order);
if ($stmt_order) {
    $stmt_order->bind_param("ii", $order_id, $user_id);
    $stmt_order->execute();
    $result_order = $stmt_order->get_result();
    $order = $result_order->fetch_assoc();
    $stmt_order->close();

    if (!$order) {
        error_log("訂單不存在或無權查看。訂單ID: $order_id, 使用者ID: $user_id");
        echo "<script>alert('訂單不存在或您無權查看此訂單。');location.href='view_orders.php';</script>";
        exit();
    }
} else {
    error_log("訂單查詢失敗。SQL錯誤：" . $db_link->error);
    echo "<script>alert('訂單查詢失敗。');location.href='view_orders.php';</script>";
    exit();
}

// 映射支付方式為中文
$payment_method_mapping = [
    '貨到付款' => '貨到付款',
];
$order['payment_method'] = $payment_method_mapping[$order['payment_method']] ?? $order['payment_method'];

// 映射訂單狀態為中文
$status_mapping = [
    'Pending' => '待出貨',
    'Shipped' => '已出貨',
    'Delivered' => '已送達',
    'Cancelled' => '已取消',
];
$order['status'] = $status_mapping[$order['status']] ?? $order['status'];

// 映射縣市為中文
$city_mapping = [
    'Taipei' => '台北市',
    'New Taipei' => '新北市',
    'Taichung' => '台中市',
    'Kaohsiung' => '高雄市',
    'Tainan' => '台南市',
    'Taoyuan' => '桃園市',
    'Hsinchu' => '新竹市',
    'Hsinchu County' => '新竹縣',
    'Miaoli' => '苗栗縣',
    'Changhua' => '彰化縣',
    'Nantou' => '南投縣',
    'Yunlin' => '雲林縣',
    'Chiayi' => '嘉義市',
    'Chiayi County' => '嘉義縣',
    'Pingtung' => '屏東縣',
    'Yilan' => '宜蘭縣',
    'Hualien' => '花蓮縣',
    'Taitung' => '台東縣',
    'Penghu' => '澎湖縣',
    'Kinmen' => '金門縣',
    'Matsu' => '連江縣',
];

// 修正收貨地址
$full_address = $order['shipping_address']; // 完整的收貨地址
$city_key = ''; // 紀錄城市部分

// 從地址中提取縣市名稱
foreach ($city_mapping as $eng_city => $chi_city) {
    if (strpos($full_address, $eng_city) === 0) {
        $city_key = $eng_city;
        break;
    }
}

// 如果找到縣市名稱，替換為中文
if ($city_key) {
    $order['shipping_address'] = str_replace($city_key, $city_mapping[$city_key], $full_address);
}

// 獲取訂單項目
$sql_items = "SELECT `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal` FROM `order_items` WHERE `order_id` = ?";
$stmt_items = $db_link->prepare($sql_items);
if ($stmt_items) {
    $stmt_items->bind_param("i", $order_id);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();
    $order_items = $result_items->fetch_all(MYSQLI_ASSOC);
    $stmt_items->close();
} else {
    echo "<script>alert('訂單項目查詢失敗。');location.href='view_orders.php';</script>";
    exit();
}

// 檢查是否需要顯示購買成功提示
$show_alert = false;
if (isset($_SESSION['purchase_success']) && $_SESSION['purchase_success'] === true) {
    $show_alert = true;
    unset($_SESSION['purchase_success']); // 取消 Session 變數
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>訂單確認</title>
    <style>
        /* 基本樣式，根據您的需求進行調整 */
        body {
            font-family: 'Microsoft JhengHei', sans-serif;
            background-color: #f9f9f9;
            padding: 20px;
        }
        .confirmation-container {
            max-width: 800px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #1976D2;
            margin-bottom: 20px;
            text-align: center;
        }
        .order-info, .order-items {
            margin-bottom: 30px;
        }
        .order-info p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table th, table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }
        table th {
            background-color: #1976D2;
            color: white;
        }
        .total {
            text-align: right;
            font-size: 1.2em;
            margin-top: 10px;
        }
        .button-container {
            display: flex; /* 使用 Flexbox */
            justify-content: center; /* 置中對齊 */
            gap: 20px; /* 按鈕之間的間距 */
            margin-top: 20px; /* 與上方內容的間距 */
        }
        .back-button {
            padding: 12px 20px;
            background-color: #1976D2;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s; /* 添加過渡效果 */
            text-decoration: none; /* 移除下劃線（若為 <a>） */
            text-align: center;
            display: inline-block; /* 讓按鈕橫向排列 */
        }
        .back-button:hover {
            background-color: #0D47A1;
        }
    </style>
</head>
<body>
    <?php if ($show_alert): ?>
        <script>
            alert('購買成功！');
        </script>
    <?php endif; ?>
    <div class="confirmation-container">
        <h2>訂單確認</h2>

        <!-- 訂單資訊 -->
        <div class="order-info">
            <h3>訂單資訊</h3>
            <p><strong>訂單編號：</strong> <?= htmlspecialchars($order['order_id']); ?></p>
            <p><strong>訂單日期：</strong> <?= htmlspecialchars($order['order_date']); ?></p>
            <p><strong>支付方式：</strong> <?= htmlspecialchars($order['payment_method']); ?></p>
            <p><strong>狀態：</strong> <?= htmlspecialchars($order['status']); ?></p>
            <p><strong>收貨地址：</strong> <?= nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
        </div>

        <!-- 訂單項目 -->
        <div class="order-items">
            <h3>訂單項目</h3>
            <table>
                <thead>
                    <tr>
                        <th>商品ID</th>
                        <th>商品名稱</th>
                        <th>數量</th>
                        <th>單價</th>
                        <th>小計</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['product_id']); ?></td>
                            <td><?= htmlspecialchars($item['product_name']); ?></td>
                            <td><?= htmlspecialchars($item['quantity']); ?></td>
                            <td>$<?= number_format($item['unit_price'], 2); ?></td>
                            <td>$<?= number_format($item['subtotal'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="total">
                <strong>總金額: $<?= number_format($order['total_amount'], 2); ?></strong>
            </div>
        </div>

        <!-- 返回按鈕容器 -->
        <div class="button-container">
            <button class="back-button" onclick="location.href='view_orders.php';">返回我的訂單</button>
            <button class="back-button" onclick="location.href='index.php';">返回首頁</button>
        </div>
    </div>
</body>
</html>
