<?php
// qurban_app/includes/header.php

session_start();
ob_start();

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/data.php';
require_once __DIR__ . '/../functions/qr_generator.php';

if (!isUserLoggedIn()) {
    header("Location: ../index.php");
    exit();
}

$active_menu = $active_menu ?? '';
$page_title = $page_title ?? 'Dashboard';
$breadcrumb_path = $breadcrumb_path ?? [];

$user_level = getUserLevel();
$user_username = $_SESSION['username'] ?? 'User';
$user_level_name = $_SESSION['level_nama'] ?? 'Unknown';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Qurban App - <?php echo htmlspecialchars($page_title); ?></title>

    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="../assets/css/animate.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div id="wrapper">