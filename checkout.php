<?php
session_start();
include "config.php";

// 檢查是否已登入
if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
    echo "<script>alert('請先登入');location.href='index.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// 取得購物車內容
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

// 新增購物車為空時的邏輯
if (empty($cart_items)) {
    echo "<script>alert('您的購物車目前沒有商品。');location.href='index.php';</script>";
    exit();
}

// 計算總金額
$total_price = 0;
foreach ($cart_items as $item) {
    $total_price += $item['price'] * $item['quantity'];
}

// 取得使用者的預設地址（假設有欄位存儲）
$sql = "SELECT  `Ubirth`, `Uemail` FROM `user` WHERE `Uid` = ?";
$stmt = $db_link->prepare($sql);
if ($stmt) {
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $stmt->bind_result($ubirth, $uemail);
    $stmt->fetch();
    $stmt->close();
} else {
    $default_address = '';
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>結帳</title>
    <style>
        /* 基本樣式，根據您的需求進行調整 */
        body {
            font-family: 'Microsoft JhengHei', sans-serif;
            background-color: #f9f9f9;
            padding: 20px;
        }
        .checkout-container {
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
        }
        .cart-summary, .order-details {
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }
        .total {
            text-align: right;
            font-size: 1.2em;
            margin-top: 10px;
        }
        .order-details form {
            display: flex;
            flex-direction: column;
        }
        .order-details label {
            margin-bottom: 5px;
            font-weight: bold;
        }
        .order-details input, .order-details textarea, .order-details select {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .order-details button {
            padding: 12px;
            background-color: #1976D2;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
        }
        .order-details button:hover {
            background-color: #0D47A1;
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <h2>結帳</h2>

        <!-- 購物車摘要 -->
        <div class="cart-summary">
            <h3>購物車內容</h3>
            <?php if (!empty($cart_items)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>商品</th>
                            <th>名稱</th>
                            <th>單價</th>
                            <th>數量</th>
                            <th>小計</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td>
                                    <?php if(!empty($item['image'])): ?>
                                        <img src="data:image/jpeg;base64,<?= base64_encode($item['image']); ?>" alt="商品圖" style="width:50px; height:50px; object-fit:cover;">
                                    <?php else: ?>
                                        <img src="static2/picture/no-image.jpg" alt="商品圖" style="width:50px; height:50px; object-fit:cover;">
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($item['name']); ?></td>
                                <td>$<?= number_format($item['price'], 2); ?></td>
                                <td><?= $item['quantity']; ?></td>
                                <td>$<?= number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="total">
                    <strong>總金額: $<?= number_format($total_price, 2); ?></strong>
                </div>
            <?php else: ?>
                <p>您的購物車目前沒有商品。</p>
            <?php endif; ?>
        </div>

        <!-- 訂單詳情 -->
        <div class="order-details">
            <h3>訂單詳情</h3><br>
            <form method="POST" action="process_checkout.php">
                <label for="shipping_city">縣市:</label>
                <select id="shipping_city" name="shipping_city" required>
                    <option value="">請選擇縣市</option>
                    <option value="Taipei">台北市</option>
                    <option value="New Taipei">新北市</option>
                    <option value="Taichung">台中市</option>
                    <option value="Kaohsiung">高雄市</option>
                    <option value="Tainan">台南市</option>
                    <option value="Taoyuan">桃園市</option>
                    <option value="Hsinchu">新竹市</option>
                    <option value="Hsinchu County">新竹縣</option>
                    <option value="Miaoli">苗栗縣</option>
                    <option value="Changhua">彰化縣</option>
                    <option value="Nantou">南投縣</option>
                    <option value="Yunlin">雲林縣</option>
                    <option value="Chiayi">嘉義市</option>
                    <option value="Chiayi County">嘉義縣</option>
                    <option value="Pingtung">屏東縣</option>
                    <option value="Yilan">宜蘭縣</option>
                    <option value="Hualien">花蓮縣</option>
                    <option value="Taitung">台東縣</option>
                    <option value="Penghu">澎湖縣</option>
                    <option value="Kinmen">金門縣</option>
                    <option value="Matsu">連江縣</option>
                </select>


                <label for="shipping_township">鄉鎮市區:</label>
                <select id="shipping_township" name="shipping_township" required>
                    <option value="">請先選擇縣市</option>
                    <!-- 依據縣市動態填入鄉鎮 -->
                </select>

                <label for="shipping_detail">詳細地址:</label>
                <input type="text" id="shipping_detail" name="shipping_detail" placeholder="請輸入街道、門牌號碼" required>

                <label for="payment_method">支付方式:</label>
                <select id="payment_method" name="payment_method" required>
                    <option value="">請選擇支付方式</option>
                    <option value="Cash on Delivery" selected>貨到付款</option>
                </select>

                <button type="submit">提交訂單</button>
            </form>
        </div>
        <script>
            // 動態更新鄉鎮市區選項
            const cityTownshipMap = {
            Taipei: ["中正區", "大同區", "中山區", "松山區", "大安區", "萬華區", "信義區", "士林區", "北投區", "內湖區", "南港區", "文山區"],
            "New Taipei": ["板橋區", "三重區", "中和區", "永和區", "新莊區", "新店區", "樹林區", "鶯歌區", "三峽區", "淡水區", "汐止區", "瑞芳區", "土城區", "蘆洲區", "五股區", "泰山區", "林口區", "深坑區", "石碇區", "坪林區", "三芝區", "石門區", "八里區", "平溪區", "雙溪區", "貢寮區", "金山區", "萬里區", "烏來區"],
            Taichung: ["中區", "東區", "南區", "西區", "北區", "北屯區", "西屯區", "南屯區", "太平區", "大里區", "霧峰區", "烏日區", "豐原區", "后里區", "石岡區", "東勢區", "和平區", "新社區", "潭子區", "大雅區", "神岡區", "大肚區", "沙鹿區", "龍井區", "梧棲區", "清水區", "大甲區", "外埔區", "大安區"],
            Kaohsiung: ["新興區", "前金區", "苓雅區", "鹽埕區", "鼓山區", "旗津區", "前鎮區", "三民區", "楠梓區", "小港區", "左營區", "仁武區", "大社區", "岡山區", "路竹區", "阿蓮區", "田寮區", "燕巢區", "橋頭區", "梓官區", "彌陀區", "永安區", "湖內區", "鳳山區", "大寮區", "林園區", "鳥松區", "大樹區", "旗山區", "美濃區", "六龜區", "內門區", "杉林區", "甲仙區", "桃源區", "那瑪夏區", "茂林區", "茄萣區"],
            Tainan: ["中西區", "東區", "南區", "北區", "安平區", "安南區", "永康區", "歸仁區", "新化區", "左鎮區", "玉井區", "楠西區", "南化區", "仁德區", "關廟區", "龍崎區", "官田區", "麻豆區", "佳里區", "西港區", "七股區", "將軍區", "學甲區", "北門區", "新營區", "後壁區", "白河區", "東山區", "六甲區", "下營區", "柳營區", "鹽水區", "善化區", "大內區", "山上區", "新市區", "安定區"],
            Taoyuan: ["桃園區", "中壢區", "平鎮區", "八德區", "楊梅區", "蘆竹區", "大溪區", "龍潭區", "龜山區", "大園區", "觀音區", "新屋區", "復興區"],
            Hsinchu: ["東區", "北區", "香山區"],
            "Hsinchu County": ["竹北市", "竹東鎮", "新埔鎮", "關西鎮", "湖口鄉", "新豐鄉", "芎林鄉", "橫山鄉", "北埔鄉", "寶山鄉", "峨眉鄉", "尖石鄉", "五峰鄉"],
            Miaoli: ["苗栗市", "苑裡鎮", "通霄鎮", "竹南鎮", "頭份市", "後龍鎮", "卓蘭鎮", "大湖鄉", "公館鄉", "銅鑼鄉", "南庄鄉", "頭屋鄉", "三義鄉", "西湖鄉", "造橋鄉", "三灣鄉", "獅潭鄉", "泰安鄉"],
            Changhua: ["彰化市", "鹿港鎮", "和美鎮", "線西鄉", "伸港鄉", "福興鄉", "秀水鄉", "花壇鄉", "芬園鄉", "員林市", "溪湖鎮", "田中鎮", "大村鄉", "埔鹽鄉", "埔心鄉", "永靖鄉", "社頭鄉", "二水鄉", "北斗鎮", "二林鎮", "田尾鄉", "埤頭鄉", "芳苑鄉", "大城鄉", "竹塘鄉"],
            Nantou: ["南投市", "埔里鎮", "草屯鎮", "竹山鎮", "集集鎮", "名間鄉", "鹿谷鄉", "中寮鄉", "魚池鄉", "國姓鄉", "水里鄉", "信義鄉", "仁愛鄉"],
            Yunlin: ["斗六市", "斗南鎮", "虎尾鎮", "西螺鎮", "土庫鎮", "北港鎮", "莿桐鄉", "林內鄉", "古坑鄉", "大埤鄉", "崙背鄉", "麥寮鄉", "東勢鄉", "褒忠鄉", "臺西鄉", "元長鄉", "四湖鄉", "口湖鄉", "水林鄉"],
            Chiayi: ["東區", "西區"],
            "Chiayi County": ["太保市", "朴子市", "布袋鎮", "大林鎮", "民雄鄉", "溪口鄉", "新港鄉", "六腳鄉", "東石鄉", "義竹鄉", "鹿草鄉", "水上鄉", "中埔鄉", "竹崎鄉", "梅山鄉", "番路鄉", "大埔鄉", "阿里山鄉"],
            Pingtung: ["屏東市", "潮州鎮", "東港鎮", "恆春鎮", "萬丹鄉", "長治鄉", "麟洛鄉", "九如鄉", "里港鄉", "鹽埔鄉", "高樹鄉", "萬巒鄉", "內埔鄉", "竹田鄉", "新埤鄉", "枋寮鄉", "新園鄉", "崁頂鄉", "林邊鄉", "南州鄉", "佳冬鄉", "琉球鄉", "車城鄉", "滿州鄉", "枋山鄉", "三地門鄉", "霧臺鄉", "瑪家鄉", "泰武鄉", "來義鄉", "春日鄉", "獅子鄉", "牡丹鄉"],
            Yilan: ["宜蘭市", "羅東鎮", "蘇澳鎮", "頭城鎮", "礁溪鄉", "壯圍鄉", "員山鄉", "冬山鄉", "五結鄉", "三星鄉", "大同鄉", "南澳鄉"],
            Hualien: ["花蓮市", "鳳林鎮", "玉里鎮", "新城鄉", "吉安鄉", "壽豐鄉", "光復鄉", "豐濱鄉", "瑞穗鄉", "富里鄉", "秀林鄉", "萬榮鄉", "卓溪鄉"],
            Taitung: ["臺東市", "成功鎮", "關山鎮", "卑南鄉", "鹿野鄉", "池上鄉", "東河鄉", "長濱鄉", "太麻里鄉", "金峰鄉", "大武鄉", "達仁鄉", "綠島鄉", "蘭嶼鄉", "延平鄉", "海端鄉"],
            Penghu: ["馬公市", "湖西鄉", "白沙鄉", "西嶼鄉", "望安鄉", "七美鄉"],
            Kinmen: ["金城鎮", "金湖鎮", "金沙鎮", "金寧鄉", "烈嶼鄉", "烏坵鄉"],
            Matsu: ["南竿鄉", "北竿鄉", "莒光鄉", "東引鄉"]
        };


            const citySelect = document.getElementById("shipping_city");
            const townshipSelect = document.getElementById("shipping_township");

            citySelect.addEventListener("change", () => {
                const selectedCity = citySelect.value;
                townshipSelect.innerHTML = '<option value="">請選擇鄉鎮市區</option>';

                if (cityTownshipMap[selectedCity]) {
                    cityTownshipMap[selectedCity].forEach(township => {
                        const option = document.createElement("option");
                        option.value = township;
                        option.textContent = township;
                        townshipSelect.appendChild(option);
                    });
                }
            });
        </script>
    </div>
</body>
</html>
