<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/jalali.php';
require_once __DIR__ . '/db.php';

$POSITIONS = [
    "توسعه‌دهنده نرم‌افزار","طراح UI/UX","کارشناس فروش","کارشناس بازاریابی",
    "مدیر محصول","کارشناس منابع انسانی","حسابداری و مالی","پشتیبانی مشتریان","سایر"
];
$JMONTHS = jalali_month_names();

$errors = [];
$old = $_POST; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullName  = trim($_POST['fullname'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $nationalId= trim($_POST['national_id'] ?? '');
    $city      = trim($_POST['city'] ?? '');
    $bday      = $_POST['bday'] ?? '';
    $bmonth    = $_POST['bmonth'] ?? '';
    $byear     = $_POST['byear'] ?? '';
    $position  = $_POST['position'] ?? '';
    $experience= $_POST['experience'] ?? '';
    $message   = trim($_POST['message'] ?? '');

    if (mb_strlen($fullName) < 3) $errors['fullname'] = 'لطفاً نام کامل خود را وارد کنید.';
    if (!preg_match('/^0\d{10}$/', str_replace(' ', '', $phone))) $errors['phone'] = 'شماره تماس معتبر وارد کنید.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'ایمیل معتبر نیست.';
    };
    if (!in_array($position, $POSITIONS, true)) $errors['position'] = 'یک موقعیت شغلی انتخاب کنید.';
    if ($nationalId === '') {
        $errors['national_id'] = 'کد ملی الزامی است.';
    }

    $photoName = null; $resumeName = null;
    $photoErr = ''; $resumeErr = '';

    if (empty($errors)) {
        $photoName = handle_upload('photo', __DIR__ . '/uploads/photos', ['jpg','jpeg','png','webp'], MAX_PHOTO_MB * 1024 * 1024, $photoErr);
        if (!$photoName) $errors['photo'] = $photoErr ?: 'آپلود تصویر با خطا مواجه شد.';

    }

    if (empty($errors)) {
        $birthJalali = '';
        if ($bday && $bmonth && $byear) {
            $birthJalali = jalali_persian_digits($bday) . ' ' . $JMONTHS[$bmonth - 1] . ' ' . jalali_persian_digits($byear);
        }
        $submittedAtJalali = jalali_today_string();
        $originalResumeName = $_FILES['resume']['name'] ?? '';

        $stmt = getDb()->prepare("INSERT INTO applications
            (full_name, phone, email, national_id, city, birth_jalali, position, experience, message, photo_path, resume_path, resume_original_name, submitted_at_jalali)
            VALUES (:full_name,:phone,:email,:national_id,:city,:birth_jalali,:position,:experience,:message,:photo_path,:resume_path,:resume_original_name,:submitted_at_jalali)");
        $stmt->execute([
            ':full_name' => $fullName, ':phone' => $phone, ':email' => $email,
            ':national_id' => $nationalId, ':city' => $city, ':birth_jalali' => $birthJalali,
            ':position' => $position, ':experience' => $experience, ':message' => $message,
            ':photo_path' => $photoName, ':resume_path' => $resumeName,
            ':resume_original_name' => $originalResumeName, ':submitted_at_jalali' => $submittedAtJalali,
        ]);
        $newId = getDb()->lastInsertId();

        header('Location: success.php?id=' . $newId);
        exit;
    }
}

$currentJY = gregorian_to_jalali((int)date('Y'), (int)date('n'), (int)date('j'))[0];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>فرم درخواست استخدام | <?= e(COMPANY_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/main.css">
</head>
<body>
<div class="hero">
  <div class="container hero-inner">
    <div class="seal"><span>ا</span></div>
    <div class="hero-texts">
      <span class="hero-eyebrow">فرصت شغلی</span>
      <h1>فرم درخواست استخدام</h1>
      <p>اطلاعات خود را با دقت تکمیل کنید. تیم منابع انسانی پس از بررسی، از طریق شماره تماس یا ایمیل با شما در ارتباط خواهد بود.</p>
    </div>
  </div>
</div>
<div class="container">
  <div class="card">
    <form method="post" enctype="multipart/form-data" novalidate>

      <div class="section-head"><div class="section-num">۱</div><h3>اطلاعات فردی</h3></div>
      <div class="grid2">
        <div class="field <?= isset($errors['fullname']) ? 'invalid' : '' ?>">
          <label>نام و نام خانوادگی <span class="req">*</span></label>
          <input type="text" name="fullname" value="<?= e($old['fullname'] ?? '') ?>" placeholder="مثلاً: علی محمدی">
          <?php if (isset($errors['fullname'])): ?><div class="field-err"><?= e($errors['fullname']) ?></div><?php endif; ?>
        </div>
        <div class="field <?= isset($errors['phone']) ? 'invalid' : '' ?>">
          <label>شماره تماس <span class="req">*</span></label>
          <input type="tel" name="phone" value="<?= e($old['phone'] ?? '') ?>" placeholder="09xxxxxxxxx">
          <?php if (isset($errors['phone'])): ?><div class="field-err"><?= e($errors['phone']) ?></div><?php endif; ?>
        </div>
      </div>
      <div class="grid2">
        <div class="field <?= isset($errors['email']) ? 'invalid' : '' ?>">
            <label>ایمیل</label>
            <input type="email" name="email" value="<?= e($old['email'] ?? '') ?>" placeholder="example@email.com">
            <?php if (isset($errors['email'])): ?>
                <div class="field-err"><?= e($errors['email']) ?></div>
            <?php endif; ?>
        </div>
        <div class="field <?= isset($errors['national_id']) ? 'invalid' : '' ?>">
            <label>کد ملی <span class="req">*</span></label>
            <input type="text"
                   name="national_id"
                   maxlength="10"
                   value="<?= e($old['national_id'] ?? '') ?>"
                   placeholder="۱۰ رقم">
        
            <?php if (isset($errors['national_id'])): ?>
                <div class="field-err"><?= e($errors['national_id']) ?></div>
            <?php endif; ?>
        </div>
      </div>
      <div class="grid2">
        <div class="field">
          <label>شهر محل سکونت</label>
          <input type="text" name="city" value="<?= e($old['city'] ?? '') ?>" placeholder="مثلاً تهران">
        </div>
        <div class="field">
          <label>تاریخ تولد (شمسی)</label>
          <div class="grid3">
            <select name="bday">
              <option value="">روز</option>
              <?php for ($d = 1; $d <= 31; $d++): ?>
                <option value="<?= $d ?>" <?= (($old['bday'] ?? '') == $d) ? 'selected' : '' ?>><?= jalali_persian_digits($d) ?></option>
              <?php endfor; ?>
            </select>
            <select name="bmonth">
              <option value="">ماه</option>
              <?php foreach ($JMONTHS as $i => $m): ?>
                <option value="<?= $i + 1 ?>" <?= (($old['bmonth'] ?? '') == $i + 1) ? 'selected' : '' ?>><?= e($m) ?></option>
              <?php endforeach; ?>
            </select>
            <select name="byear">
              <option value="">سال</option>
              <?php for ($y = $currentJY - 15; $y >= $currentJY - 75; $y--): ?>
                <option value="<?= $y ?>" <?= (($old['byear'] ?? '') == $y) ? 'selected' : '' ?>><?= jalali_persian_digits($y) ?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="section-head"><div class="section-num">۲</div><h3>اطلاعات شغلی</h3></div>
      <div class="grid2">
        <div class="field <?= isset($errors['position']) ? 'invalid' : '' ?>">
          <label>موقعیت شغلی مورد نظر <span class="req">*</span></label>
          <select name="position">
            <option value="">انتخاب کنید...</option>
            <?php foreach ($POSITIONS as $p): ?>
              <option value="<?= e($p) ?>" <?= (($old['position'] ?? '') === $p) ? 'selected' : '' ?>><?= e($p) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['position'])): ?><div class="field-err"><?= e($errors['position']) ?></div><?php endif; ?>
        </div>
        <div class="field">
          <label>سابقه کار</label>
          <select name="experience">
            <?php foreach (['بدون سابقه','کمتر از ۱ سال','۱ تا ۳ سال','۳ تا ۵ سال','بیش از ۵ سال'] as $ex): ?>
              <option value="<?= e($ex) ?>" <?= (($old['experience'] ?? '') === $ex) ? 'selected' : '' ?>><?= e($ex) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="field">
        <label>توضیحات تکمیلی</label>
        <textarea name="message" placeholder="در صورت تمایل، درباره خودتان یا انگیزه‌تان برای همکاری بنویسید..."><?= e($old['message'] ?? '') ?></textarea>
      </div>

      <div class="section-head"><div class="section-num">۳</div><h3>مستندات</h3></div>
      <div class="grid2">
        <div class="field <?= isset($errors['photo']) ? 'invalid' : '' ?>">
          <label>تصویر پروفایل <span class="req">*</span></label>
          <div class="upload-box">
            <input type="file" accept="image/*" name="photo" onchange="document.getElementById('photoName').textContent = this.files[0]?.name || '';">
            <div class="up-icon">🖼️</div>
            <div class="up-title">برای آپلود تصویر کلیک کنید</div>
            <div class="up-sub">JPG یا PNG — حداکثر <?= MAX_PHOTO_MB ?> مگابایت</div>
          </div>
          <div class="upload-filename" id="photoName"></div>
          <?php if (isset($errors['photo'])): ?><div class="field-err"><?= e($errors['photo']) ?></div><?php endif; ?>
        </div>
        <div class="field <?= isset($errors['resume']) ? 'invalid' : '' ?>">
          <label>فایل رزومه</label>
          <div class="upload-box">
            <input type="file" accept=".pdf,.doc,.docx" name="resume" onchange="document.getElementById('resumeName').textContent = this.files[0]?.name || '';">
            <div class="up-icon">📄</div>
            <div class="up-title">برای آپلود رزومه کلیک کنید</div>
            <div class="up-sub">PDF یا Word — حداکثر <?= MAX_RESUME_MB ?> مگابایت</div>
          </div>
          <div class="upload-filename" id="resumeName"></div>
          <?php if (isset($errors['resume'])): ?><div class="field-err"><?= e($errors['resume']) ?></div><?php endif; ?>
        </div>
      </div>

      <div style="margin-top:32px;display:flex;justify-content:flex-end;">
        <button type="submit" class="btn btn-primary" style="min-width:200px;">ارسال درخواست</button>
      </div>
    </form>
  </div>
  <p class="foot-note">تاریخ امروز: <?= jalali_today_string() ?> — تمامی اطلاعات شما محرمانه تلقی می‌شود.</p>
</div>
<a href="panel/login.php" class="admin-link">ورود ادمین</a>
</body>
</html>
