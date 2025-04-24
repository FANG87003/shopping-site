<?php
session_start();

/**
 * 本檔案負責 AJAX 更新 $_SESSION['cart']
 * action 可為:
 *   - 'update_qty' => 更新數量
 *   - 'remove_item' => 移除商品
 */

// 檢查 AJAX action
if (!isset($_POST['action'])) {
    // 回傳錯誤
    echo json_encode([
      'status'  => 'error',
      'message' => 'No action provided'
    ]);
    exit;
}

$action = $_POST['action'];

// 如果目前沒 cart，就先確保它是陣列
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 1) 更新數量
if ($action === 'update_qty') {
    $upd_id  = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $upd_qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;
    if ($upd_qty < 1) $upd_qty = 1; // 數量不可少於1

    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['id'] == $upd_id) {
            $_SESSION['cart'][$key]['quantity'] = $upd_qty;
            break;
        }
    }

    echo json_encode([
      'status'  => 'ok',
      'message' => 'Quantity updated'
    ]);
    exit;
}

// 2) 刪除商品
if ($action === 'remove_item') {
    $del_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['id'] == $del_id) {
            unset($_SESSION['cart'][$key]);
            // 重建索引
            $_SESSION['cart'] = array_values($_SESSION['cart']);
            break;
        }
    }

    echo json_encode([
      'status'  => 'ok',
      'message' => 'Item removed'
    ]);
    exit;
}

// 若都沒對應的 action
echo json_encode([
  'status'  => 'error',
  'message' => 'Unknown action'
]);
exit;
