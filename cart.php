<?php
session_start();

// ====================== 1) 接收操作 (更新或刪除) ===========================
// (A) 若用 GET ?do=delete&id=XXX 或 POST "do=delete" 方式刪除商品
if (isset($_GET['do']) && $_GET['do'] === 'delete') {
    $del_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    // 移除對應商品
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] == $del_id) {
                unset($_SESSION['cart'][$key]);
                // 重建索引
                $_SESSION['cart'] = array_values($_SESSION['cart']);
                break;
            }
        }
    }

    // 移除後導回 cart.php，避免重複刪除
    header("Location: cart.php");
    exit;
}

// (B) 若用 POST "do=update" 方式更新某商品數量
if (isset($_POST['do']) && $_POST['do'] === 'update') {
    $upd_id  = isset($_POST['id'])       ? (int)$_POST['id']       : 0;
    $upd_qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    if ($upd_qty < 1) {
        $upd_qty = 1; // 數量不可小於1
    }

    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] == $upd_id) {
                $_SESSION['cart'][$key]['quantity'] = $upd_qty;
                break;
            }
        }
    }

    // 更新後回到 cart.php
    header("Location: cart.php");
    exit;
}

// ====================== 2) 讀取購物車內容 + 計算總金額 ======================
$total_price = 0;
$cart_items  = [];

if (!empty($_SESSION['cart'])) {
    $cart_items = $_SESSION['cart'];

    foreach ($cart_items as $ci) {
        $item_subtotal = $ci['price'] * $ci['quantity'];
        $total_price  += $item_subtotal;
    }
}
?>
<!doctype html>
<html class="no-js" lang="zxx">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>購物車</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

   <!-- CSS here -->
   <link rel="stylesheet" href="static2/css/preloader.css">
   <link rel="stylesheet" href="static2/css/owl.carousel.min.css">
   <link rel="stylesheet" href="static2/css/animate.min.css">
   <link rel="stylesheet" href="static2/css/magnific-popup.css">
   <link rel="stylesheet" href="static2/css/meanmenu.css">
   <link rel="stylesheet" href="static2/css/animate.min.css">
   <link rel="stylesheet" href="static2/css/slick.css">
   <link rel="stylesheet" href="static2/css/bootstrap.min.css">
   <link rel="stylesheet" href="static2/css/fontawesome-all.min.css">
   <link rel="stylesheet" href="static2/css/themify-icons.css">
   <link rel="stylesheet" href="static2/css/nice-select.css">
   <link rel="stylesheet" href="static2/css/ui-range-slider.css">
   <link rel="stylesheet" href="static2/css/main.css">

   <style>
    .remove-icon {
    width: 18px;
    height: 18px;
    fill: #666;        /* 圖示顏色 */
    transition: 0.3s;
    }
    .remove-icon:hover {
    fill: #d9534f;     /* 滑鼠移上去時變色 (Bootstrap danger 色) */
    transform: scale(1.1);
    }
   </style>
</head>

<body>

    <!-- TODO: 您可自行保留或刪除「preloader / header / sidebar / footer」等區塊 -->
    <!-- preloader start -->
    <div id="loading">
        <div id="loading-center">
            <div id="loading-center-absolute">
                <!-- ...preloader圖示... -->
            </div>
        </div>
    </div>
    <!-- preloader end -->

    <!-- header area start -->
    <header>
        <!-- 您的 header 區塊，可保留或刪除 -->
    </header>
    <!-- header area end -->


<!-- breadcrumb area start -->
<div class="breadcrumb-area pt-10 pb-80 mb-10" data-background="assets/img/banner/breadcrumb.jpg">
    <div class="container">
        <div class="breadcrumb-title text-center">
            <h2>您的購物車</h2>
        </div>
        <div class="breadcrumb-list" style="font-size: 20px;">
            <a href="index.php">首頁</a>
            <span>購物車</span>
        </div>
    </div>
</div>
<!-- breadcrumb area end -->


<!-- Cart Area Start -->
<section class="cart-area pt-40 pb-100">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <!-- cart form (不再需要包裹表單) -->
                <div class="table-content table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="product-thumbnail">商品</th>
                                <th class="cart-product-name">商品名稱</th>
                                <th class="product-price">單價</th>
                                <th class="product-quantity">數量</th>
                                <th class="product-subtotal">小計</th>
                                <th class="product-remove">移除</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($cart_items)): ?>
                                <?php foreach ($cart_items as $ci): ?>
                                    <?php 
                                        $subtotal = $ci['price'] * $ci['quantity'];
                                    ?>
                                    <tr>
                                        <td class="product-thumbnail">
                                            <!-- 商品圖 -->
                                            <?php if(!empty($ci['image'])): ?>
                                                <a href="product.php">
                                                    <img src="data:image/jpeg;base64,<?= base64_encode($ci['image']); ?>" 
                                                         alt="cart-img" style="max-width:80px;">
                                                </a>
                                            <?php else: ?>
                                                <a href="product.php">
                                                    <img src="static2/picture/no-image.jpg" 
                                                         alt="cart-img" style="max-width:80px;">
                                                </a>
                                            <?php endif; ?>
                                        </td>

                                        <td class="product-name">
                                            <a href="product.php">
                                                <?= htmlspecialchars($ci['name']); ?>
                                            </a>
                                        </td>

                                        <td class="product-price">
                                            <span class="amount">$<?= number_format($ci['price'], 2); ?></span>
                                        </td>

                                        <td class="product-quantity">
                                            <!-- ★★ 不使用 form ★★ -->
                                            
                                                <input 
                                                    type="number" 
                                                    value="<?= $ci['quantity']; ?>" 
                                                    min="1"
                                                    style="width:60px; text-align:center;"
                                                    data-id="<?= $ci['id']; ?>"        
                                                    data-price="<?= $ci['price']; ?>"  
                                                    class="cart-item-qty"
                                                >
                                            
                                        </td>

                                        <td class="product-subtotal">
                                            <span class="amount item-subtotal">
                                                $<?= number_format($subtotal, 2); ?>
                                            </span>
                                        </td>

                                        <td class="product-remove">
                                            <!-- 刪除按鈕 (改用 SVG 垃圾桶圖示) -->
                                            <a href="cart.php?do=delete&id=<?= $ci['id']; ?>" class="remove-item" data-id="<?= $ci['id']; ?>" 
                                            style="display:inline-flex; align-items:center; gap:4px;">
                                                <!-- 這裡插入 SVG -->
                                                <svg viewBox="0 0 448 512" 
                                                    style="width:16px; height:16px; fill:#444; transition:0.3s;"
                                                    class="remove-icon">
                                                <path d="M135.2 17.4C138.8 7 148.2 0 158.3 0h99.4c10.1 0 19.5 7 23.1 17.4l10.6 30.6H432c8.8 
                                                0 16 7.2 16 16v16c0 8.8-7.2 16-16 16h-16.9l-21.2 339.3c-1.3 20.1-18.1 35.7-38.3 35.7H93.5c-20.2 
                                                0-37-15.6-38.3-35.7L34 80H16c-8.8 0-16-7.2-16-16V48c0-8.8 7.2-16 16-16h141.5l10.6-30.6zM177.2 
                                                80l-10.6 30.6H281.4L270.8 80H177.2zM112 172c-6.6 0-12 5.4-12 12v260c0 6.6 
                                                5.4 12 12 12s12-5.4 12-12V184c0-6.6-5.4-12-12-12zm112 12v260c0 6.6 5.4 12 
                                                12 12s12-5.4 12-12V184c0-6.6-5.4-12-12-12s-12 5.4-12 12z"/>
                                                </svg>
                                                <!-- 文字(可留可刪) -->
                                                <span style="font-size: 0.875rem; color:#444;">移除</span>
                                            </a>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align:center;">
                                        購物車沒有商品
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 總金額顯示 -->
                <div class="row justify-content-end">
                    <div class="col-md-5 ml-auto">
                        <div class="cart-page-total">
                            <h2>購買總額</h2>
                            <ul class="mb-20">
                                <li>小計 
                                    <span class="cart-total-amount">
                                        $<?= number_format($total_price, 2); ?>
                                    </span>
                                </li>
                                <li>總額 
                                    <span class="cart-total-amount">
                                        $<?= number_format($total_price, 2); ?>
                                    </span>
                                </li>
                            </ul>
                            <a class="s-btn s-btn-2" href="checkout.php">
                                進行結帳
                            </a>
                        </div>
                    </div>
                </div>
                <!-- 總金額顯示結束 -->
            </div>
        </div>
    </div>
</section>
<!-- Cart Area End -->


<script>
document.addEventListener("DOMContentLoaded", function() {

    // 1) 監聽每個數量輸入框的 input/change 事件
    const qtyInputs = document.querySelectorAll(".cart-item-qty");
    qtyInputs.forEach(function(input) {
        input.addEventListener("input", function() {
            // 取得商品ID, newQty, 單價
            const newQty   = parseInt(this.value) || 1;
            const itemId   = parseInt(this.dataset.id);
            const unitPrice= parseFloat(this.dataset.price);

            // (A) 先更新前端(小計)
            const itemSubtotalElem = this.closest("tr").querySelector(".item-subtotal");
            const newSubtotal = newQty * unitPrice;
            itemSubtotalElem.textContent = "$" + newSubtotal.toFixed(2);

            // 更新整個購物車總金額
            recalcCartTotal();

            // (B) AJAX -> 同步更新 $_SESSION['cart']
            fetch("ajax_update_cart.php", {
                method : "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body   : new URLSearchParams({
                    action: "update_qty",
                    id    : itemId,
                    qty   : newQty
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === "ok") {
                    console.log("Quantity updated in session.");
                } else {
                    console.error("Update error:", data);
                }
            })
            .catch(err => console.error("Fetch error:", err));
        });
    });

    // 2) 刪除商品 (和原先的 GET ?do=delete 不同，如果要用 AJAX 可直接改成 fetch)
    const removeLinks = document.querySelectorAll(".remove-item");
    removeLinks.forEach(function(link) {
        link.addEventListener("click", function(e) {
            // 如果仍要使用原本 GET 方式，可保留不阻止預設
            // e.preventDefault();

            // 若要改 AJAX: 
            //  e.preventDefault();
            //  let itemId = parseInt(this.dataset.id);
            //  (A) 前端移除該列 + recalcCartTotal()
            //  (B) fetch -> ajax_update_cart.php { action:'remove_item', id:itemId }

            // 如果直接用 GET 的話, 就是目前原先功能
        });
    });

    // 3) 計算總金額 (前端) 的函式
    function recalcCartTotal() {
        let total = 0;
        document.querySelectorAll(".cart-item-qty").forEach(function(input) {
            const qty = parseInt(input.value) || 1;
            const price = parseFloat(input.dataset.price);
            total += (qty * price);
        });
        document.querySelectorAll(".cart-total-amount").forEach(function(elem) {
            elem.textContent = "$" + total.toFixed(2);
        });
    }
});
</script>





    <!-- 其他區塊 (Product modal / subscribe / footer) 略 ... -->

    <!-- JS here -->
    <script src="static2/js/jquery.min.js"></script>
    <script src="static2/js/waypoints.min.js"></script>
    <script src="static2/js/bootstrap.bundle.min.js"></script>
    <script src="static2/js/tweenmax.js"></script>
    <script src="static2/js/owl.carousel.min.js"></script>
    <script src="static2/js/slick.min.js"></script>
    <script src="static2/js/jquery-ui-slider-range.js"></script>
    <script src="static2/js/jquery.meanmenu.min.js"></script>
    <script src="static2/js/isotope.pkgd.min.js"></script>
    <script src="static2/js/wow.min.js"></script>
    <script src="static2/js/jquery.scrollUp.min.js"></script>
    <script src="static2/js/countdown.min.js"></script>
    <script src="static2/js/jquery.magnific-popup.min.js"></script>
    <script src="static2/js/parallex.js"></script>
    <script src="static2/js/imagesloaded.pkgd.min.js"></script>
    <script src="static2/js/jquery.nice-select.min.js"></script>
    <script src="static2/js/main.js"></script>

</body>
</html>
