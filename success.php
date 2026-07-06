<?php
require_once __DIR__ . '/functions.php';
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }
$trackCode = 'APP-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ثبت درخواست موفق بود</title>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/main.css">
</head>
<body>
<div class="success-wrap">
  <div class="success-card">
    <div class="check">✓</div>
    <h2>درخواست شما با موفقیت ثبت شد</h2>
    <p>از زمان و دقتی که برای تکمیل این فرم گذاشتید سپاسگزاریم. کارشناسان منابع انسانی پس از بررسی رزومه، در صورت تناسب با فرصت‌های موجود، از طریق شماره تماس یا ایمیل با شما تماس خواهند گرفت.</p>
    <div class="track-id">کد پیگیری: <?= e($trackCode) ?></div>
  </div>
</div>
</body>
</html>
