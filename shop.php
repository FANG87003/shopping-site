<?php
session_start();
include "config.php";
$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}

// 若使用者點了「加入購物車」表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity   = isset($_POST['quantity'])   ? (int)$_POST['quantity']   : 1;

    // 從資料庫抓取商品
    $sql = "SELECT p.id, p.name, p.price_new, i.image1
            FROM products p
            LEFT JOIN product_images i ON p.id = i.product_id
            WHERE p.id = ?";
    $stmt = $db_link->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if ($product) {
        // 建立要加入購物車的陣列
        $cart_item = [
            'id'       => $product['id'],
            'name'     => $product['name'],
            'price'    => $product['price_new'],
            'quantity' => $quantity,
            'image'    => $product['image1']  // BLOB
        ];

        // 如果 Session 裡已有 cart
        if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            // 檢查是否已存在該商品
            $found = false;
            foreach ($_SESSION['cart'] as $key => $item) {
                if ($item['id'] == $cart_item['id']) {
                    // 數量累加
                    $_SESSION['cart'][$key]['quantity'] += $cart_item['quantity'];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $_SESSION['cart'][] = $cart_item;
            }
        } else {
            // 若 cart 尚不存在
            $_SESSION['cart'] = [$cart_item];
        }
        echo "<script>alert('成功加入購物車！');window.location.href='shop.php';</script>";
    } else {
        // 查無此商品
        echo "<script>alert('商品不存在！');window.location.href='shop.php';</script>";
    }
    exit;
}
?>
<!doctype html>
<html class="no-js" lang="zxx">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>方哥的商店</title>
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
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" />

   <script>
    (function(){
        // 若支援 PerformanceNavigation API
        if (performance && performance.navigation && performance.navigation.type === performance.navigation.TYPE_RELOAD) {
            // 使用者手動刷新了本頁 (F5, Ctrl+R, or Browser Refresh btn)
            var url = new URL(window.location.href);

            // 刪除 price_min 與 price_max 參數
            url.searchParams.delete('price_min');
            url.searchParams.delete('price_max');

            // 同理，如需同時清除其他參數(如 cat_ids[], tag, short...)可視需求加上
            // url.searchParams.delete('cat_ids[]');
            // ...

            // 導向新的 URL (不含 price_min / price_max)
            window.location.replace(url.toString());
        }
    })();
    </script>

    <style>
        a.log-bar {
            background: linear-gradient(135deg, #1c1c1c, #4e4e4e); /* 炭黑到深灰 */

            color: #fff; /* 文字白色 */
            border: 2px solid #fff; /* 白色邊框 */
            border-radius: 50px; /* 圓角邊框 */
            padding: 6px 20px; /* 增加按鈕的填充，使按鈕更大 */
            font-family: 'Pathway Gothic One', sans-serif; /* 使用聖誕風格字體 */
            font-weight: 700; /* 加粗字體 */
            font-size: 12px; /* 增大字體 */
            text-transform: uppercase; /* 文字大寫 */
            text-align: center; /* 文字居中 */
            display: inline-block; /* 確保按鈕內容水平排列 */
            position: relative; /* 為雪花提供位置 */
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3); /* 更深的陰影，增強浮動效果 */
            transition: 0.3s all; /* 平滑過渡效果 */
            white-space: nowrap; /* 禁止文字換行 */
        }

        /* 滑鼠懸停效果 */
        a.log-bar:hover, a.log-bar:focus {
            background: linear-gradient(45deg, #4e4e4e, #1c1c1c); /* 深灰到炭黑 */

            color: #fff;
            border-color: #ffeb3b; /* 金色邊框 */
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5); /* 更深陰影，讓按鈕看起來浮起來 */
            transform: translateY(-3px); /* 添加浮動效果 */
            transition: 0.3s all ease-out;
        }
        .btn-area {
            margin-top: 30px;
            padding-top: 80px;
        }

        /*input placeholder 顯示為灰色 */
        .gray-placeholder::placeholder {
            color: #999; /* 可依需求調整灰色深淺 */
        }

        /* 讓 <button> 也能擁有同樣的樣式 */
        .cart-btn.cart-btn-1.p-abs button.product-modal-cart-btn {
            display: flex;           /* 用 flexbox */
            align-items: center;     /* 垂直置中 */
            justify-content: center; /* 水平置中（如果需要） */
            width: 100%;
            height: 44px;            /* 按鈕固定高度 */
            background: #222;
            color: #fff;
            text-transform: uppercase;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .cart-btn.cart-btn-1.p-abs button.product-modal-cart-btn:hover {
        background: #ff5252;   /* 與 .cart-btn.cart-btn-1.p-abs a:hover 相同 */
        color: #fff;
        }

    </style>
</head>

<body>

    <!-- preloader start -->
    <div id="loading">
        <div id="loading-center">
            <div id="loading-center-absolute">
                <svg viewbox="0 0 58 58" id="mustard" class="product">
                <g>
                  <path style="fill:#ED7161;" d="M39.869,58H18.131C16.954,58,16,57.046,16,55.869V12.621C16,11.726,16.726,11,17.621,11h22.757
                    C41.274,11,42,11.726,42,12.621v43.248C42,57.046,41.046,58,39.869,58z"></path>
                  <polygon style="fill:#D13834;" points="35,11 23,11 27.615,0 30.385,0 	"></polygon>
                  <rect x="16" y="16" style="fill:#D75A4A;" width="26" height="2"></rect>
                  <rect x="20" y="11" style="fill:#D75A4A;" width="2" height="6"></rect>
                  <rect x="25" y="11" style="fill:#D75A4A;" width="2" height="6"></rect>
                  <rect x="30" y="11" style="fill:#D75A4A;" width="2" height="6"></rect>
                  <rect x="36" y="11" style="fill:#D75A4A;" width="2" height="6"></rect>
                  <circle style="fill:#D13834;" cx="29" cy="36" r="10"></circle>
                </g>
                </svg>
                <svg viewbox="0 0 49.818 49.818" id="meat" class="product">
                    <g>
                    <path style="fill:#994530;" d="M0.953,38.891c0,0,3.184,6.921,11.405,9.64c1.827,0.604,3.751,0.751,5.667,0.922
                        c7.866,0.703,26.714-0.971,31.066-18.976c1.367-5.656,0.76-11.612-1.429-17.003C44.51,5.711,37.447-4.233,22.831,2.427
                        c-8.328,3.795-7.696,10.279-5.913,14.787c2.157,5.456-2.243,11.081-8.06,10.316C1.669,26.584-1.825,30.904,0.953,38.891z"></path>
                    <g>
                        <path style="fill:#D75A4A;" d="M4.69,37.18c0.402,0.785,3.058,5.552,9.111,7.554c1.335,0.441,2.863,0.577,4.482,0.72l0.282,0.025
                            c0.818,0.073,1.698,0.11,2.617,0.11c18.18,0,22.854-11.218,24.02-16.041c1.134-4.693,0.706-9.703-1.235-14.488
                            C41.049,7.874,36.856,4.229,31.506,4.229c-2.21,0-4.683,0.615-7.349,1.83c-2.992,1.364-6.676,3.921-4.13,10.36
                            c1.284,3.25,0.912,6.746-1.023,9.591c-2.17,3.191-6.002,4.901-9.895,4.39c-0.493-0.065-0.966-0.099-1.404-0.099
                            c-1.077,0-2.502,0.198-3.173,1.143C3.765,32.524,3.823,34.609,4.69,37.18z"></path>
                        <path style="fill:#C64940;" d="M21.184,46.589c-0.948,0-1.858-0.038-2.706-0.114l-0.283-0.025
                            c-1.674-0.147-3.257-0.287-4.706-0.767c-6.376-2.108-9.188-7.073-9.688-8.047l-0.058-0.137c-0.984-2.917-0.993-5.273-0.026-6.635
                            c0.912-1.285,2.89-1.807,5.524-1.456c3.537,0.466,6.959-1.054,8.936-3.961c1.746-2.565,2.082-5.723,0.921-8.661
                            c-3.189-8.065,2.707-10.754,4.645-11.638c9.68-4.407,16.81-1.155,21.152,9.535c2.021,4.981,2.464,10.202,1.28,15.099
                            C44.953,34.836,40.073,46.589,21.184,46.589z M5.613,36.787c0.401,0.758,2.936,5.155,8.503,6.997
                            c1.229,0.406,2.699,0.536,4.256,0.673l0.284,0.025c0.788,0.07,1.639,0.106,2.527,0.106c17.469,0,21.938-10.683,23.048-15.276
                            c1.084-4.487,0.672-9.286-1.19-13.877C40.29,8.663,36.409,5.229,31.506,5.229c-2.067,0-4.4,0.585-6.934,1.74
                            c-3.02,1.376-5.81,3.532-3.615,9.083c1.408,3.563,0.998,7.398-1.126,10.521c-2.404,3.534-6.563,5.386-10.852,4.818
                            c-1.793-0.236-3.197,0.019-3.632,0.632C4.912,32.636,4.756,34.207,5.613,36.787z"></path>
                    </g>
                    <g>
                        <circle style="fill:#E6E6E6;" cx="32.455" cy="12.779" r="4"></circle>
                        <path style="fill:#7A3726;" d="M32.455,17.779c-2.757,0-5-2.243-5-5s2.243-5,5-5s5,2.243,5,5S35.212,17.779,32.455,17.779z
                            M32.455,9.779c-1.654,0-3,1.346-3,3s1.346,3,3,3s3-1.346,3-3S34.109,9.779,32.455,9.779z"></path>
                    </g>
                    <path style="fill:#C64940;" d="M25.617,45.684l-1.941-0.479c0.435-1.761-1.063-3.216-3.446-4.859
                        c-2.875-1.984-4.817-5.117-5.327-8.595c-0.186-1.266-0.425-2.285-0.428-2.295l1.922-0.548c0.01,0.028,1.09,3.104,3.978,4.314
                        c2.094,0.877,4.667,0.598,7.648-0.832c11.578-5.554,17.102-2.646,17.332-2.52l-0.967,1.752c-0.04-0.021-4.97-2.48-15.5,2.57
                        c-3.53,1.694-6.662,1.984-9.312,0.863c-0.801-0.339-1.49-0.779-2.078-1.265c0.769,1.974,2.11,3.695,3.867,4.907
                        C23.149,39.931,26.472,42.222,25.617,45.684z"></path>
                    <path style="fill:#C64940;" d="M27.074,27.586c-5.37,0-7.605-3.694-7.633-3.74l1.727-1.01l-0.863,0.505l0.859-0.511
                        c0.108,0.179,2.714,4.335,9.738,2.105c1.54-0.794,12.038-6.002,15.619-2.289l-1.439,1.389c-1.979-2.052-9.229,0.576-13.332,2.714
                        l-0.154,0.064C29.892,27.364,28.389,27.586,27.074,27.586z"></path>
                    </g>
                </svg>
                <svg viewbox="0 0 49 49" id="soda" class="product">
                    <g>
                    <path style="fill:#E22F37;" d="M9.5,27V5.918c0-1.362,0.829-2.587,2.094-3.093l0,0C12.642,2.406,13.5,1.14,13.5,0.011L13.5,0v0
                        l11,0l11,0v0v0.011c0,1.129,0.858,2.395,1.906,2.814l0,0c1.265,0.506,2.094,1.73,2.094,3.093V27v-5v21.082
                        c0,1.362-0.829,2.587-2.094,3.093h0c-1.048,0.419-1.906,1.686-1.906,2.814V49l0,0h-11h-11l0,0l0-0.011
                        c0-1.129-0.858-2.395-1.906-2.814h0c-1.265-0.506-2.094-1.73-2.094-3.093V22"></path>
                    <path style="fill:#F75B57;" d="M18.5,7h-5c-0.553,0-1-0.447-1-1s0.447-1,1-1h5c0.553,0,1,0.447,1,1S19.053,7,18.5,7z"></path>
                    <path style="fill:#F75B57;" d="M35.5,7h-13c-0.553,0-1-0.447-1-1s0.447-1,1-1h13c0.553,0,1,0.447,1,1S36.053,7,35.5,7z"></path>
                    <path style="fill:#994530;" d="M18.5,45h-5c-0.553,0-1-0.447-1-1s0.447-1,1-1h5c0.553,0,1,0.447,1,1S19.053,45,18.5,45z"></path>
                    <path style="fill:#994530;" d="M35.5,45h-13c-0.553,0-1-0.447-1-1s0.447-1,1-1h13c0.553,0,1,0.447,1,1S36.053,45,35.5,45z"></path>
                    <polygon style="fill:#E6E6E6;" points="39.5,32 9.5,42 9.5,20 39.5,10 	"></polygon>
                    <polygon style="fill:#F9D70B;" points="39.5,28 9.5,38 9.5,24 39.5,14 	"></polygon>
                    </g>
                </svg>
                <div class="cart-container">
                    <svg viewbox="0 0 512 512" id="cart">
                    <circle cx="376.8" cy="440" r="55"></circle>
                    <circle cx="192" cy="440" r="55"></circle>
                    <polygon points="128,0 0.8,0 0.8,32 104.8,32 136.8,124.8 170.4,124.8 "></polygon>
                    <polygon style="fill:#ED7161;" points="250.4,49.6 224,124.8 411.2,124.8 "></polygon>
                    <polygon style="fill:#ee5a46;" points="411.2,124.8 224,124.8 170.4,124.8 136.8,124.8 68,124.8 141.6,361.6 427.2,361.6 
                    511.2,124.8 "></polygon>
                    <g>
                        <rect x="166.4" y="185.6" style="fill:#FFFFFF;" width="255.2" height="16"></rect>
                        <rect x="166.4" y="237.6" style="fill:#FFFFFF;" width="166.4" height="16"></rect>
                    </g>
                    </svg>
                </div>
            </div>
        </div>
    </div>
    <!-- preloader end -->


<!-- header area start -->
<header>
        <div id="header-sticky" class="header-area header-transparent header-sticky">
            <div class="header-main header-main-1 header-padding pl-50 pr-50">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <div class="col-xxl-9 col-xl-9 col-lg-9 col-md-6 col-sm-4 col-4">
                            <div class="header-left p-rel d-flex align-items-center">
                                <div class="logo pr-55 d-inline-block">
                                    <a href="index.php"><img src="static/picture/NCUT1.png" alt="#"></a>
                                </div>
                                <div class="main-menu">
                                <nav id="mobile-menu">
                                        <ul>
                                            <li><a href="">首頁</a></li>
                                            <li><a href="shop.php">商品類別</a>
                                                <ul class="sub-menu">
                                                    <li><a href="shop.php?cat_ids[]=女裝">女裝</a></li>
                                                    <li><a href="shop.php?cat_ids[]=配件">配件</a></li>
                                                    <li><a href="shop.php?cat_ids[]=書籍">書籍</a></li>
                                                    <li><a href="shop.php?cat_ids[]=家電">家電</a></li>
                                                    <li><a href="shop.php?cat_ids[]=家具">家具</a></li>
                                                    <li><a href="shop.php?cat_ids[]=男裝">男裝</a></li>
                                                    <li><a href="shop.php?cat_ids[]=3C">3C</a></li>
                                                    <li><a href="shop.php?cat_ids[]=鞋子">鞋子</a></li>
                                                </ul>
                                            </li>

                                            <?php
                                                // 檢查是否為管理員
                                                if (isset($_SESSION['user_access']) && $_SESSION['user_access'] == 1) {
                                                    // 顯示「管理中心」連結
                                                    echo '<li><a href="admin_mana.php">管理中心</a></li>';
                                                } else {
                                                    // 顯示「我的賣場」連結
                                                    echo '<li><a href="my_store.php">我的賣場</a></li>';
                                                }
                                            ?>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-3 col-xl-3 col-lg-3 col-md-6 col-sm-8 col-8">
                            <div class="header-right-wrapper d-flex align-items-center justify-content-end">
                                <div class="header-right d-flex align-items-center justify-content-end" style="gap: 10px;">
                                    <?php 
                                        if (isset($_SESSION['logged']) && $_SESSION['logged'] === true) 
                                        {
                                            $userName = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name'], ENT_QUOTES) : '訪客';
                                            // 在此多用一個 <span> 或 <div> 包使用者名稱並加上簡單樣式
                                            echo '
                                            <span 
                                                style="
                                                    display: inline-block;
                                                    color: #fff; 
                                                    font-size: 14px; 
                                                    white-space: nowrap;
                                                "
                                            >
                                                ' . $userName . '，歡迎
                                            </span>
                                            <a class="btn btn-default log-bar" href="會員中心/user.php" role="button">會員中心</a>
                                            <a class="btn btn-default log-bar" href="logout.php" role="button">登出</a>
                                            ';
                                        } 
                                        else 
                                        {
                                            echo '
                                            <a class="btn btn-default log-bar" href="login.php" role="button">登入</a>
                                            <a class="btn btn-default log-bar" href="register.php" role="button">註冊</a>
                                            ';
                                        }
                                    ?>
                                    <a href="javascript:void(0)" class="search-toggle d-none d-sm-inline-block"><i class="fal fa-search"></i></a>
                                    <div class="header-icon d-inline-block ml-30">
    <button type="button" data-bs-toggle="modal" data-bs-target="#cartMiniModal">
        <i class="fal fa-shopping-cart"></i>
        <span class="cart-item-count"><?php echo $cartCount; ?></span>
    </button>
</div>

                                </div>
                                <div class="header-bar ml-20 d-lg-none">
                                    <button type="button" class="header-bar-btn" data-bs-toggle="modal" data-bs-target="#offCanvasModal">
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <!-- header area end -->

<!-- cart mini area start -->
<div class="cartmini__area">
    <div class="modal fade" id="cartMiniModal" tabindex="-1" aria-labelledby="cartMiniModal" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="cartmini__wrapper">
                    <div class="cartmini__top d-flex align-items-center justify-content-between">
                        <h4>您的購物車</h4>
                        <div class="cartminit__close">
                            <button type="button" data-bs-dismiss="modal" aria-label="Close" class="cartmini__close-btn">
                                <i class="fal fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="cartmini__list">
                        <ul>
                            <?php
                                $total_price = 0;
                                if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                                    foreach ($_SESSION['cart'] as $item):
                                        // 計算此商品總價
                                        $item_total = $item['price'] * $item['quantity'];
                                        $total_price += $item_total;
                            ?>
                                <li class="cartmini__item p-rel d-flex align-items-start">
                                    <div class="cartmini__thumb mr-15">
                                        <a href="product-details.html">
                                            <img src="data:image/jpeg;base64,<?php echo base64_encode($item['image']); ?>" alt="">
                                        </a>
                                    </div>
                                    <div class="cartmini__content">
                                        <h3 class="cartmini__title">
                                            <a href="product-details.html"><?php echo htmlspecialchars($item['name']); ?></a>
                                        </h3>
                                        
                                        <!-- 數量 + 單價區塊 (純前端處理) -->
                                        <div>
                                            <input 
                                                type="number" 
                                                value="<?php echo $item['quantity']; ?>" 
                                                min="1" 
                                                style="width:60px; text-align:center; margin-bottom:5px;"
                                                data-price="<?php echo $item['price']; ?>"        
                                                data-item-id="<?php echo $item['id']; ?>"         
                                                class="cart-item-qty"                             
                                            />
                                            <span class="cartmini__price">
                                                <span class="price item-subtotal">
                                                    <?php echo $item['quantity']; ?> × $<?php echo number_format($item['price'], 2); ?>
                                                </span>
                                            </span>
                                            <br>
                                            <span class="cartmini__price">
                                                <span class="price item-total-price">
                                                    共：$<?php echo number_format($item_total, 2); ?>
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- 刪除該商品：把原本的 href 改為 '#'，並加上 data-item-id -->
                                    <a href="#" data-item-id="<?php echo $item['id']; ?>" class="cartmini__remove cart-delete-item">
                                        <i class="fal fa-times"></i>
                                    </a>
                                </li>
                            <?php
                                    endforeach;
                                } else {
                                    echo "<li>購物車中沒有商品。</li>";
                                }
                            ?>
                        </ul>
                    </div>
                    
                    <!-- 總金額 -->
                    <div class="cartmini__total d-flex align-items-center justify-content-between">
                        <h5>總額</h5>
                        <span class="cart-total-amount">$<?php echo number_format($total_price, 2); ?></span>
                    </div>

                    <!-- 按鈕區 -->
                    <div class="cartmini__bottom">
                        <a href="cart.php" class="s-btn w-100 mb-20">view cart</a>
                        <a href="checkout.php" class="s-btn s-btn-2 w-100">checkout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- cart mini area end -->


<script>
    document.addEventListener("DOMContentLoaded", function() {

        // 1) 數量輸入框：監聽 input 事件 => AJAX 更新
        const qtyInputs = document.querySelectorAll(".cart-item-qty");
        qtyInputs.forEach(function(input) {
            input.addEventListener("input", function() {
                const newQty    = parseInt(this.value) || 1; 
                const unitPrice = parseFloat(this.dataset.price);
                const itemId    = parseInt(this.dataset.itemId);

                // (A) 先更新前端顯示(小計/總金額)
                const itemSubtotalElem = this.closest(".cartmini__item").querySelector(".item-subtotal");
                itemSubtotalElem.textContent = newQty + " × $" + unitPrice.toFixed(2);

                const itemTotalElem = this.closest(".cartmini__item").querySelector(".item-total-price");
                const itemTotalPrice = newQty * unitPrice;
                itemTotalElem.textContent = "共：$" + itemTotalPrice.toFixed(2);

                // (A2) 更新整體總金額 & 右上角數量
                recalcCartTotal();

                // (B) AJAX -> 同步更新 $_SESSION['cart']
                fetch("ajax_update_cart.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: new URLSearchParams({
                        action : "update_qty",
                        id     : itemId,
                        qty    : newQty
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === "ok") {
                        console.log("Session cart updated (qty).");
                    } else {
                        console.error("Update qty error", data);
                    }
                })
                .catch(err => console.error(err));
            });
        });

        // 2) 刪除商品：前端移除 DOM + AJAX 同步 Session
        const deleteLinks = document.querySelectorAll(".cart-delete-item");
        deleteLinks.forEach(function(link) {
            link.addEventListener("click", function(e) {
                e.preventDefault(); // 阻止預設跳轉
                const cartItem = this.closest(".cartmini__item");
                const itemId   = parseInt(this.dataset.itemId);

                // (A) 先移除前端 DOM
                cartItem.remove();
                recalcCartTotal();

                // (B) AJAX -> 同步更新 $_SESSION['cart']
                fetch("ajax_update_cart.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: new URLSearchParams({
                        action : "remove_item",
                        id     : itemId
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === "ok") {
                        console.log("Session cart updated (remove).");
                    } else {
                        console.error("Remove item error", data);
                    }
                })
                .catch(err => console.error(err));
            });
        });

        // 3) 重新計算總金額 & 更新「右上角購物車數量」
        function recalcCartTotal() {
            let newCartTotal = 0;
            let newCartCount = 0; // 同時計算數量

            document.querySelectorAll(".cart-item-qty").forEach(function(input) {
                const qty   = parseInt(input.value) || 1;
                const price = parseFloat(input.dataset.price) || 0;
                newCartTotal += qty * price;
                newCartCount += qty; // 累加數量
            });

            // (A) 更新 mini cart 內的總金額
            document.querySelector(".cart-total-amount").textContent 
                = "$" + newCartTotal.toFixed(2);

            // (B) 更新 Header 頂部購物車按鈕的數量
            const cartCountElem = document.querySelector(".cart-item-count");
            if (cartCountElem) {
                cartCountElem.textContent = newCartCount;
            }
        }

    });
    </script>


    <!-- search area start -->
    <div class="search__area">
        <div class="search__close">
            <button type="button" class="search__close-btn search-close-btn"><i class="fal fa-times"></i></button>
        </div>
        <div class="search__wrapper">
            <h4>Searching</h4>
            <div class="search__form">
                <form action="#">
                    <div class="search__input">
                        <input type="text" placeholder="Search Products">
                        <button type="submit">
                            <i class="far fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- search area end -->

    <!-- sidebar area start -->
    <section class="offcanvas__area">
        <div class="modal fade" id="offCanvasModal" tabindex="-1" aria-labelledby="offCanvasModal" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="offcanvas__wrapper">
                        <div class="offcanvas__top d-flex align-items-center mb-60 justify-content-between">
                            <div class="logo">
                                <a href="index.html">
                                   <img src="static2/picture/logo-black.png" alt="logo">
                                </a>
                             </div>
                           <div class="offcanvas__close">
                              <button class="offcanvas__close-btn" data-bs-toggle="modal" data-bs-target="#offCanvasModal">
                                 <svg viewbox="0 0 22 22">
                                    <path d="M12.41,11l5.29-5.29c0.39-0.39,0.39-1.02,0-1.41s-1.02-0.39-1.41,0L11,9.59L5.71,4.29c-0.39-0.39-1.02-0.39-1.41,0
                                    s-0.39,1.02,0,1.41L9.59,11l-5.29,5.29c-0.39,0.39-0.39,1.02,0,1.41C4.49,17.9,4.74,18,5,18s0.51-0.1,0.71-0.29L11,12.41l5.29,5.29
                                    C16.49,17.9,16.74,18,17,18s0.51-0.1,0.71-0.29c0.39-0.39,0.39-1.02,0-1.41L12.41,11z"></path>
                                 </svg>
                              </button>
                           </div>
                        </div>
                        <div class="offcanvas__content p-relative z-index-1">
                           <div class="canvas__menu">
                              <div class="mobile-menu fix"></div>
                           </div>
                           <div class="offcanvas__action mb-15">
                              <a href="login.html">Login</a>
                           </div>
                           <div class="offcanvas__action mb-15 ">
                              <a href="wishlist.html" class="has-tag">
                                 <svg viewbox="0 0 22 22">
                                    <path d="M20.26,11.3c2.31-2.36,2.31-6.18-0.02-8.53C19.11,1.63,17.6,1,16,1c0,0,0,0,0,0c-1.57,0-3.05,0.61-4.18,1.71c0,0,0,0,0,0
                                    L11,3.41l-0.81-0.69c0,0,0,0,0,0C9.06,1.61,7.58,1,6,1C4.4,1,2.89,1.63,1.75,2.77c-2.33,2.35-2.33,6.17-0.02,8.53
                                    c0,0,0,0.01,0.01,0.01l0.01,0.01c0,0,0,0,0,0c0,0,0,0,0,0L11,20.94l9.25-9.62c0,0,0,0,0,0c0,0,0,0,0,0L20.26,11.3
                                    C20.26,11.31,20.26,11.3,20.26,11.3z M3.19,9.92C3.18,9.92,3.18,9.92,3.19,9.92C3.18,9.92,3.18,9.91,3.18,9.91
                                    c-1.57-1.58-1.57-4.15,0-5.73C3.93,3.42,4.93,3,6,3c1.07,0,2.07,0.42,2.83,1.18C8.84,4.19,8.85,4.2,8.86,4.21
                                    c0.01,0.01,0.01,0.02,0.03,0.03l1.46,1.25c0.07,0.06,0.14,0.09,0.22,0.13c0.01,0,0.01,0.01,0.02,0.01c0.13,0.06,0.27,0.1,0.41,0.1
                                    c0.08,0,0.16-0.03,0.25-0.05c0.03-0.01,0.07-0.01,0.1-0.02c0.07-0.03,0.13-0.07,0.2-0.11c0.03-0.02,0.07-0.03,0.1-0.06l1.46-1.24
                                    c0.01-0.01,0.02-0.02,0.03-0.03c0.01-0.01,0.03-0.01,0.04-0.02C13.93,3.42,14.93,3,16,3c0,0,0,0,0,0c1.07,0,2.07,0.42,2.83,1.18
                                    c1.56,1.58,1.56,4.15,0,5.73c0,0,0,0.01-0.01,0.01c0,0,0,0,0,0L11,18.06L3.19,9.92z"></path>
                                 </svg>
                                 <span class="tag">2</span>
                              </a>
                           </div>
                           <div class="offcanvas__action mb-15 d-sm-block">
                              <a href="cart.html" class="has-tag">
                                <i class="far fa-shopping-bag"></i>
                                <span class="tag">4</span>
                              </a>
                           </div>
                           <div class="offcanvas__social mt-15">
                              <ul>
                                 <li>
                                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                                 </li>
                                 <li>
                                    <a href="#"><i class="fab fa-twitter"></i></a>
                                 </li>
                                 <li>
                                    <a href="#"><i class="fab fa-instagram"></i></a>
                                 </li>
                                 <li>
                                    <a href="#"><i class="fab fa-google-plus-g"></i></a>
                                 </li>
                                 <li>
                                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                                 </li>
                              </ul>
                           </div>
                        </div>
                     </div>
                </div>
            </div>
        </div>
    </section>
    <!-- sidebar area end -->

    <main>

        <!-- slider area start -->
        <section class="slider-area pb-20 p-rel">
            <div class="slider-active dot-style dot-style-1 dot-right">
                <div class="single-slider slider-height" data-background="assets/img/slider/2.jpg">
                </div>
                <div class="single-slider slider-height" data-background="assets/img/slider/2.jpg">
                </div>
                <div class="single-slider slider-height" data-background="assets/img/slider/3.jpg">
                </div>
                <div class="single-slider slider-height" data-background="assets/img/slider/4.jpg">
                </div>
            </div>

        </section>
        <!-- slider area end -->

        <!-- breadcrumb area start -->
        <div class="breadcrumb-area pt-10 pb-80 mb-10" data-background="assets/img/banner/breadcrumb.jpg">
            <div class="container">
                <div class="breadcrumb-title text-center">
                    <h2>方哥的商店</h2>
                </div>
                <div class="breadcrumb-list" style="font-size: 20px;">
                    <a href="index.php">首頁</a>
                    <a href="">方哥的商店</a>
                    <span>瀏覽商品</span>
                </div>
            </div>
        </div>
        <!-- breadcrumb area end -->

        <?php
    include "config.php";
    $db_link->query("SET NAMES 'utf8'");

    // 每頁顯示商品數量
    $per_page = 12;

    // 取得目前頁碼，若未指定則為1
    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($current_page < 1) { 
        $current_page = 1; 
    }

    // 查詢產品總數 (計算分頁用)
    $count_query = "SELECT COUNT(*) as total FROM products";
    $count_result = $db_link->query($count_query);
    $count_row    = $count_result->fetch_assoc();
    $total_products = $count_row['total'];

    // 計算總頁數
    $total_pages = ($total_products > 0) ? ceil($total_products / $per_page) : 1;

    // 確保當前頁數不超過總頁數
    if ($current_page > $total_pages) {
        $current_page = $total_pages;
    }

    // 計算起始筆數
    $start = ($current_page - 1) * $per_page;

    // 獲取所有類別及其商品數量 (仍然以 c.name 做 FIND_IN_SET)
    $query = "
        SELECT 
            c.id AS category_id, 
            c.name AS category_name, 
            COUNT(p.id) AS product_count 
        FROM categories c
        LEFT JOIN products p 
            ON FIND_IN_SET(c.name, p.category_ids) > 0
        GROUP BY c.id, c.name
    ";
    $result = $db_link->query($query);

    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }

    // 從資料庫抓取所有標籤
    $tag_query = "SELECT id, name FROM tags";
    $tag_result = $db_link->query($tag_query);

    $tags = [];
    while ($tag_row = $tag_result->fetch_assoc()) {
        $tags[] = $tag_row;
    }

    // ★★★ 隨機洗牌 & 取前 9 筆標籤 ★★★
    shuffle($tags);
    $tags = array_slice($tags, 0, 9);

    // 讀取多選分類 (cat_ids[]) 與點擊標籤 (tag)
    // 這裡我們把 cat_ids[] 視為「分類名稱」陣列
    $selected_cat_names = isset($_GET['cat_ids']) ? $_GET['cat_ids'] : []; 
    $selected_tag       = isset($_GET['tag'])     ? trim($_GET['tag']) : '';

    // 建立 WHERE 條件陣列
    $where_clauses = [];

    // 若有勾選多個「分類名稱」
    if (!empty($selected_cat_names)) {
        // ex: (FIND_IN_SET('Shoes', p.category_ids) OR FIND_IN_SET('Dress', p.category_ids))
        $cat_sub = [];
        foreach($selected_cat_names as $cat_name) {
            $safe_cat_name = $db_link->real_escape_string($cat_name);
            $cat_sub[] = "FIND_IN_SET('$safe_cat_name', p.category_ids) > 0";
        }
        $where_clauses[] = '('. implode(' OR ', $cat_sub) .')';
    }

    // 若有點選標籤
    if (!empty($selected_tag)) {
        // 假設 p.tag_ids 以逗號分隔儲存「標籤名稱」
        $safe_tag = $db_link->real_escape_string($selected_tag);
        $where_clauses[] = "FIND_IN_SET('$safe_tag', p.tag_ids) > 0";
    }

    // ★★★ 新增：讀取使用者輸入之「價格範圍」(price_min / price_max) ★★★
    $price_min = isset($_GET['price_min']) ? (int)$_GET['price_min'] : 0;
    $price_max = isset($_GET['price_max']) ? (int)$_GET['price_max'] : 0;

    // 如果有輸入最小值
    if ($price_min > 0) {
        $where_clauses[] = "p.price_new >= {$price_min}";
    }
    // 如果有輸入最大值且 >= 最小值
    if ($price_max > 0 && $price_max >= $price_min) {
        $where_clauses[] = "p.price_new <= {$price_max}";
    }

    // 組裝完整的 WHERE SQL
    $where_sql = '';
    if (!empty($where_clauses)) {
        $where_sql = 'WHERE '. implode(' AND ', $where_clauses);
    }

    // 排序
    $order_by = ""; // 預設不排序
    if (isset($_GET['short'])) {
        if ($_GET['short'] === 'ASC') {
            $order_by = "ORDER BY p.price_new ASC";
        } else if ($_GET['short'] === 'DEC') {
            $order_by = "ORDER BY p.price_new DESC";
        }
    }

    // 最終查詢 (整合 WHERE + ORDER BY + LIMIT)
    $query_products = "
        SELECT 
            p.id, 
            p.name AS title, 
            p.price_new AS price, 
            p.price_old AS original_price, 
            p.availability AS rating,
            i.image1, i.image2, i.image3, i.image4,
            COALESCE(AVG(r.rating), 0) AS avg_rating,
            COUNT(r.id) AS review_count
        FROM products p
        LEFT JOIN product_images i ON p.id = i.product_id
        LEFT JOIN reviews r ON p.id = r.product_id
        $where_sql
        GROUP BY p.id
        $order_by
        LIMIT $start, $per_page
    ";

    $product_result = $db_link->query($query_products);
    $products = [];

    while ($row = $product_result->fetch_assoc()) {
        $row['images'] = array_filter([
            $row['image1'],
            $row['image2']
        ]);
        unset($row['image1'], $row['image2'], $row['image3'], $row['image4']);
        $products[] = $row;
    }
?>

<!-- shop area start -->
<div class="shop-area mb-70">
    <div class="container">
        <div class="row">
            <div class="col-xxl-3 col-xl-3 col-lg-4">
                <div class="shop-sidebar-area pt-7 pr-60">
                    <div class="single-widget pb-50 mb-50">
                        <h4 class="widget-title">產品分類</h4>
                        <div class="widget-category-list">
                            <!-- ★★ 用 GET 提交，並在 checkbox 的 onchange 直接提交表單 ★★ -->
                            <form method="GET" action="">
                                <?php foreach($categories as $cat) : ?>
                                    <?php 
                                        $checkbox_id = "cat-item-" . $cat['category_id']; 
                                        // 用分類名稱作為 checkbox 的值
                                        $value_for_checkbox = $cat['category_name'];

                                        // 若已勾選, 預設checked
                                        $checked = in_array($value_for_checkbox, $selected_cat_names) ? 'checked' : '';
                                    ?>
                                    <div class="single-widget-category">
                                        <input type="checkbox"
                                               id="<?php echo $checkbox_id; ?>" 
                                               name="cat_ids[]" 
                                               value="<?php echo htmlspecialchars($value_for_checkbox, ENT_QUOTES); ?>" 
                                               <?php echo $checked; ?>
                                               onchange="this.form.submit();">
                                        
                                        <label for="<?php echo $checkbox_id; ?>">
                                            <?php echo htmlspecialchars($cat['category_name']); ?> 
                                            <span>(<?php echo $cat['product_count']; ?>)</span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>

                                <?php if(isset($_GET['short'])): ?>
                                   <input type="hidden" name="short" value="<?php echo htmlspecialchars($_GET['short']); ?>">
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                    
                    <!-- 1) HTML 區塊：在 form 上加 onsubmit 屬性，並替兩個輸入框加上 ID -->
                    <div class="single-widget mb-50">
                        <h4 class="widget-title title-price-space">價格範圍</h4>
                        <form action="" method="GET" id="priceForm" onsubmit="return checkPrice();">
                            <!-- 若需保留分類/標籤/排序參數，需一併以 hidden 帶回 -->
                            <?php if(!empty($selected_cat_names)): 
                                foreach($selected_cat_names as $scn): ?>
                                    <input type="hidden" name="cat_ids[]" value="<?php echo htmlspecialchars($scn, ENT_QUOTES); ?>">
                                <?php endforeach; 
                            endif; ?>
                            <?php if(!empty($selected_tag)): ?>
                                <input type="hidden" name="tag" value="<?php echo htmlspecialchars($selected_tag, ENT_QUOTES); ?>">
                            <?php endif; ?>
                            <?php if(isset($_GET['short'])): ?>
                                <input type="hidden" name="short" value="<?php echo htmlspecialchars($_GET['short']); ?>">
                            <?php endif; ?>

                            <div class="mb-3" style="text-align: center;">
                                <!-- 最小值輸入框 -->
                                <input type="text"
                                    name="price_min"
                                    id="price_min"
                                    class="gray-placeholder"
                                    value="<?php echo isset($_GET['price_min']) ? (int)$_GET['price_min'] : ''; ?>"
                                    style="width: 35%; margin-right: 5px;"
                                    placeholder="$最小值"
                                >

                                <span style="margin-right: 5px; font-weight: bold; color: #555;">~</span>

                                <!-- 最大值輸入框 -->
                                <input type="text"
                                    name="price_max"
                                    id="price_max"
                                    class="gray-placeholder"
                                    value="<?php echo isset($_GET['price_max']) ? (int)$_GET['price_max'] : ''; ?>"
                                    style="width: 35%;"
                                    placeholder="$最大值"
                                >

                                <!-- 套用按鈕-->
                                <button type="submit" class="widget-title" style="margin-left: 5px;">
                                    套用
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- 驗證輸入值-->
                    <script>
                    function checkPrice(){
                        // 取得使用者輸入的最小值、最大值；若轉換失敗則預設為 0
                        let minVal = parseInt(document.getElementById('price_min').value) || 0;
                        let maxVal = parseInt(document.getElementById('price_max').value) || 0;

                        // 檢查不可輸入負數
                        if(minVal < 0){
                            alert('最小值不可為負數');
                            return false;  // 中斷送出
                        }
                        if(maxVal < 0){
                            alert('最大值不可為負數');
                            return false;  // 中斷送出
                        }

                        // 檢查最大值不可小於最小值
                        if(maxVal < minVal){
                            alert('最大值不可小於最小值');
                            return false;  // 中斷送出
                        }

                        // 若以上條件都通過，允許送出
                        return true;
                    }
                    </script>


                    <!--隨機顯示 9 個標籤-->
                    <div class="single-widget pb-50 mb-50">
                        <h4 class="widget-title">產品關鍵字</h4>
                        <div class="tagcloud">
                            <?php foreach ($tags as $tag_item): ?>
                                <a href="?tag=<?php echo urlencode($tag_item['name']); ?>">
                                    <?php echo htmlspecialchars($tag_item['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>
            </div>

            <!-- JS偵測使用者重整(Refresh) 來清除 cat_ids & tag -->
            <script>
            (function(){
                // 檢查瀏覽器的Navigation Type
                if (performance && performance.navigation && performance.navigation.type === performance.navigation.TYPE_RELOAD) {
                    // 使用者是手動刷新本頁(F5、瀏覽器重新整理按鈕、Ctrl+R等)
                    // 刪除 cat_ids[] 和 tag 等篩選參數後重新導向
                    const url = new URL(window.location.href);

                    // 刪除多選 cat_ids[] (可能是多筆)
                    url.searchParams.delete('tag'); // if any
                    // 迴圈刪除 cat_ids[]
                    // 因為 cat_ids[] 可能會有多個 key, 需反覆刪除
                    while(url.searchParams.has('cat_ids[]')){
                        url.searchParams.delete('cat_ids[]');
                    }

                    // 若您想清除排序等參數, 也可加上
                    // url.searchParams.delete('short');

                    window.location.replace(url.toString());
                }
            })();
            </script>



            <div class="col-xxl-9 col-xl-9 col-lg-8 order-first order-lg-last">
                <div class="shop-top-area mb-40">
                    <div class="row">
                        <div class="col-xxl-4 col-xl-2 col-md-3 col-sm-3">
                            <div class="shop-top-left">
                                <span class="label mr-15">檢視方式:</span>
                                <div class="nav d-inline-block tab-btn-group" id="nav-tab" role="tablist">
                                    <!-- 網格視圖按鈕 -->
                                    <button class="active" data-bs-toggle="tab" data-bs-target="#tab1" type="button">
                                        <i class="fas fa-th"></i>
                                    </button>
                                    <!-- 列表視圖按鈕 -->
                                    <button data-bs-toggle="tab" data-bs-target="#tab2" type="button">
                                        <i class="fas fa-list-ul"></i>
                                    </button>
                                </div>

                            </div>
                        </div>
                        <div class="col-xxl-4 col-xl-6 col-md-6 col-sm-6">
                            <p class="show-total-result text-sm-center">
                                Showing 
                                <?php echo ($total_products > 0)? (($start+1) . '–' . min($start + $per_page, $total_products)) : 0; ?> 
                                of 
                                <?php echo $total_products; ?> 
                                results
                            </p>
                        </div>
                        <div class="col-xl-4 col-xl-4 col-md-3 col-sm-3">
                            <div class="text-sm-end">
                               <div class="select-default">
                                   <form method="GET" id="sorting-form">
                                       <?php if($current_page > 1): ?>
                                           <input type="hidden" name="page" value="<?php echo $current_page; ?>">
                                       <?php endif; ?>

                                       <select name="short" id="short" class="shorting-select" 
                                               onchange="document.getElementById('sorting-form').submit();">
                                           <option value="">預設排序</option>
                                           <option value="ASC"  <?php if(isset($_GET['short']) && $_GET['short'] === 'ASC')  echo 'selected'; ?>>價格:低到高</option>
                                           <option value="DEC"  <?php if(isset($_GET['short']) && $_GET['short'] === 'DEC')  echo 'selected'; ?>>價格:高到低</option>
                                       </select>
                                   </form>
                               </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /. shop top area -->
                <div class="shop-main-area">
          <div class="tab-content" id="nav-tabContent">

            <!-- tab1：網格顯示 -->
            <div class="tab-pane fade show active" id="tab1">
              <div class="row pb-20">
                <?php foreach ($products as $product): ?>
                <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-sm-6">
                  <div class="single-product mb-15 wow fadeInUp" data-wow-delay=".1s"
                       style="height: 400px; display: flex; flex-direction: column; border: 1px solid #eee;">

                    <!-- 圖片區 -->
                    <div class="product-thumb"
                         style="flex:1; overflow:hidden; display:flex; align-items:center; justify-content:center; background-color:#f9f9f9;">
                      <?php
                        $images = $product['images'];
                        $firstImage = $images ? reset($images) : null;
                        $secondImage = $images ? next($images) : null;
                      ?>
                      <?php if ($firstImage): ?>
                        <?php echo "<a href='http://localhost:8080/Final%20Project/product.php?id=".$product['id']."'>";?><img src="data:image/jpeg;base64,<?= base64_encode($firstImage) ?>"
                             alt="#"
                             style="max-width:100%; max-height:100%;">
                      <?php else: ?>
                        <img src="static2/picture/no-image.jpg" alt="#"
                             style="max-width:100%; max-height:100%;">
                      <?php endif; ?>

                      <?php if ($secondImage): ?>
                        <img src="data:image/jpeg;base64,<?= base64_encode($secondImage) ?>"
                             alt="#"
                             style="max-width:100%; max-height:100%;"></a>
                      <?php endif; ?>

                      <!-- ★★ 這裡改為一個加入購物車的表單 ★★ -->
                      <div class="cart-btn cart-btn-1 p-abs">
                        <form action="shop.php" method="POST">
                            <input type="hidden" name="action" value="add_to_cart">
                            <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="product-modal-cart-btn">
                            加入購物車
                            </button>
                        </form>
                        </div>
                    </div>

                    <!-- 文字區 -->
                    <div class="product-content" style="padding:10px; background:#fff;">
                      <h4 class="pro-title pro-title-1">
                        <a href="product-details.html">
                          <?= htmlspecialchars($product['title']); ?>
                        </a>
                      </h4>
                      <div class="pro-price">
                        <span>NT <?= htmlspecialchars($product['price']); ?></span>
                        <?php if (!empty($product['original_price'])): ?>
                          <del>NT <?= htmlspecialchars($product['original_price']); ?></del>
                        <?php endif; ?>
                      </div>
                      <div class="rating">
                        <?php for ($i=0; $i<5; $i++): ?>
                          <i class="fal fa-star <?= ($i < round($product['avg_rating'])) ? 'active':''; ?>"></i>
                        <?php endfor; ?>
                        <span>(<?= htmlspecialchars($product['review_count']); ?> reviews)</span>
                      </div>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <!-- tab1：網格顯示 end -->


                    <!-- tab2：列表顯示 -->
<div class="tab-pane fade" id="tab2">
  <div class="product-wrap">
    <?php foreach ($products as $product): ?>
      <div class="single-product mb-30 puik-list-product-wrap">
        <div class="row align-items-xl-center">
          <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4">
            <div class="product-thumb mr-30 product-thumb-list">
              <?php 
                $images = $product['images'];
                $listFirstImage = $images ? reset($images) : null;
                $listSecondImage = $images ? next($images) : null;
              ?>
              <?php if($listFirstImage): ?>
                <img src="data:image/jpeg;base64,<?= base64_encode($listFirstImage) ?>" alt="#">
              <?php else: ?>
                <img src="static2/picture/no-image.jpg" alt="#">
              <?php endif; ?>
              <?php if($listSecondImage): ?>
                <img src="data:image/jpeg;base64,<?= base64_encode($listSecondImage) ?>" alt="#">
              <?php endif; ?>
            </div>
          </div>

          <div class="col-xxl-8 col-xl-8 col-lg-8 col-md-8">
            <div class="puik-product-content puik-product-list-content">
              <h4 class="pro-title pro-title-1">
                <a href="product-details.html">
                  <?= htmlspecialchars($product['title']); ?>
                </a>
              </h4>
              <div class="pro-price">
                <span>NT <?= htmlspecialchars($product['price']); ?></span>
                <?php if (!empty($product['original_price'])): ?>
                  <del>NT <?= htmlspecialchars($product['original_price']); ?></del>
                <?php endif; ?>
              </div>
              <div class="rating">
                <?php for ($i = 0; $i < 5; $i++): ?>
                  <i class="fal fa-star <?= ($i < round($product['avg_rating'])) ? 'active' : ''; ?>"></i>
                <?php endfor; ?>
                <span>(<?= htmlspecialchars($product['review_count']); ?> reviews)</span>
              </div>


              <div class="puik-shop-product-actions">
                <!-- ★★ 將原本的 <a> 替換成表單 + 按鈕 ★★ -->
                <form action="shop.php" method="POST" style="display:inline-block;">
                  <input type="hidden" name="action" value="add_to_cart">
                  <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                  <input type="hidden" name="quantity" value="1">
                  <button type="submit" class="puik-cart-btn">
                    加入購物車
                  </button>
                </form>

              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<!-- tab2：列表顯示 end -->


                  </div>
                </div>

                <!-- 分頁 -->
                <?php if ($total_products > 0): ?>
                <div class="shop-pagination">
                  <div class="basic-pagination">
                    <nav>
                      <ul>
                        <!-- 上一頁 -->
                        <li>
                          <a href="?page=<?= max(1, $current_page - 1); ?>">
                            <i class="fas fa-chevron-left"></i>
                          </a>
                        </li>
                        
                        <!-- 頁碼連結 -->
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                          <li>
                            <a href="?page=<?= $i; ?>"
                               class="<?= ($i == $current_page) ? 'active' : ''; ?>">
                              <?= $i; ?>
                            </a>
                          </li>
                        <?php endfor; ?>

                        <!-- 下一頁 -->
                        <li>
                          <a href="?page=<?= min($total_pages, $current_page + 1); ?>">
                            <i class="fas fa-chevron-right"></i>
                          </a>
                        </li>
                      </ul>
                    </nav>
                  </div>
                </div>
                <?php endif; ?>

              </div>
            </div>
          </div>
        </div>
        <!-- shop area end -->


        <!-- product modal area start -->
        <div class="product__modal-area modal fade" id="productModal" tabindex="-1" role="dialog" aria-labelledby="productModal" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
              <div class="modal-content">
                <div class="product__modal-inner position-relative">
                    <div class="product__modal-close">
                        <button data-bs-dismiss="modal" aria-label="Close">
                            <i class="ti-close"></i>
                        </button>
                    </div>
                    <div class="product__modal-left">
                        <div class="tab-content mb-10" id="productModalThumb">
                            <div class="tab-pane fade show active" id="pro-1" role="tabpanel" aria-labelledby="pro-1-tab">
                                <div class="product__modal-thumb w-img">
                                    <img src="static2/picture/product-modal-1.jpg" alt="">
                                </div>
                            </div>
                            <div class="tab-pane fade" id="pro-2" role="tabpanel" aria-labelledby="pro-2-tab">
                                <div class="product__modal-thumb w-img">
                                    <img src="static2/picture/product-modal-2.jpg" alt="">
                                </div>
                            </div>
                            <div class="tab-pane fade" id="pro-3" role="tabpanel" aria-labelledby="pro-3-tab">
                                <div class="product__modal-thumb w-img">
                                    <img src="static2/picture/product-modal-3.jpg" alt="">
                                </div>
                            </div>
                            <div class="tab-pane fade" id="pro-4" role="tabpanel" aria-labelledby="pro-4-tab">
                                <div class="product__modal-thumb w-img">
                                    <img src="static2/picture/product-modal-4.jpg" alt="">
                                </div>
                            </div>
                        </div>
                        <div class="product__modal-nav">
                            <ul class="nav nav-tabs" id="productModalNav" role="tablist">
                                <li class="nav-item" role="presentation">
                                  <button class="nav-link active" id="pro-1-tab" data-bs-toggle="tab" data-bs-target="#pro-1" type="button" role="tab" aria-controls="pro-1" aria-selected="true">
                                      <img src="static2/picture/product-modal-sm-1.jpg" alt="">
                                  </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                  <button class="nav-link" id="pro-2-tab" data-bs-toggle="tab" data-bs-target="#pro-2" type="button" role="tab" aria-controls="pro-2" aria-selected="false">
                                    <img src="static2/picture/product-modal-sm-2.jpg" alt="">
                                  </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                  <button class="nav-link" id="pro-3-tab" data-bs-toggle="tab" data-bs-target="#pro-3" type="button" role="tab" aria-controls="pro-3" aria-selected="false">
                                    <img src="static2/picture/product-modal-sm-3.jpg" alt="">
                                  </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="pro-4-tab" data-bs-toggle="tab" data-bs-target="#pro-4" type="button" role="tab" aria-controls="pro-4" aria-selected="false">
                                      <img src="static2/picture/product-modal-sm-4.jpg" alt="">
                                    </button>
                                </li>
                              </ul>
                        </div>
                    </div>
                    <div class="product__modal-right">
                        <h3 class="product__modal-title">
                            <a href="product-details.html">Living Room Lighting</a>
                        </h3>
                        <div class="product__modal-rating d-flex align-items-center">
                            <ul class="mr-10">
                                <li>
                                    <a href="#"><i class="ti-star"></i></a>
                                </li>
                                <li>
                                    <a href="#"><i class="ti-star"></i></a>
                                </li>
                                <li>
                                    <a href="#"><i class="ti-star"></i></a>
                                </li>
                                <li>
                                    <a href="#"><i class="ti-star"></i></a>
                                </li>
                                <li>
                                    <a href="#"><i class="ti-star"></i></a>
                                </li>
                            </ul>
                            <div class="customer-review">
                                <a href="#">(1 customer review)</a>
                            </div>
                        </div>
                        <div class="product__modal-price mb-10">
                            <span class="price new-price">$700.00</span>

                            <span class="price old-price">$899.99</span>
                        </div>
                        <div class="product__modal-available">
                            <p> Availability: <span>In Stock</span> </p>
                        </div>
                        <div class="product__modal-sku">
                            <p> Sku: <span> 0026AG90-1</span> </p>
                        </div>
                        <div class="product__modal-des">
                            <p>Typi non habent claritatem insitam, est usus legentis in iis qui facit eorum claritatem. Investigationes demonstraverunt lectores legere me lius quod legunt saepius.…</p>
                        </div>
                        <div class="product__modal-quantity mb-15">
                            <h5>Quantity:</h5>
                            <form action="#">
                                <div class="pro-quan-area d-lg-flex align-items-center">
                                    <div class="product-quantity mr-20 mb-25">
                                        <div class="cart-plus-minus p-relative"><input type="text" value="1"></div>
                                    </div>
                                    <div class="product__modal-cart mb-25">
                                        <button class="product-modal-cart-btn " type="submit">Add to cart</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="product__modal-categories d-flex align-items-center">
                            <span>Categories: </span>
                            <ul>
                                <li><a href="#">Decor, </a></li>
                                <li><a href="#">Lamp, </a></li>
                                <li><a href="#">Lighting</a></li>
                            </ul>
                        </div>
                        <div class="product__modal-categories d-flex align-items-center">
                            <span>Tags: </span>
                            <ul>
                                <li><a href="#">Furniture, </a></li>
                                <li><a href="#">Lighting, </a></li>
                                <li><a href="#">Living Room, </a></li>
                                <li><a href="#">Table</a></li>
                            </ul>
                        </div>
                        <div class="product__modal-share d-flex align-items-center">
                            <span>Share this product: </span>
                            <ul>
                                <li>
                                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                                </li>
                                <li>
                                    <a href="#"><i class="fab fa-twitter"></i></a>
                                </li>
                                <li>
                                    <a href="#"><i class="fab fa-pinterest-p"></i></a>
                                </li>
                                <li>
                                    <a href="#"><i class="fab fa-google-plus-g"></i></a>
                                </li>
                                <li>
                                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
              </div>
            </div>
        </div>
        <!-- product modal area start -->

        <!-- popup area start -->
        <div class="subscribe-popup d-none">      
            <div class="subscribe-wrapper s-popup-padding h-100">
                <div class="pl-75 pr-75">
                    <div class="row">
                        <div class="col-xxl-6">
                            <div class="subscribe-content">
                                <div class="logo mb-65">
                                    <a href="index.html"><img src="static2/picture/logo-black.png" alt=""></a>
                                </div>
                                <h4 class="popup-title">Comming Soon</h4>
                                <p class="popup-desc">We’ll be here soon with our new<br> 
                                    awesome site, subscribe to be notified.</p>
                                <div class="comming-countdown  pb-45">
                                    <div class="countdown-inner" data-countdown="" data-date="Mar 02 2022 20:20:22">
                                        <ul>
                                            <li><span data-days="">401</span> Day</li>
                                            <li><span data-hours="">1</span> hrs</li>
                                            <li><span data-minutes="">29</span> min</li>
                                            <li><span data-seconds="">40</span> sec</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="subscribe-form-2">
                                    <input type="email" placeholder="Enter your email...">
                                    <button class="p-btn border-0">Subscribe</button>
                                </div>
                                <div class="popup-social">
                                    <a href="#"><i class="fal fa-facebook"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <div class="subscribe-thumb" data-background="assets/img/popup/fashion/subscribe-bg.jpg"></div>
        </div>
        <!-- popup area end -->

        

</main>

    

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