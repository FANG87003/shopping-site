<?php
session_start();

// 清除輸出緩衝區
ob_clean();

// 產生隨機驗證碼
$captcha_code = '';
$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
for ($i = 0; $i < 4; $i++) {
    $captcha_code .= $characters[rand(0, strlen($characters) - 1)];
}

// 存到 SESSION 中
$_SESSION["captcha"] = $captcha_code;

// 設置圖片的 Content-Type
header('Content-Type: image/png');

// 創建圖片
$width = 120;
$height = 45;
$im = imagecreate($width, $height);

// 設置顏色
$background_color = imagecolorallocate($im, 255, 255, 255); // 背景顏色為白色
$line_color = imagecolorallocate($im, 200, 200, 200); // 淡灰色線條
$dot_color = imagecolorallocate($im, 100, 100, 100);  // 深灰色雜點

// 繪製背景干擾線條
for ($i = 0; $i < 10; $i++) {
    imageline($im, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $line_color);
}

// 添加隨機雜點
for ($i = 0; $i < 200; $i++) {
    imagesetpixel($im, rand(0, $width), rand(0, $height), $dot_color);
}

// 使用 TrueType 字體
$font_path = __DIR__ . '/static/font/Arial.ttf'; // 確保有這個字體檔案
$font_size = 20; // 字體大小

// 繪製驗證碼
for ($i = 0; $i < strlen($captcha_code); $i++) {
    $text_color = imagecolorallocate($im, rand(0, 150), rand(0, 150), rand(0, 150)); // 隨機文字顏色
    $x = 10 + $i * 25; // 文字間距
    $y = rand(30, 35); // 隨機垂直位置
    imagettftext($im, $font_size, rand(-30, 30), $x, $y, $text_color, $font_path, $captcha_code[$i]);
}

// 輸出圖片
imagepng($im);
imagedestroy($im);
?>
