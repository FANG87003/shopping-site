<?php
session_start();
include "config.php";

// 檢查用戶是否已登入
if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
    echo "<script>alert('請先登入才能提交評論！');window.location.href='login.php';</script>";
    exit;
}

// 檢查是否通過 POST 提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 獲取並驗證輸入數據
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $rating     = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $review     = isset($_POST['review']) ? trim($_POST['review']) : '';

    // 基本驗證
    if ($product_id <= 0 || $rating < 1 || $rating > 5 || empty($review)) {
        echo "<script>alert('請填寫所有必填欄位並提供有效的評分！');window.history.back();</script>";
        exit;
    }

    // 獲取用戶 ID（對應 `user` 資料表的 `Uid`）
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    if (!$user_id) {
        echo "<script>alert('用戶資訊錯誤，請重新登入！');window.location.href='login.php';</script>";
        exit;
    }

    // 連接資料庫
    $link = mysqli_connect('localhost','root','','shopwebdb');
    if (!$link) {
        die("資料庫連線失敗: " . mysqli_connect_error());
    }

    // 撈取最近一次的評論時間（可選，用於限制評論頻率）
    $sqlLastReview = "SELECT `created_at` FROM `reviews` 
                      WHERE `user_id` = ? AND `product_id` = ?
                      ORDER BY `created_at` DESC LIMIT 1";
    $stmtLastReview = mysqli_prepare($link, $sqlLastReview);
    mysqli_stmt_bind_param($stmtLastReview, "si", $user_id, $product_id);
    mysqli_stmt_execute($stmtLastReview);
    mysqli_stmt_bind_result($stmtLastReview, $last_review_time);
    mysqli_stmt_fetch($stmtLastReview);
    mysqli_stmt_close($stmtLastReview);

    if ($last_review_time) {
        $time_diff = strtotime("now") - strtotime($last_review_time);
        if ($time_diff < 3600) { // 例如，限制每小時只能提交一次
            echo "<script>alert('您提交評論的頻率過高，請稍後再試！');window.history.back();</script>";
            exit;
        }
    }

    // 插入評論到 reviews 資料表
    $sqlInsert = "INSERT INTO `reviews` (`product_id`, `rating`, `review`, `user_id`, `created_at`) 
                  VALUES (?, ?, ?, ?, NOW())";
    $stmtInsert = mysqli_prepare($link, $sqlInsert);
    if ($stmtInsert) {
        mysqli_stmt_bind_param($stmtInsert, "iiss", $product_id, $rating, $review, $user_id);
        $exec = mysqli_stmt_execute($stmtInsert);
        if ($exec) {
            echo "<script>alert('評論提交成功！');window.location.href='product.php?id={$product_id}';</script>";
        } else {
            echo "<script>alert('評論提交失敗，請稍後再試！');window.history.back();</script>";
        }
        mysqli_stmt_close($stmtInsert);
    } else {
        echo "<script>alert('資料庫錯誤，請稍後再試！');window.history.back();</script>";
    }

    mysqli_close($link);
} else {
    // 非法訪問
    echo "<script>alert('非法訪問！');window.location.href='index.php';</script>";
    exit;
}
?>
