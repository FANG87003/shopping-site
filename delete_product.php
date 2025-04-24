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
    $sql_check = "SELECT `id` FROM `products` WHERE `id` = ?";
    $stmt_check = $db_link->prepare($sql_check);
    if (!$stmt_check) {
        die("商品查詢準備語句錯誤: " . $db_link->error);
    }
    $stmt_check->bind_param("i", $product_id);
} else {
    // 普通使用者：檢查商品是否屬於自己
    $sql_check = "SELECT `id` FROM `products` WHERE `id` = ? AND `owner` = ?";
    $stmt_check = $db_link->prepare($sql_check);
    if (!$stmt_check) {
        die("商品查詢準備語句錯誤: " . $db_link->error);
    }
    $stmt_check->bind_param("is", $product_id, $user_id);
}

$stmt_check->execute();
$result_check = $stmt_check->get_result();
if ($result_check->num_rows === 0) {
    $stmt_check->close();
    die("找不到該商品或您無權刪除此商品。");
}
$stmt_check->close();

// 刪除商品及其相關資料
$db_link->begin_transaction();

try {
    // 刪除產品圖片
    $sql_delete_images = "DELETE FROM `product_images` WHERE `product_id` = ?";
    $stmt_delete_images = $db_link->prepare($sql_delete_images);
    if (!$stmt_delete_images) {
        throw new Exception("產品圖片刪除準備語句錯誤: " . $db_link->error);
    }
    $stmt_delete_images->bind_param("i", $product_id);
    if (!$stmt_delete_images->execute()) {
        throw new Exception("產品圖片刪除執行錯誤: " . $stmt_delete_images->error);
    }
    $stmt_delete_images->close();

    // 刪除產品
    $sql_delete_product = "DELETE FROM `products` WHERE `id` = ?";
    $stmt_delete_product = $db_link->prepare($sql_delete_product);
    if (!$stmt_delete_product) {
        throw new Exception("產品刪除準備語句錯誤: " . $db_link->error);
    }
    $stmt_delete_product->bind_param("i", $product_id);
    if (!$stmt_delete_product->execute()) {
        throw new Exception("產品刪除執行錯誤: " . $stmt_delete_product->error);
    }
    $stmt_delete_product->close();

    // 提交事務
    $db_link->commit();

    // 根據使用者權限重定向
    if ($user_access == 1) {
        // 管理員重定向到管理中心
        header("Location: admin_mana.php?message=deleted");
    } else {
        // 普通使用者重定向到我的賣場
        header("Location: my_store.php?message=deleted");
    }
    exit();
} catch (Exception $e) {
    // 回滾事務
    $db_link->rollback();
    die("刪除商品失敗: " . $e->getMessage());
}
?>
