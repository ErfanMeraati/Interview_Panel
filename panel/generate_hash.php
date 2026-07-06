<?php

$hash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['password'])) {
    $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<title>ساخت هش رمز عبور</title>
<style>
  body{font-family:sans-serif;max-width:600px;margin:60px auto;padding:0 20px;background:#faf9f7;}
  input{width:100%;padding:12px;font-size:15px;margin-bottom:10px;box-sizing:border-box;}
  button{padding:12px 24px;font-size:15px;cursor:pointer;}
  .result{background:#eef0fa;padding:16px;border-radius:8px;margin-top:20px;word-break:break-all;font-family:monospace;}
  .warn{color:#c40442;font-weight:bold;margin-top:20px;}
</style>
</head>
<body>
  <h2>ساخت هش رمز عبور ادمین</h2>
  <form method="post">
    <input type="text" name="password" placeholder="رمز عبور دلخواه را وارد کنید" required>
    <button type="submit">ساخت هش</button>
  </form>
  <?php if ($hash): ?>
    <div class="result"><?= htmlspecialchars($hash) ?></div>
    <p>این مقدار را کپی کرده و در فایل <code>config.php</code> جایگزین <code>PASTE_YOUR_GENERATED_HASH_HERE</code> کنید.</p>
  <?php endif; ?>
  <p class="warn">⚠️ پس از استفاده، این فایل (generate_hash.php) را حتماً از روی هاست حذف کنید.</p>
</body>
</html>
