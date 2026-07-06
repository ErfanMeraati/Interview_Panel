<?php
require_once __DIR__ . '/../functions.php';

if (is_admin_logged_in()) { header('Location: dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if (ADMIN_PASSWORD_HASH === 'PASTE_YOUR_GENERATED_HASH_HERE') {
        $error = 'ابتدا باید رمز عبور ادمین را در فایل config.php تنظیم کنید (راهنما در README.txt).';
    } elseif (password_verify($password, ADMIN_PASSWORD_HASH)) {
        $_SESSION['is_admin'] = true;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'رمز عبور نادرست است.';
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ورود ادمین</title>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700;800&family=Amiri:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/main.css">
</head>
<body>
<div class="gate-wrap">
  <div class="gate-card">
    <div class="seal" style="margin:0 auto;"><span>ا</span></div>
    <h2>پنل مدیریت استخدام</h2>
    <p>برای مشاهده درخواست‌ها، رمز عبور را وارد کنید.</p>
    <form method="post">
      <div class="field" style="text-align:right;">
        <input type="password" name="password" placeholder="رمز عبور" autofocus>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;">ورود</button>
    </form>
    <div class="gate-err"><?= e($error) ?></div>
  </div>
</div>
</body>
</html>
