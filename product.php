<?php 
session_start();
include "config.php";

// 計算購物車中的總商品數量
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
    if ($stmt) {
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

            // 更新購物車計數
            $cartCount = 0;
            foreach ($_SESSION['cart'] as $item) {
                $cartCount += $item['quantity'];
            }

            // 跳轉回產品頁面，並帶上成功訊息
            header("Location: product.php?id=" . $product_id . "&added=1");
            exit;
        } else {
            // 查無此商品，跳轉並帶上錯誤訊息
            header("Location: product.php?id=" . $product_id . "&error=1");
            exit;
        }
    } else {
        // SQL prepare 失敗，跳轉並帶上錯誤訊息
        header("Location: product.php?id=" . $product_id . "&error=1");
        exit;
    }
}

// 取得商品id
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 連線資料庫
$link = mysqli_connect('localhost','root','','shopwebdb');
if (!$link) {
    die("資料庫連線失敗: " . mysqli_connect_error());
}

// 從 product_images 撈出四張圖片
$sqlImg = "SELECT `image1`, `image2`, `image3`, `image4`
           FROM `product_images`
           WHERE `product_id` = {$id} LIMIT 1";
$resultImg = mysqli_query($link, $sqlImg);
$rowImg = mysqli_fetch_assoc($resultImg);

// 組成陣列
$images = [];
if (!empty($rowImg['image1'])) $images[] = $rowImg['image1'];
if (!empty($rowImg['image2'])) $images[] = $rowImg['image2'];
if (!empty($rowImg['image3'])) $images[] = $rowImg['image3'];
if (!empty($rowImg['image4'])) $images[] = $rowImg['image4'];

// 撈 products 資料
$sqlProd = "SELECT `id`, `name`, `description`, `price_new`, `price_old`, 
                   `availability`, `Psales`, `category_ids`, `tag_ids`
            FROM `products`
            WHERE `id` = {$id} LIMIT 1";
$resProd = mysqli_query($link, $sqlProd);
$rowProd = mysqli_fetch_assoc($resProd);

if (!$rowProd) {
    echo "<div style='padding:40px;'>查無此商品</div>";
    exit;
}

// 撈取評論資料
$sqlReviews = "SELECT r.rating, r.review, r.created_at, u.Uname 
               FROM `reviews` r
               LEFT JOIN `user` u ON r.user_id = u.Uid
               WHERE r.product_id = ?
               ORDER BY r.created_at DESC";
$stmtReviews = mysqli_prepare($link, $sqlReviews);
mysqli_stmt_bind_param($stmtReviews, "i", $id);
mysqli_stmt_execute($stmtReviews);
$resultReviews = mysqli_stmt_get_result($stmtReviews);

// 將評論存入陣列
$reviews = [];
if ($resultReviews) {
    while ($row = mysqli_fetch_assoc($resultReviews)) {
        $reviews[] = $row;
    }
}

$review_count = count($reviews);

// 處理反饋訊息並使用 JavaScript 彈出視窗
if (isset($_GET['added']) && $_GET['added'] == 1) {
    echo "<script>alert('成功加入購物車！');</script>";
} elseif (isset($_GET['error']) && $_GET['error'] == 1) {
    echo "<script>alert('加入購物車失敗，請稍後再試。');</script>";
}


mysqli_stmt_close($stmtReviews);
mysqli_close($link);
?>

<!doctype html>
<html class="no-js" lang="zxx">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title><?php echo htmlspecialchars($rowProd['name']); ?> </title>
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
        a.log-bar {
            background: linear-gradient(135deg, #1c1c1c, #4e4e4e); /* 炭黑到深灰 */
            color: #fff; /* 文字白色 */
            border: 2px solid #fff; /* 白色邊框 */
            border-radius: 50px; /* 圓角邊框 */
            padding: 6px 20px; /* 增加按鈕的填充，使按鈕更大 */
            font-family: 'Pathway Gothic One', sans-serif;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            text-align: center;
            display: inline-block;
            position: relative;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
            transition: 0.3s all;
            white-space: nowrap;
        }

        a.log-bar:hover,
        a.log-bar:focus {
            background: linear-gradient(45deg, #4e4e4e, #1c1c1c);
            color: #fff;
            border-color: #ffeb3b;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
            transform: translateY(-3px);
            transition: 0.3s all ease-out;
        }

        .btn-area {
            margin-top: 30px;
            padding-top: 80px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
</head>

<body>

    <!-- preloader start -->
    <div id="loading">
        <div id="loading-center">
            <div id="loading-center-absolute">
                <!-- Preloader SVGs here -->
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
                                            <a href="product-details.php?id=<?php echo $item['id']; ?>">
                                                <img src="data:image/jpeg;base64,<?php echo base64_encode($item['image']); ?>" alt="">
                                            </a>
                                        </div>
                                        <div class="cartmini__content">
                                            <h3 class="cartmini__title">
                                                <a href="product-details.php?id=<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?></a>
                                            </h3>
                                            
                                            <!-- 數量 + 單價區塊 (純前端處理) -->
                                            <div>
                                                <input 
                                                    type="number" 
                                                    name="quantity" 
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
                            <a href="cart.php" class="s-btn w-100 mb-20">View Cart</a>
                            <a href="checkout.php" class="s-btn s-btn-2 w-100">Checkout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- cart mini area end -->


    <!-- search area start -->
    <div class="search__area">
        <div class="search__close">
            <button type="button" class="search__close-btn search-close-btn">
                <i class="fal fa-times"></i>
            </button>
        </div>
        <div class="search__wrapper">
            <h4>Searching</h4>
            <div class="search__form">
                <form action="shop.php" method="GET">
                    <div class="search__input">
                        <input type="text" name="query" placeholder="Search Products" required>
                        <button type="submit">
                            <i class="far fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- search area end -->



    <main>
        <!-- slider area start -->
        <section class="slider-area pb-20 p-rel">
            <div class="slider-active dot-style dot-style-1 dot-right">
                <div class="single-slider slider-height" data-background="assets/img/slider/2.jpg"></div>
                <div class="single-slider slider-height" data-background="assets/img/slider/2.jpg"></div>
                <div class="single-slider slider-height" data-background="assets/img/slider/3.jpg"></div>
                <div class="single-slider slider-height" data-background="assets/img/slider/4.jpg"></div>
            </div>
        </section>
        <!-- slider area end -->

        <!-- breadcrumb area start -->
        <div class="breadcrumb-area-2 box-plr-45 gray-bg-4">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-xxl-12">
                        <nav aria-label="breadcrumb" class="breadcrumb-list-2">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">首頁</a></li>
                                <li class="breadcrumb-item"><a href="shop.php">方哥的商店</a></li>
                                <li class="breadcrumb-item active" aria-current="page">
                                    <?php echo htmlspecialchars($rowProd['name']); ?>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <!-- breadcrumb area end -->

        <!-- product details area start -->
        <section class="product__details-area pb-45 box-plr-45 gray-bg-4">
            <div class='container-fluid'>
                <div class='row'>
                    <!-- 左邊 (圖片) -->
                    <div class='col-xxl-6 col-xl-6 col-lg-6'>
                        <div class='product__details-nav-wrapper d-sm-flex align-items-center'>
                            <div class='product__details-nav mr-120'>
                                <ul class='nav nav-tabs flex-sm-column' id='productDetailsNav' role='tablist'>
                                    <?php
                                    // 使用 $images 生成預覽圖 tabs
                                    if (!empty($images)) {
                                        foreach ($images as $i => $imgData) {
                                            $isActive = ($i === 0) ? 'active' : '';
                                            $tabId    = 'pro-nav-' . ($i+1);
                                            echo "
                                            <li class='nav-item' role='presentation'>
                                                <button class='nav-link {$isActive}'
                                                        id='{$tabId}-tab'
                                                        data-bs-toggle='tab'
                                                        data-bs-target='#{$tabId}'
                                                        type='button'
                                                        role='tab'
                                                        aria-controls='{$tabId}'
                                                        aria-selected='".($i===0?'true':'false')."'>
                                                    <img src='data:image/jpeg;base64,".base64_encode($imgData)."'
                                                         alt='nav-{$i}'
                                                         style='width:80px; height:80px; object-fit:cover;'>
                                                </button>
                                            </li>";
                                        }
                                    } else {
                                        // 如果沒有圖片，顯示預設縮圖
                                        echo "
                                        <li class='nav-item' role='presentation'>
                                            <button class='nav-link active'
                                                    id='pro-nav-1-tab'
                                                    data-bs-toggle='tab'
                                                    data-bs-target='#pro-nav-1'
                                                    type='button'
                                                    role='tab'
                                                    aria-controls='pro-nav-1'
                                                    aria-selected='true'>
                                                <img src='static2/picture/no-image.jpg'
                                                     alt='nav-default'
                                                     style='width:80px; height:80px; object-fit:cover;'>
                                            </button>
                                        </li>";
                                    }
                                    ?>
                                </ul>
                            </div>
                            <div class='product__details-thumb'>
                                <div class='tab-content' id='productDetailsTabContent'>
                                    <?php
                                    // 大圖區
                                    if (!empty($images)) {
                                        foreach ($images as $i => $imgData) {
                                            $isActive = ($i===0) ? 'show active' : '';
                                            $tabId    = 'pro-nav-' . ($i+1);
                                            echo "
                                            <div class='tab-pane fade {$isActive}'
                                                 id='{$tabId}'
                                                 role='tabpanel'
                                                 aria-labelledby='{$tabId}-tab'>
                                                <div class='product-nav-thumb-wrapper'>

                                                    <img src='data:image/jpeg;base64,".base64_encode($imgData)."'
                                                         alt='big-{$i}'
                                                         style='max-width: 100%; max-height: 550px; object-fit:contain;'>
                                                </div>
                                            </div>";
                                        }
                                    } else {
                                        // 如果沒有圖片，顯示預設大圖
                                        echo "
                                        <div class='tab-pane fade show active' id='pro-nav-1'>
                                            <div class='product-nav-thumb-wrapper'>
                                                <img src='static2/picture/no-image.jpg'
                                                     alt='no-img'
                                                     style='max-width:100%; max-height:550px; object-fit:contain;'>
                                            </div>
                                        </div>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 右邊 (資訊) -->
                    <div class='col-xxl-4 col-xl-6 col-lg-6'>
                        <div class='product__details-content pt-60'>
                            <h3 class='product__details-title'>
                                <a href='#'><?php echo htmlspecialchars($rowProd['name']); ?></a>
                            </h3>



                            <div class='product__details-price'>
                                <span class='price'>NT <?php echo htmlspecialchars($rowProd['price_new']); ?></span>
                            </div>
                            
                            <p class='product-des'>
                                <?php echo nl2br(htmlspecialchars($rowProd['description'])); ?>
                            </p>

                            <div class='product__details-action'>
                                <!-- 修正「加入購物車」表單 -->
                                <form action="product.php?id=<?php echo $id; ?>" method="POST">
                                    <div class='product__details-quantity d-sm-flex align-items-center'>
                                        <div class='product-quantity mb-20 mr-15'>
                                            <div class='cart-plus-minus'>
                                                <input type='number' name='quantity' value='1' min='1' class='cart-plus-minus-box'>
                                            </div>
                                        </div>
                                        <div class='product-add-cart mb-20'>
                                            <!-- 隱藏欄位傳遞操作和產品ID -->
                                            <input type="hidden" name="action" value="add_to_cart">
                                            <input type="hidden" name="product_id" value="<?php echo $id; ?>">
                                            <button type="submit" class='s-btn s-btn-2 s-btn-big'>加入購物車</button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <div class='product__details-meta mb-25'>
                                <ul>
                                    <li>
                                        <div class='product-availibility'>
                                            <span>庫存：</span>
                                            <p><span><?php echo htmlspecialchars($rowProd['availability']); ?></span></p>
                                        </div>
                                    </li>
                                    <li>
                                        <div class='product-sku'>
                                            <span>已售出：</span>
                                            <p>
                                                <a href='#'><?php echo htmlspecialchars($rowProd['Psales']); ?></a>
                                            </p>
                                        </div>
                                    </li>
                                    <li>
                                        <div class='product-sku'>
                                            <span>標籤：</span>
                                            <p>
                                                <a href='#'><?php echo htmlspecialchars($rowProd['tag_ids']); ?></a>
                                            </p>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div> <!-- /.col-xxl-4 -->
                </div> <!-- /.row -->
            </div> <!-- /.container-fluid -->
        </section>
        <!-- product details area end -->

    <!-- product info area start -->
    <section class="product__info-area pt-95">
        <div class="container">
            <div class="row">
                <div class="col-xxl-12">
                    <div class="product__info-btn text-center">
                        <ul class="nav d-sm-flex justify-content-center" id="productInfoTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="review-tab" type="button" data-bs-toggle="tab" data-bs-target="#review" aria-controls="review" aria-selected="true">
                                    商品評價 (<?php echo $review_count; ?>)
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xxl-12">
                    <div class="product__info-tab-content pb-75">
                        <!-- Reviews Section -->
                        <div class="tab-content" id="productInfoTabContent">
                            <div class="tab-pane fade show active" id="review" role="tabpanel" aria-labelledby="review-tab">
                                <div class="product__details-review mt-50">
                                    <div class="row">
                                        <div class="product-review-wrapper">
                                            <?php if (!empty($reviews)): ?>
                                                <?php foreach ($reviews as $review): ?>
                                                    <div class="product-review-item">
                                                        <div class="product-review-top d-flex align-items-center justify-content-between">
                                                            <div class="product-review-name d-sm-flex align-items-center">
                                                                <h4 class="mr-10"><?php echo htmlspecialchars($review['Uname'] ?? '匿名'); ?></h4>
                                                                <span class="date"><?php echo htmlspecialchars(date("F d, Y", strtotime($review['created_at']))); ?></span>
                                                            </div>
                                                            <div class="product-review-rating">
                                                                <ul class="rating-stars">
                                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                        <li>
                                                                            <a href="#">
                                                                                <i class="far fa-star <?php echo ($i <= $review['rating']) ? 'active' : ''; ?>"></i>
                                                                            </a>
                                                                        </li>
                                                                    <?php endfor; ?>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                        <p><?php echo nl2br(htmlspecialchars($review['review'])); ?></p>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <p>暫無評論。</p>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Review Submission Form -->
                                        <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6">
                                            <div class="product-review-form pl-60">
                                                <form action="submit_review.php" method="POST">
                                                    <h3 class="product-review-title">
                                                        您正在查看
                                                        <span>“<?php echo htmlspecialchars($rowProd['name']); ?>”</span>
                                                    </h3>

                                                    <div class="product-review-form-rating mb-40">
                                                        <p>您的評價</p>
                                                        <ul class="rating-stars">
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <li>
                                                                    <a href="#" class="rate-star" data-rating="<?php echo $i; ?>">
                                                                        <i class="far fa-star"></i>
                                                                    </a>
                                                                </li>
                                                            <?php endfor; ?>
                                                        </ul>
                                                        <input type="hidden" name="rating" id="rating" value="0" required>
                                                    </div>
                                                    <div class="product-review-form-wrapper">
                                                        <div class="row">
                                                            <div class="col-xxl-12">
                                                                <div class="product-review-input">
                                                                    <label>您的評論 *</label>
                                                                    <textarea name="review" required></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="col-xxl-12">
                                                                <div class="product-review-btn">
                                                                    <button type="submit" class="s-btn s-btn-2 s-btn-big-2">
                                                                        送出評論
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div> <!-- /.row -->
                                                    </div>
                                                    <input type="hidden" name="product_id" value="<?php echo $rowProd['id']; ?>">
                                                </form>
                                            </div>
                                        </div>
                                    </div> <!-- /.row -->
                                </div>
                            </div>
                        </div>
                        <!-- End Reviews Section -->
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- product info area end -->



    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // 星星評分互動
        const rateStars = document.querySelectorAll('.rate-star');
        const ratingInput = document.getElementById('rating');

        rateStars.forEach(function(star) {
            star.addEventListener('click', function(e) {
                e.preventDefault();
                const rating = parseInt(this.getAttribute('data-rating'));
                ratingInput.value = rating;

                // 更新星級顯示
                rateStars.forEach(function(s, index) {
                    const icon = s.querySelector('i');
                    if (index < rating) {
                        icon.classList.add('active');
                    } else {
                        icon.classList.remove('active');
                    }
                });
            });
        });
    });
    </script>

    <style>
    .rating-stars li a i.active {
        color: #FFD700; /* 金色 */
    }
    .rating-stars li a i {
        color: #CCCCCC; /* 灰色 */
        cursor: pointer;
        transition: color 0.2s;
    }
    </style>


        <div class="product-line"></div>
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
