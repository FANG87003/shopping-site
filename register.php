<!doctype html>
<html class="no-js" lang="">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>註冊</title>
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

    <style>
    </style>
</head>

<body>
    <div id="preloader" class="preloader">
        <div class='inner'>
            <div class='line1'></div>
            <div class='line2'></div>
            <div class='line3'></div>
        </div>
    </div>
    <section class="fxt-template-animation fxt-template-layout27" data-bg-image="img/figure/bg27-l.jpg">
        <!-- Animation Start Here -->
        <div id="particles-js"></div>
        <!-- Animation End Here -->
        <div class="fxt-content">
            <div class="fxt-header">
                <a href="index.php" class="fxt-logo"><img src="static/picture/NCUT1.png" alt="Logo"></a>
                <ul class="fxt-switcher-wrap">
                    <li><a href="login.php" class="switcher-text">已有帳號？ 登入</a></li>
                    <li><a href="" class="switcher-text active">註冊</a></li>
                    <!--<li><a href="forgot-password-27.html" class="switcher-text">Forgot Password</a></li>-->
                </ul>
            </div>
            <div class="fxt-form">
                <div class="fxt-transformY-50 fxt-transition-delay-1">
                    <p>註冊您的帳號</p>
                </div>
                <form method="POST">

                    <div class="form-group">
                        <div class="fxt-transformY-50 fxt-transition-delay-2">
                            帳號：<input type="text" id="id" class="form-control" name="id" placeholder="請輸入帳號">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="fxt-transformY-50 fxt-transition-delay-3">
                            信箱：<input type="text" class="form-control" name="email" placeholder="請輸入信箱" title="信箱格式不正確">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="fxt-transformY-50 fxt-transition-delay-2">
                            手機：<input type="text" id="phone" class="form-control" name="phone" placeholder="請輸入手機號碼"  title="手機格式不正確">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="fxt-transformY-50 fxt-transition-delay-2">
                            姓名：<input type="text" id="name" class="form-control" name="name" placeholder="請輸入姓名">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="fxt-transformY-50 fxt-transition-delay-2">
                            <div class="radio-container">
                                性別：<input type="radio" id="man" name="sex" value="1" class="custom-radio">
                                <label for="man" class="radio-label">男性</label>

                                <input type="radio" id="woman" name="sex" value="2" class="custom-radio">
                                <label for="woman" class="radio-label">女性</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group birthday-group">
                        <label for="year">生日：</label>
                        <select aria-label="年" name="birth_year" id="year" title="年" class="_9407 _5dba _9hk6 _8esg">
                            <?php
                                // 取得目前年
                                $currentYear = date("Y");

                                // 從目前年開始遞減
                                for ($year = $currentYear; $year >= 1911; $year--) 
                                {
                                    // 如果年份等於今年，則設為 selected
                                    echo '<option value="'.$year.'"';
                                    if ($year == $currentYear) 
                                    {
                                        echo 'selected="selected"';
                                    }
                                    echo '>'.$year.'年</option>';
                                }
                            ?>
                        </select>

                        <select aria-label="月" name="birth_month" id="month" title="月" class="_9407 _5dba _9hk6 _8esg">
                            <?php
                                // 取得目前月
                                $currentMonth = date("m");
                                
                                for ($month = 1; $month <= 12; $month++) 
                                {
                                    echo '<option value="'.$month.'"';
                                    //  如果月份等於今月，則設為 selected
                                    if ($month == $currentMonth) 
                                    {
                                        echo 'selected="selected"';
                                    }
                                    echo '>'.$month .'月</option>';
                                }
                            ?>
                        </select>

                        <select aria-label="日" name="birth_day" id="day" title="日" class="_9407 _5dba _9hk6 _8esg">
                            <?php
                                // 取得當天日期
                                $currentDay = date("d");

                                for ($day = 1; $day <= 31; $day++) 
                                {
                                    echo '<option value="'.$day.'"';
                                    //  如果日期等於今日，則設為 selected
                                    if ($day == $currentDay) 
                                    {
                                        echo 'selected="selected"';
                                    }
                                    echo '>'.$day.'日</option>';
                                }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <div class="fxt-transformY-50 fxt-transition-delay-4">
                            <input id="password" type="password" class="form-control" name="pwd" placeholder="請輸入密碼">
                            <i toggle="#password" class="fa fa-fw fa-eye toggle-password field-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="fxt-transformY-50 fxt-transition-delay-4">
                            <input id="password" type="password" class="form-control" name="chkpwd" placeholder="再次輸入密碼以確認">
                            <i toggle="#password" class="fa fa-fw fa-eye toggle-password field-icon"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="fxt-transformY-50 fxt-transition-delay-5">
                            <button type="submit" class="fxt-btn-fill">註冊</button>
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
    
    if ($_SERVER["REQUEST_METHOD"] == "POST")
    {
        if ($_POST["id"] == "" || $_POST["id"] == null)
        {
            echo "<script>alert('請輸入帳號');</script>";
        }
        else if ($_POST["email"] == "" || $_POST["email"] == null)
        {
            echo "<script>alert('請輸入信箱');</script>";
        }
        else if ($_POST["phone"] == "" || $_POST["phone"] == null)
        {
            echo "<script>alert('請輸入手機號碼');</script>";
        }
        else if ($_POST["name"] == "" || $_POST["name"] == null)
        {
            echo "<script>alert('請輸入姓名');</script>";
        }
        else if (!isset($_POST["sex"]) || empty($_POST["sex"])) 
        {
            echo "<script>alert('請選擇性別');</script>";
        }
        else if ($_POST["pwd"] == "" || $_POST["pwd"] == null)
        {
            echo "<script>alert('請輸入密碼');</script>";
        }
        else if ($_POST["chkpwd"] == "" || $_POST["chkpwd"] == null)
        {
            echo "<script>alert('請再次確認密碼');</script>";
        }
        else
        {
            
            if ($_POST["pwd"] !== $_POST["chkpwd"])
            {
                echo "<script>alert('密碼與確認密碼不一致');</script>";
            }
            else if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL))
            {
                echo "<script>alert('信箱格式錯誤');</script>";
            }
            else if (!preg_match("/^\d{10}$/", $_POST["phone"]))
            {
                echo "<script>alert('請輸入有效手機號碼');</script>";
            }
            else
            {
                // 檢查帳號是否已被使用
                $checkIdQuery = "SELECT * FROM `user` WHERE `Uid` = '" . $_POST["id"] . "'";
                $resultId = $db_link->query($checkIdQuery);
                if ($resultId->num_rows > 0) 
                {
                    echo "<script>alert('該帳號已被使用');</script>";
                } 
                else 
                {
                    // 檢查信箱是否已被使用
                    $checkEmailQuery = "SELECT * FROM user WHERE Uemail = '" . $_POST["email"] . "'";
                    $resultEmail = $db_link->query($checkEmailQuery);
                    if ($resultEmail->num_rows > 0) 
                    {
                        echo "<script>alert('該信箱已被使用');</script>";
                    } 
                    else 
                    {
                        // 檢查手機號碼是否已被使用
                        $checkPhoneQuery = "SELECT * FROM user WHERE Uphone = '" . $_POST["phone"] . "'";
                        $resultPhone = $db_link->query($checkPhoneQuery);
                        if ($resultPhone->num_rows > 0) 
                        {
                            echo "<script>alert('該手機號碼已被使用');</script>";
                        } 
                        else 
                        {
                            //如以上都沒錯誤，執行資料庫新增
                            $id = $_POST["id"];
                            $email = $_POST["email"];
                            $phone = $_POST["phone"];
                            $name = $_POST["name"];
                            $sex = $_POST["sex"];
                            $birth = sprintf("%04d-%02d-%02d", $_POST["birth_year"], $_POST["birth_month"], $_POST["birth_day"]);
                            $pwd = $_POST["pwd"];
                            
                            //新增
                            $insertQuery = "INSERT INTO `user` (`Uid`, `Uemail`, `Uphone`, `Uname`, `Usex`, `Ubirth`, `Upwd`) 
                                            VALUES ('$id', '$email', '$phone', '$name', '$sex', '$birth','$pwd')";
                            
                            $result = $db_link->query($insertQuery);
                            
                            if ($result > 0 ) 
                            {
                                echo "<script>alert('註冊成功!\\n將為您導向登入頁面');location.href ='login.php';</script>";
                            } 
                            else 
                            {}
                        }
                    }
                }
            }
        }
    }
?>



    
</body>

</html>