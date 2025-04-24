<?php
session_start();
include "config.php";

// 檢查是否已登入
if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; // 保持 user_id 為字串

// 在這裡添加調試信息
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<!-- Debug: user_id in session is " . htmlspecialchars($user_id) . " -->";

// 獲取使用者的所有訂單
$sql_orders = "SELECT `order_id`, `order_date`, `total_amount`, `status` 
              FROM `orders` 
              WHERE `user_id` = ? 
              ORDER BY `order_date` DESC";
$stmt_orders = $db_link->prepare($sql_orders);
if (!$stmt_orders) {
    die("訂單查詢準備語句錯誤: " . $db_link->error);
}

$stmt_orders->bind_param("s", $user_id); // 使用 "s" 代表字串類型
$stmt_orders->execute();
$result_orders = $stmt_orders->get_result();
$orders = $result_orders->fetch_all(MYSQLI_ASSOC);
$stmt_orders->close();
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>查看訂單</title>
    <style>
        /* 基本樣式，根據您的需求進行調整 */
        body {
            font-family: 'Microsoft JhengHei', sans-serif;
            background-color: #f9f9f9;
            padding: 20px;
        }
        .orders-container {
            max-width: 1000px;
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
        .status-pending { color: orange; }
        .status-shipped { color: blue; }
        .status-delivered { color: green; }
        .status-cancelled { color: red; }
        .button-container {
            display: flex;
            justify-content: center;
            gap: 20px; /* 調整按鈕之間的間距 */
        }
        .back-button {
            padding: 12px 20px;
            background-color: #1976D2;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s;
            text-decoration: none; /* 移除連結下劃線 */
            text-align: center;
            display: inline-block; /* 讓連結像按鈕一樣 */
        }
        .back-button:hover {
            background-color: #0D47A1;
        }
    </style>
</head>
<body>
    <div class="orders-container">
        <h2>我的訂單</h2>

        <?php if (!empty($orders)): ?>
            <table>
                <thead>
                    <tr>
                        <th>訂單編號</th>
                        <th>訂單日期</th>
                        <th>總金額</th>
                        <th>狀態</th>
                        <th>查看詳情</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <?php
                            // 映射訂單狀態為中文並設置樣式
                            $status_display = '';
                            $status_class = '';
                            switch ($order['status']) {
                                case 'Pending':
                                    $status_display = '待出貨';
                                    $status_class = 'status-pending';
                                    break;
                                case 'Shipped':
                                    $status_display = '已出貨';
                                    $status_class = 'status-shipped';
                                    break;
                                case 'Delivered':
                                    $status_display = '已送達';
                                    $status_class = 'status-delivered';
                                    break;
                                case 'Cancelled':
                                    $status_display = '已取消';
                                    $status_class = 'status-cancelled';
                                    break;
                                default:
                                    $status_display = htmlspecialchars($order['status']);
                                    $status_class = '';
                            }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($order['order_id']); ?></td>
                            <td><?= htmlspecialchars($order['order_date']); ?></td>
                            <td>$<?= number_format($order['total_amount'], 2); ?></td>
                            <td class="<?= $status_class; ?>"><?= $status_display; ?></td>
                            <td>
                                <a class="back-button" href="order_confirmation.php?order_id=<?= htmlspecialchars($order['order_id']); ?>">查看詳情</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>您目前沒有任何訂單。</p>
        <?php endif; ?>

        <div class="button-container">
            <a class="back-button" href="會員中心/user.php">返回會員中心</a>
            <a class="back-button" href="index.php">返回首頁</a>
        </div>
    </div>
</body>
</html>
