<!doctype html>
<html class="no-js" lang="">

<head>
	<meta charset="utf-8">
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<title>登入</title>
	<meta name="description" content="">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- Bootstrap CSS -->
	<link rel="stylesheet" href="static/css/bootstrap.min.css">
	<!-- Fontawesome CSS -->
	<link rel="stylesheet" href="static/css/fontawesome-all.min.css">
	<!-- Flaticon CSS -->
	<link rel="stylesheet" href="static/css/flaticon.css">
	<!-- Google Web Fonts -->
	<link href="static/css/css.css" rel="stylesheet">
	<!-- Custom CSS -->
	<link rel="stylesheet" href="static/css/style.css">
</head>

<body>
    <div id="preloader" class="preloader">
        <div class='inner'>
            <div class='line1'></div>
            <div class='line2'></div>
            <div class='line3'></div>
        </div>
    </div>
	<section class="fxt-template-animation fxt-template-layout27" data-bg-image="static/img/figure/bg27-l.jpg">
		<!-- Animation Start Here -->
		<div id="particles-js"></div>
		<!-- Animation End Here -->
		<div class="fxt-content">
			<div class="fxt-header">
				<a href="index.php" class="fxt-logo"><img src="static/picture/NCUT1.png" alt="Logo"></a>
				<ul class="fxt-switcher-wrap">
					<li><a href="" class="switcher-text active">登入</a></li>
					<li><a href="register.php" class="switcher-text inline-text">還沒有帳號？ 註冊</a></li>
					<!--<li><a href="forgot-password-27.html" class="switcher-text">Forgot Password</a></li>-->
				</ul>
			</div>
			<div class="fxt-form">
				<div class="fxt-transformY-50 fxt-transition-delay-1">
					<p>登入您的帳號</p>
				</div>
				<form method="POST">
					<div class="form-group">
                        <div class="fxt-transformY-50 fxt-transition-delay-2">
                            <input type="text" id="id" class="form-control" name="id" placeholder="帳號 / Email / 手機號碼">
                        </div>
                    </div>
					<div class="form-group">
						<div class="fxt-transformY-50 fxt-transition-delay-3">
							<input id="password" type="password" class="form-control" name="pwd" placeholder="********">
							<i toggle="#password" class="fa fa-fw fa-eye toggle-password field-icon"></i>
						</div>
					</div>


					<!-- 驗證碼區域 -->
					<div class="form-group" style="display: flex; align-items: center;">
						<!-- 輸入框 -->
						<input type="text" name="captcha" class="form-control" placeholder="請輸入驗證碼" style="flex: 1; margin-right: 10px;">
						
						<!-- 驗證碼圖片 -->
						<img src="captcha.php" alt="驗證碼圖片" id="captcha_image" style="cursor: pointer; height: 50px;" onclick="this.src='captcha.php?'+Math.random();">
					</div>


					<div class="form-group">
						<div class="fxt-transformY-50 fxt-transition-delay-4">
							<button type="submit" class="fxt-btn-fill">登入</button>
						</div>
					</div>

				</form>
			</div>

		</div>
	</section>
	<!-- jquery-->
	<script src="static/js/jquery-3.5.0.min.js"></script>
	<!-- Bootstrap js -->
	<script src="static/js/bootstrap.min.js"></script>
	<!-- Imagesloaded js -->
	<script src="static/js/imagesloaded.pkgd.min.js"></script>
	<!-- Particles js -->
	<script src="static/js/particles.min.js"></script>
	<script src="static/js/particles-2.js"></script>
	<!-- Validator js -->
	<script src="static/js/validator.min.js"></script>
	<!-- Custom Js -->
	<script src="static/js/main.js"></script>


<?php
	include "config.php";
    $db_link->query("SET NAMES 'utf8'");
	session_start();

	if ($_SERVER["REQUEST_METHOD"] == "POST") 
	{
		if ($_POST["id"] == "" || $_POST["id"] == null)
		{
			echo "<script>alert('請輸入帳號');</script>;";
		}
		else if ($_POST["pwd"] == "" || $_POST["pwd"] == null)
		{
			echo "<script>alert('請輸入密碼');</script>;";
		}
		else if ($_POST["captcha"] == "" || $_POST["captcha"] == null)
		{
			echo "<script>alert('請輸入驗證碼');</script>;";
		}
		else
		{
			//驗證碼驗證
			$captcha = $_POST["captcha"];
			if ($captcha != $_SESSION["captcha"])
			{
				echo "<script>alert('驗證碼錯誤');</script>;";
			}
			else
			{
				//檢查有無此帳號
				$checkIdQuery = "SELECT `Uid`, `Uemail`, `Uphone` FROM `user` WHERE `Uid` = '" . $_POST["id"] . "' OR `Uemail` = '" . $_POST["id"] . "' OR `Uphone` = '" . $_POST["id"] . "'";
				$resultId = $db_link->query($checkIdQuery);
				if (!$resultId->fetch_assoc())
				{
					echo "<script>alert('查無此帳號');</script>";
				}
				else
				{
					//檢查密碼
					$Id = $_POST["id"];
					$checkPasswordQuery = "SELECT * FROM `user` WHERE `Upwd` = '" . $_POST["pwd"] . "' AND (`Uid` = '$Id' OR `Uemail` = '$Id' OR `Uphone` = '$Id')";
					$resultPwd = $db_link->query($checkPasswordQuery);
					if($resultPwd->num_rows == 0)
					{
						echo "<script>alert('密碼錯誤');</script>";
					}
					else
					{
						//登入成功，檢查權限
						$checkAccessQuery = "SELECT `Uaccess`,`Uid`,`Uname` FROM `user` WHERE `Uid` = '$Id' OR `Uemail` = '$Id' OR `Uphone` = '$Id'";

   						$resultAccess = $db_link->query($checkAccessQuery);
						$row = $resultAccess->fetch_assoc();
						$_SESSION['user_id'] = $row['Uid'];
						$_SESSION['user_name'] = $row['Uname'];
						$_SESSION['user_access'] = $row['Uaccess'];
						$_SESSION['logged'] = true;

						if ($_SESSION["user_access"] == 0) 
						{
							// 一般使用者
							//echo "<script>alert('使用者登入成功\\n將為您導向購物網站');location.href='index.php';</script>";
							echo <<<EOT
								<script>
								let countdown = 5; // 倒數秒數
								const message = document.createElement('div');
								message.style.position = 'fixed';
								message.style.top = '50%';
								message.style.left = '50%';
								message.style.transform = 'translate(-50%, -50%)';
								message.style.padding = '30px';
								message.style.backgroundColor = 'rgba(255, 255, 255, 0.9)';
								message.style.borderRadius = '15px';
								message.style.textAlign = 'center';
								message.style.boxShadow = '0 4px 15px rgba(0, 0, 0, 0.3)';
								message.style.zIndex = '9999'; // 確保訊息框在最前面
								message.style.maxWidth = '500px';
								message.style.width = '90%';
								message.innerHTML = `
									<h2 style="font-size: 24px; color: #333; margin-bottom: 20px;">使用者登入成功</h2>
									<p style="font-size: 16px; color: #555;">將於 <span id="timer" style="font-weight: bold; color: #e9b102;">\${countdown}</span> 秒後將為您導向購物網站</p>
									<div style="margin-top: 20px;">
										<button id="redirectBtn" style="padding: 10px 20px; background-color: #e9b102; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; transition: background-color 0.3s;">
											立即跳轉
										</button>
									</div>`;

								document.body.appendChild(message);

								const timer = document.getElementById('timer');
								const redirectBtn = document.getElementById('redirectBtn');

								const interval = setInterval(() => {
									countdown--;
									timer.textContent = countdown;
									if (countdown <= 0) {
										clearInterval(interval);
										location.href = 'index.php'; // 頁面跳轉
									}
								}, 1000); // 每秒執行一次

								// 當「立即跳轉」按鈕被點擊時，立即跳轉
								redirectBtn.addEventListener('click', () => {
									location.href = 'index.php'; // 頁面跳轉
								});
								</script>
								EOT;

						} 
						else 
						{
							// 管理員
							echo "<script>alert('管理員登入成功');location.href='index.php';</script>";
						}
					}
						
				}
			}
		}
	}
?>

</body>

</html>