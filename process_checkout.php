<?php
session_start();
include "config.php";

// 檢查是否已登入
if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 檢查購物車是否有商品
if (empty($_SESSION['cart'])) {
    echo "<script>alert('您的購物車沒有商品。');location.href='cart.php';</script>";
    exit();
}

// 獲取並驗證表單資料
$shipping_city = isset($_POST['shipping_city']) ? trim($_POST['shipping_city']) : '';
$shipping_township = isset($_POST['shipping_township']) ? trim($_POST['shipping_township']) : '';
$shipping_detail = isset($_POST['shipping_detail']) ? trim($_POST['shipping_detail']) : '';
$payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : '';

$errors = [];

// 驗證收貨地址
if (empty($shipping_city)) {
    $errors[] = "請選擇縣市。";
}
if (empty($shipping_township)) {
    $errors[] = "請選擇鄉鎮市區。";
}
if (empty($shipping_detail)) {
    $errors[] = "請輸入詳細地址。";
}

// 驗證支付方式
$allowed_payment_methods = ['Cash on Delivery'];
if (empty($payment_method) || !in_array($payment_method, $allowed_payment_methods)) {
    $errors[] = "請選擇有效的支付方式。";
}

if (!empty($errors)) {
    // 顯示錯誤訊息並返回結帳頁面
    echo "<script>alert('" . implode("\\n", $errors) . "');history.back();</script>";
    exit();
}

// 映射縣市和鄉鎮市區為中文
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
$shipping_city_display = $city_mapping[$shipping_city] ?? $shipping_city;

// 映射支付方式為中文
$payment_method_mapping = [
    'Cash on Delivery' => '貨到付款',
];
$payment_method_display = $payment_method_mapping[$payment_method] ?? $payment_method;

// 組合完整收貨地址
$shipping_address = $shipping_city_display . $shipping_township . $shipping_detail;

// 取得購物車內容和總金額
$cart_items = $_SESSION['cart'];
$total_price = 0;
foreach ($cart_items as $item) {
    $total_price += $item['price'] * $item['quantity'];
}

// 開始事務
$db_link->begin_transaction();

try {
    // 插入訂單
    $sql_order = "INSERT INTO `orders` (`user_id`, `total_amount`, `status`, `shipping_address`, `payment_method`) 
                  VALUES (?, ?, 'Pending', ?, ?)";
    $stmt_order = $db_link->prepare($sql_order);
    if (!$stmt_order) {
        throw new Exception("訂單準備語句錯誤: " . $db_link->error);
    }

    $stmt_order->bind_param("sdss", $user_id, $total_price, $shipping_address, $payment_method_display);

    if (!$stmt_order->execute()) {
        throw new Exception("訂單執行錯誤: " . $stmt_order->error);
    }
    $order_id = $stmt_order->insert_id;
    $stmt_order->close();

    // 插入訂單項目
    $sql_item = "INSERT INTO `order_items` (`order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`)
                 VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_item = $db_link->prepare($sql_item);
    if (!$stmt_item) {
        throw new Exception("訂單項目準備語句錯誤: " . $db_link->error);
    }

    foreach ($cart_items as $item) {
        $product_id = $item['id'];
        $product_name = $item['name'];
        $quantity = $item['quantity'];
        $unit_price = $item['price'];
        $subtotal = $quantity * $unit_price;

        $stmt_item->bind_param("iisidd", $order_id, $product_id, $product_name, $quantity, $unit_price, $subtotal);

        if (!$stmt_item->execute()) {
            throw new Exception("訂單項目執行錯誤: " . $stmt_item->error);
        }

        // 更新商品庫存和銷量
        $sql_update_product = "UPDATE `products` SET `availability` = `availability` - ?, `Psales` = `Psales` + ? WHERE `id` = ? AND `availability` >= ?";
        $stmt_update = $db_link->prepare($sql_update_product);
        if (!$stmt_update) {
            throw new Exception("商品更新準備語句錯誤: " . $db_link->error);
        }

        $stmt_update->bind_param("iiii", $quantity, $quantity, $product_id, $quantity);

        if (!$stmt_update->execute()) {
            throw new Exception("商品更新執行錯誤: " . $stmt_update->error);
        }

        if ($stmt_update->affected_rows === 0) {
            throw new Exception("商品ID {$product_id} 的庫存不足。");
        }

        $stmt_update->close();
    }
    $stmt_item->close();

    // 設置購買成功的 Session 變數
    $_SESSION['purchase_success'] = true;

    // 提交事務
    $db_link->commit();

    // 清空購物車
    unset($_SESSION['cart']);

    // 導向訂單確認頁面
    header("Location: order_confirmation.php?order_id=" . $order_id);
    exit();

} catch (Exception $e) {
    // 回滾事務
    $db_link->rollback();
    echo "<script>alert('訂單處理失敗：" . addslashes($e->getMessage()) . "');history.back();</script>";
    exit();
}
?>
