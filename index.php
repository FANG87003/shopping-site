<?php
session_start();  // 確保有啟用 session

// 如果使用者按了加入購物車 (POST: action=add_to_cart)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {

    // 1) 取得表單資料 (商品ID, 名稱, 價格, 數量, 圖片)
    $prod_id   = isset($_POST['id'])         ? (int)$_POST['id'] : 0;
    $prod_name = isset($_POST['name'])       ? trim($_POST['name']) : '';
    $prod_price= isset($_POST['price'])      ? (float)$_POST['price'] : 0.0;
    $prod_qty  = isset($_POST['quantity'])   ? (int)$_POST['quantity'] : 1;
    $prod_img  = isset($_POST['image_blob']) ? base64_decode($_POST['image_blob']) : '';

    // 數量最小保護
    if ($prod_qty < 1) $prod_qty = 1;

    // 2) 準備要放進購物車的陣列
    $cart_item = [
        'id'       => $prod_id,
        'name'     => $prod_name,
        'price'    => $prod_price,
        'quantity' => $prod_qty,
        'image'    => $prod_img   // BLOB
    ];

    // 3) 如果 session 裡沒有 cart，就先初始化
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // 4) 檢查購物車內是否已經有此商品
    $found = false;
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['id'] == $prod_id) {
            // 已存在 => 數量累加
            $_SESSION['cart'][$key]['quantity'] += $prod_qty;
            $found = true;
            break;
        }
    }
    // 如果在購物車找不到，則直接 push
    if (!$found) {
        $_SESSION['cart'][] = $cart_item;
    }

    // 5) 加入完成後，可用 JS alert 或導回本頁並顯示訊息
    echo "<script>alert('商品已加入購物車!');window.location.href='index.php';</script>";
    exit;
}
?>
<?php
include "config.php";
$db_link->query("SET NAMES 'utf8'");

// 定義可隨機挑選的類別
$allCats = ["女裝","配件","書籍","家電","家具","男裝","3C","鞋子"];

// 洗牌並取前 5 筆(範例): 1 張大圖 + 4 小圖
shuffle($allCats);
$selectedCats = array_slice($allCats, 0, 5);

$randomCategories = [];

foreach($selectedCats as $catName) {
    // 1) 取得「該類別隨機商品」(為了顯示圖片)
    $queryProduct = "
        SELECT 
            p.id,
            p.name AS product_name,
            i.image1
        FROM products p
        LEFT JOIN product_images i ON p.id = i.product_id
        WHERE FIND_IN_SET('$catName', p.category_ids) > 0
        ORDER BY RAND()
        LIMIT 1
    ";
    $resProduct = $db_link->query($queryProduct);

    // 2) 取得「該類別總商品數量」
    $queryCount = "
        SELECT COUNT(*) as total_count
        FROM products
        WHERE FIND_IN_SET('$catName', category_ids) > 0
    ";
    $resCount = $db_link->query($queryCount);
    $countRow = $resCount->fetch_assoc();
    $catTotal = (int)$countRow['total_count'];

    // 組合結果
    if($row = $resProduct->fetch_assoc()) {
        // 有隨機商品
        $randomCategories[] = [
            'category'      => $catName,
            'product_id'    => $row['id'],
            'product_image' => $row['image1'], // BLOB或路徑
            'count'         => $catTotal       // 顯示此類別共有幾件商品
        ];
    } else {
        // 該類別查無商品
        $randomCategories[] = [
            'category'      => $catName,
            'product_id'    => null,
            'product_image' => null,
            'count'         => 0
        ];
    }
}
?>




<!doctype html>
<html class="no-js" lang="zxx">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>方先生的購物網站</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS here -->
    <link rel="stylesheet" href="static2/css/preloader.css">
    <link rel="stylesheet" href="static2/css/bootstrap.min.css">
    <link rel="stylesheet" href="static2/css/owl.carousel.min.css">
    <link rel="stylesheet" href="static2/css/animate.min.css">
    <link rel="stylesheet" href="static2/css/magnific-popup.css">
    <link rel="stylesheet" href="static2/css/meanmenu.css">
    <link rel="stylesheet" href="static2/css/animate.min.css">
    <link rel="stylesheet" href="static2/css/slick.css">
    <link rel="stylesheet" href="static2/css/fontawesome-all.min.css">
    <link rel="stylesheet" href="static2/css/themify-icons.css">
    <link rel="stylesheet" href="static2/css/nice-select.css">
    <link rel="stylesheet" href="static2/css/ui-range-slider.css">
    <link rel="stylesheet" href="static2/css/main.css">

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
                                    <a href=""><img src="static/picture/NCUT1.png" alt="#"></a>
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
                                    <!-- ★★ 右上角購物車按鈕，動態顯示購物車數量 ★★ -->
                                    <div class="header-icon d-inline-block ml-30">
                                        <?php
                                          // 計算目前購物車內的「總商品數量(累加 quantity)」
                                          $cartCount = 0;
                                          if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                                              foreach ($_SESSION['cart'] as $it) {
                                                  $cartCount += $it['quantity'];
                                              }
                                          }
                                        ?>
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
                        </div> <!-- /. col-xxl-3 -->
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
                                <a href="">
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
            <div class="slider-scroll p-abs">
                <a href="#category-area"><i class="fal fa-angle-double-down"></i></a>
            </div>
        </section>
        <!-- slider area end -->

<!-- category area start -->
<div class="category-area pb-75" id="category-area">
  <div class="pl-20 pr-20">
    <div class="row row-2">

      <!-- 大圖 (index=0) -->
<?php
if (!empty($randomCategories[0])) {
    $big = $randomCategories[0];
    $catName  = htmlspecialchars($big['category'], ENT_QUOTES);
    $catCount = (int)$big['count'];  // 類別總商品數
    // 若是 BLOB 就 base64_encode
    $imgData  = $big['product_image']
                ? 'data:image/jpeg;base64,' . base64_encode($big['product_image'])
                : 'static2/picture/no-image.jpg';
    
    echo '
    <div class="col-xxl-6 col-xl-6 col-md-6 col-12">
        <div class="single-category mb-20 p-rel wow fadeInUp" data-wow-delay=".1s">
            <a href="shop.php?' . http_build_query(['cat_ids' => [$catName]]) . '">
                <!-- 大圖容器: 高300px + overflow hidden, 可依需求調整 -->
                <div class="cat-thumb fix" style="height:680px; overflow:hidden;">
                    <!-- 圖片: 寬100%, 高100%, object-fit:cover -->
                    <img src="' . $imgData . '" alt="#" 
                        style="width:100%; height:100%; object-fit:cover;">
                </div>
                <div class="cat-content p-abs bottom-left">
                    <h4 class="pb-15">' . $catName . '</h4>
                    <span class="cat-subtitle"> ' . $catCount . ' 件商品</span>
                </div>
            </a>
        </div>
    </div>
    ';
}
?>

<!-- 右側 4 小圖 (index=1~4) -->
<div class="col-xxl-6 col-xl-6 col-md-6 col-12">
    <div class="row row-2">
        <?php
        // 4張小圖
        for($i=1; $i<=4; $i++){
            if (!empty($randomCategories[$i])) {
                $cat      = $randomCategories[$i];
                $catName  = htmlspecialchars($cat['category'], ENT_QUOTES);
                $catCount = (int)$cat['count'];
                $imgData  = $cat['product_image']
                            ? 'data:image/jpeg;base64,' . base64_encode($cat['product_image'])
                            : 'static2/picture/no-image.jpg';

                // 第1張 .3s, 第2張 .6s, ...
                $delay = 0.3 + (($i-1)*0.3);

                echo '
                <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6 col-sm-6">
                    <a href="shop.php?' . http_build_query(['cat_ids' => [$catName]]) . '">
                        <div class="single-category mb-20 p-rel wow fadeInUp" data-wow-delay="' . $delay . 's">
                            <!-- 小圖容器: 高350px + overflow hidden -->
                            <div class="cat-thumb fix" style="height:340px; overflow:hidden;">
                                <img src="' . $imgData . '" alt="#"
                                    style="width:100%; height:100%; object-fit:cover;">
                            </div>
                            <div class="cat-content p-abs bottom-left">
                                <h4 class="pb-15">' . $catName . '</h4>
                                <span class="cat-subtitle"> ' . $catCount . ' 件商品</span>
                            </div>
                        </div>
                    </a>
                </div>
                ';
            }
        }
        ?>
    </div> <!-- /.row-2 -->
</div>
<!-- 右邊 4 張小圖 end -->


    </div>
  </div>
</div>
<!-- category area end -->






<?php
include "config.php";
$db_link->query("SET NAMES 'utf8'");

// 從資料庫獲取產品和對應圖片及評論數據
$query = "
    SELECT 
        p.id, 
        p.name AS title, 
        p.description, 
        p.price_new AS price, 
        p.price_old AS original_price, 
        p.availability AS rating, 
        p.category_ids, 
        p.tag_ids,
        i.image1, i.image2, i.image3, i.image4,
        COALESCE(AVG(r.rating), 0) AS avg_rating,
        COUNT(r.id) AS review_count
    FROM products p
    LEFT JOIN product_images i ON p.id = i.product_id
    LEFT JOIN reviews r ON p.id = r.product_id
    GROUP BY p.id
    ORDER BY p.Psales DESC
    LIMIT 4
";
$result = $db_link->query($query);
$products = [];

while ($row = $result->fetch_assoc()) {
    $row['images'] = array_filter([
        $row['image1']
    ]);
    unset($row['image1'], $row['image2'], $row['image3'], $row['image4']);
    $products[] = $row;
}
?>

<!-- top selling area start -->
<div class="top-selling-area top-selling-padding pb-100">
    <div class="container">
        <div class="row">
            <div class="col-xxl-12">
                <div class="section-title top-selling-title text-center pb-47">
                    <span class="p-subtitle">探索你的時尚風格</span>
                    <h3 class="p-title pb-15 mb-0">最暢銷商品!!</h3>
                    <p class="p-desc">精選人氣商品，為你呈現時尚與品質的完美結合。</p>
                </div>
            </div>
        </div>
        <div class="row pb-20">
            <?php foreach ($products as $product): ?>
                <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="single-product mb-15 wow fadeInUp" data-wow-delay=".1s"
                         style="
                            height: 500px; 
                            display: flex; 
                            flex-direction: column;
                            overflow: hidden; 
                            position: relative; 
                            border: 1px solid #eee;
                            box-sizing: border-box;
                    ">
                        <!-- 圖片區 -->
                        <div class="product-thumb"
                             style="
                                flex: 1; 
                                display: flex; 
                                align-items: center; 
                                justify-content: center; 
                                overflow: hidden; 
                                background-color: #f9f9f9;
                             ">
                            <?php foreach ($product['images'] as $image): ?>
                                <!-- 點擊圖片 => 開啟 modal -->
                                <a href="javascript:void(0)"
                                   data-bs-toggle="modal"
                                   data-bs-target="#productModal-<?= $product['id'] ?>">
                                    <img src="data:image/jpeg;base64,<?= base64_encode($image) ?>" 
                                         alt="#" 
                                         style="max-width: 100%; max-height: 100%; cursor: pointer;">
                                </a>
                            <?php endforeach; ?>

                            <!-- ★★ 加入購物車表單(與 modal 相同做法) ★★ -->
                            <div class="cart-btn cart-btn-1 p-abs">
                                <form action="index.php" method="POST">
                                    <!-- 隱藏欄位: 告訴後端要 add_to_cart -->
                                    <input type="hidden" name="action" value="add_to_cart">
                                    <!-- 商品資訊 -->
                                    <input type="hidden" name="id"    value="<?= $product['id'] ?>">
                                    <input type="hidden" name="name"  value="<?= htmlspecialchars($product['title']) ?>">
                                    <input type="hidden" name="price" value="<?= $product['price'] ?>">

                                    <?php 
                                        // 僅存第一張圖當作加入購物車顯示 (若要多張可自行調整)
                                        $firstImage = !empty($product['images']) ? reset($product['images']) : '';
                                    ?>
                                    <input type="hidden" name="image_blob" value="<?= base64_encode($firstImage) ?>">

                                    <!-- 預設數量: 1 -->
                                    <input type="hidden" name="quantity" value="1">

                                    <button 
                                    type="submit" 
                                    class="product-modal-cart-btn"
                                    style="
                                        border: none; 
                                        background: none; 
                                        color: #fff;
                                        text-align: center; 
                                        display: block; 
                                        width: 100%;
                                    "
                                    >
                                        加入購物車
                                    </button>
                                </form>
                            </div>
                            <!-- 加入購物車表單結束 -->
                            
                            <span class="discount discount-1 p-abs">HOT</span>
                            <div class="product-action product-action-1 p-abs">
                                <a href="#" class="icon-box icon-box-1" 
                                   data-bs-toggle="modal" 
                                   data-bs-target="#productModal-<?= $product['id'] ?>">
                                    <i class="fal fa-eye"></i>
                                    <i class="fal fa-eye"></i>
                                </a>
                            </div>
                        </div>
                        
                        <!-- 文字區 -->
                        <div class="product-content" style="padding:10px; background:#fff;">
                            <h4 class="pro-title pro-title-1">
                                <a href="javascript:void(0)"
                                   data-bs-toggle="modal"
                                   data-bs-target="#productModal-<?= $product['id'] ?>">
                                   <?= htmlspecialchars($product['title']) ?>
                                </a>
                            </h4>
                            <div class="pro-price">
                                <span>NT <?= htmlspecialchars($product['price']) ?></span>
                            </div>
                            <div class="rating">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <i class="fal fa-star <?= $i < round($product['avg_rating']) ? 'active' : '' ?>"></i>
                                <?php endfor; ?>
                                <span>(<?= htmlspecialchars($product['review_count']) ?> 則評論)</span>
                            </div>
                        </div>
                        
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="row">
            <div class="col-xxl-12">
                <div class="btn-area text-center wow fadeInUp" data-wow-delay="1.2s" style="margin-top: 30px;">
                    <div class="p-btn p-btn-1">
                        <a href="shop.php">查 看 所 有 商 品</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- top selling area end -->


 

<?php
include "config.php";
$db_link->query("SET NAMES 'utf8'");

// 從資料庫獲取產品數據
$query = "
    SELECT 
        p.id, 
        p.name AS title, 
        p.description, 
        p.price_new AS price, 
        p.price_old AS original_price, 
        p.availability AS rating, 
        p.category_ids, 
        p.tag_ids,
        i.image1, i.image2, i.image3, i.image4,
        COALESCE(AVG(r.rating), 0) AS avg_rating,
        COUNT(r.id) AS review_count
    FROM products p
    LEFT JOIN product_images i ON p.id = i.product_id
    LEFT JOIN reviews r ON p.id = r.product_id
    GROUP BY p.id
";
$result = $db_link->query($query);

$products = [];
while ($row = $result->fetch_assoc()) {
    $row['images'] = array_filter([
        $row['image1'],
        $row['image2'],
        $row['image3'],
        $row['image4'],
    ]);
    unset($row['image1'], $row['image2'], $row['image3'], $row['image4']);
    
    // 分割 category_ids 和 tag_ids 為陣列 (如有需要)
    $row['categories'] = array_filter(explode(',', $row['category_ids']));
    $row['tags'] = array_filter(explode(',', $row['tag_ids']));
    $products[] = $row;
}
?>

<!-- product modal area start -->
<?php foreach ($products as $product): ?>
<div class="product__modal-area modal fade" id="productModal-<?= $product['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="productModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="product__modal-inner position-relative">
                <div class="product__modal-close">
                    <button data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti-close"></i>
                    </button>
                </div>
                <div class="product__modal-left">
                    <div class="tab-content mb-10" id="productModalThumb-<?= $product['id'] ?>">
                        <?php foreach ($product['images'] as $index => $image): ?>
                            <div class="tab-pane fade <?= $index === 0 ? 'show active' : '' ?>" id="pro-<?= $product['id'] ?>-<?= $index + 1 ?>" role="tabpanel">
                                <div class="product__modal-thumb w-img" style="height: 400px; width: 100%; display: flex; justify-content: center; align-items: center;">
                                    <img src="data:image/jpeg;base64,<?= base64_encode($image) ?>" alt="" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="product__modal-nav">
                        <ul class="nav nav-tabs" id="productModalNav-<?= $product['id'] ?>" role="tablist">
                            <?php foreach ($product['images'] as $index => $image): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?= $index === 0 ? 'active' : '' ?>" id="pro-<?= $product['id'] ?>-<?= $index + 1 ?>-tab" data-bs-toggle="tab" data-bs-target="#pro-<?= $product['id'] ?>-<?= $index + 1 ?>" type="button" role="tab" aria-controls="pro-<?= $product['id'] ?>-<?= $index + 1 ?>" aria-selected="<?= $index === 0 ? 'true' : 'false' ?>">
                                        <img src="data:image/jpeg;base64,<?= base64_encode($image) ?>" alt="" style="width: 80px; height: 80px; object-fit: cover;">
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <div class="product__modal-right" style="padding-left: 20px; margin-left: -0px;">
                    <h3 class="product__modal-title">
                        <br><a href="#"><?= htmlspecialchars($product['title']) ?></a>
                    </h3>
                    <div class="product__modal-rating d-flex align-items-center">
                        <ul class="mr-10">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <li>
                                    <a href="#">
                                        <i class="ti-star <?= $i < round($product['avg_rating']) ? 'active' : '' ?>"></i>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                        <div class="customer-review">
                            <a href="#">(<?= htmlspecialchars($product['review_count']) ?> 則 評論)</a>
                        </div>
                    </div>
                    <div class="product__modal-price mb-10">
                        <span class="price new-price">NT <?= htmlspecialchars($product['price']) ?></span>
                        <!--<del class="price old-price">$<?= htmlspecialchars($product['original_price']) ?></del>-->
                    </div>
                    <div class="product__modal-available">
                        <p> 剩餘庫存: <span><?= htmlspecialchars($product['rating']) ?></span> </p><br>
                    </div>
                    <div class="product__modal-des">
                        <p>產品說明:<?= htmlspecialchars($product['description']) ?></p>
                    </div>

                    <!-- ★★★ 這裡改為真正的「加入購物車」表單 ★★★ -->
                    <div class="product__modal-quantity mb-15">
                        <h5>購買數量:</h5>
                        <form action="index.php" method="POST"> 
                            <!-- 隱藏欄位: 告訴後端要做 add_to_cart -->
                            <input type="hidden" name="action" value="add_to_cart">

                            <!-- 隱藏欄位: 商品ID / 價格 / 名稱 / 圖片 (BLOB) -->
                            <input type="hidden" name="id" value="<?= $product['id'] ?>">
                            <input type="hidden" name="name" value="<?= htmlspecialchars($product['title']) ?>">
                            <input type="hidden" name="price" value="<?= $product['price'] ?>">
                            <?php 
                              // 為了簡化，只存第一張圖 (若要存多張可自行設計)
                              // base64 字串比較大，也可在 cart 中只顯示 product_id 再去DB撈
                              $firstImage = !empty($product['images']) ? reset($product['images']) : '';
                            ?>
                            <input type="hidden" name="image_blob" value="<?= base64_encode($firstImage) ?>">

                            <!-- 數量欄位 -->
                            <div class="pro-quan-area d-sm-flex align-items-center">
                                <div class="product-quantity mr-20 mb-25">
                                    <div class="cart-plus-minus p-relative">
                                        <input type="number" name="quantity" value="1" min="1">
                                    </div>
                                </div>
                                <div class="product__modal-cart mb-25">
                                    <button class="product-modal-cart-btn" type="submit">
                                        加入購物車
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- ★★★ 加入購物車表單結束 ★★★ -->

                    <div class="product__modal-categories d-sm-flex align-items-center">
                        <span>Categories: </span>
                        <ul>
                            <?php foreach ($product['categories'] as $index => $category): ?>
                                <li>
                                    <a href="#"><?= htmlspecialchars($category) ?></a><?= $index !== array_key_last($product['categories']) ? ',' : '' ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="product__modal-categories d-sm-flex align-items-center">
                        <span>Tags: </span>
                        <ul>
                            <?php foreach ($product['tags'] as $index => $tag): ?>
                                <li>
                                    <a href="#"><?= htmlspecialchars($tag) ?></a><?= $index !== array_key_last($product['tags']) ? ',' : '' ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
<!-- product modal area end -->


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