<?php
    $db_link = @mysqli_connect("127.0.0.1","root","","ShopWebDb");

    

    if (!$db_link)
    {
        die("失敗");
    }
    /*else
    {
        echo("成功");
    }*/
?>