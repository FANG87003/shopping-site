<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>方哥的商店</title>
</head>
<?php
    session_start();
    session_destroy();
    echo "<script>alert('您已登出');location.href='index.php';</script>";
    exit;
?>
