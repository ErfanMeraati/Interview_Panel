<?php
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/interview_helpers.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$db = getDb();
$stmt = $db->prepare("SELECT * FROM applications WHERE id = :id");
$stmt->execute([':id' => $id]);
$app = $stmt->fetch();

if (!$app) { header('Location: dashboard.php'); exit; }

$saved = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $newStatus = $_POST['status'] ?? $app['status'];
    $newNote = trim($_POST['admin_note'] ?? '');
    $upd = $db->prepare("UPDATE applications SET status = :status, admin_note = :note WHERE id = :id");
    $upd->execute([':status' => $newStatus, ':note' => $newNote, ':id' => $id]);
    $app['status'] = $newStatus;
    $app['admin_note'] = $newNote;
    $saved = true;
}

$interviewSaved = isset($_GET['interview_saved']);
$interviewError = isset($_GET['interview_error']);
$smsFlag = $_GET['sms'] ?? null; 
$smsErr  = $_GET['sms_err'] ?? '';

if (!empty($app['interview_date'])) {
    list($iGy, $iGm, $iGd) = array_map('intval', explode('-', $app['interview_date']));
    list($defJy, $defJm, $defJd) = interview_gregorian_to_jalali_ymd($iGy, $iGm, $iGd);
} else {
    list($defJy, $defJm, $defJd) = interview_today_jalali();
}
$jMonths = interview_jalali_month_names();

function initials2($name) {
    $parts = preg_split('/\s+/', trim($name));
    $out = '';
    foreach (array_slice($parts, 0, 2) as $p) { if ($p !== '') $out .= mb_substr($p, 0, 1); }
    return mb_strtoupper($out);
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($app['full_name']) ?> — پرونده متقاضی</title>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700;800&family=Amiri:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/main.css">
<style>
  .action-bar{display:flex;gap:10px;flex-wrap:wrap;margin:0 0 18px;}
  .btn-danger{background:transparent;border:1.5px solid #c40442;color:#c40442;}
  .btn-danger:hover{background:#fdecf1;}
  .btn-invite{background:#253289;color:#fff;border:none;}
  .btn-invite:hover{filter:brightness(1.08);}
  .banner{padding:12px 16px;border-radius:10px;font-size:13.5px;font-weight:700;margin-bottom:16px;}
  .banner-ok{background:#e5f6ec;color:#0f7a42;}
  .banner-err{background:#fdecf1;color:#c40442;}
  .interview-card{border:1.5px dashed #253289;background:#eef0fa;}
  .interview-card .v{color:#253289;}

  .modal-overlay{
    position:fixed;inset:0;background:rgba(20,24,60,.55);backdrop-filter:blur(2px);
    display:none;align-items:center;justify-content:center;z-index:80;padding:20px;
  }
  .modal-overlay.open{display:flex;}
  .modal-box{
    background:#fff;border-radius:18px;padding:28px 26px;max-width:420px;width:100%;
    box-shadow:0 24px 60px -20px rgba(22,29,84,.45);
  }
  .modal-box h3{margin:0 0 8px;color:#161d54;font-size:17px;}
  .modal-box p{margin:0 0 18px;color:#5b6079;font-size:13.5px;line-height:1.8;}
  .modal-actions{display:flex;gap:10px;margin-top:18px;}
  .modal-actions .btn{flex:1;}
  .confirm-check{display:flex;align-items:center;gap:8px;font-size:13px;color:#1b1e2e;margin-top:6px;}
  .confirm-check input{width:auto;}
</style>
</head>
<body>
<div class="admin-shell">
    <div class="profile-head">
        <div class="profile-head-inner">

            <a href="dashboard.php" class="back-link">
                ← بازگشت
            </a>

            <div class="profile-user">

                <?php if ($app['photo_path']): ?>
                    <img class="profile-avatar" src="../uploads/photos/<?= e($app['photo_path']) ?>">
                <?php else: ?>
                    <div class="profile-avatar-fallback">
                        <?= e(initials2($app['full_name'])) ?>
                    </div>
                <?php endif; ?>

                <div>
                    <h2><?= e($app['full_name']) ?></h2>
                    <span><?= e($app['position']) ?></span>
                </div>

            </div>

        </div>
    </div>

  <div class="profile-body-wrap">
    <div class="profile-body">

      <?php if ($saved): ?>
        <div class="banner banner-ok">تغییرات وضعیت با موفقیت ذخیره شد ✓</div>
      <?php endif; ?>
      <?php if ($interviewSaved): ?>
        <div class="banner banner-ok">زمان مصاحبه با موفقیت ثبت شد ✓</div>
      <?php endif; ?>
      <?php if ($interviewError): ?>
        <div class="banner banner-err">تاریخ یا ساعت وارد شده معتبر نیست، دوباره تلاش کنید.</div>
      <?php endif; ?>
      <?php if ($smsFlag === '1'): ?>
        <div class="banner banner-ok">پیامک دعوت به مصاحبه برای متقاضی ارسال شد ✓</div>
      <?php elseif ($smsFlag === '0'): ?>
        <div class="banner banner-err">زمان مصاحبه ثبت شد، اما ارسال پیامک ناموفق بود<?= $smsErr ? ' («' . e($smsErr) . '»)' : '' ?>. لطفاً به‌صورت دستی به متقاضی اطلاع دهید.</div>
      <?php endif; ?>

      <div class="action-bar">
        <button type="button" class="btn btn-invite" onclick="openInterviewModal()">
          📅 <?= $app['interview_status'] === 'scheduled' ? 'ویرایش زمان مصاحبه' : 'دعوت به مصاحبه' ?>
        </button>
        <button type="button" class="btn btn-danger" onclick="openDeleteModal()">🗑 حذف پرونده</button>
      </div>

      <?php if ($app['interview_status'] === 'scheduled' && $app['interview_date']): ?>
        <div class="info-card interview-card">
          <div class="info-row"><span class="k">تاریخ مصاحبه</span><span class="v"><?= e(interview_jalali_display($app['interview_date'])) ?></span></div>
          <div class="info-row"><span class="k">ساعت مصاحبه</span><span class="v"><?= e(interview_time_display($app['interview_time'])) ?></span></div>
        </div>
      <?php endif; ?>

      <div class="info-card">
        <div class="info-row"><span class="k">شماره تماس</span><span class="v"><?= e($app['phone']) ?></span></div>
        <div class="info-row"><span class="k">ایمیل</span><span class="v"><?= e($app['email']) ?></span></div>
        <div class="info-row"><span class="k">کد ملی</span><span class="v"><?= e($app['national_id'] ?: '—') ?></span></div>
        <div class="info-row"><span class="k">شهر</span><span class="v"><?= e($app['city'] ?: '—') ?></span></div>
        <div class="info-row"><span class="k">تاریخ تولد</span><span class="v"><?= e($app['birth_jalali'] ?: '—') ?></span></div>
        <div class="info-row"><span class="k">سابقه کار</span><span class="v"><?= e($app['experience']) ?></span></div>
        <div class="info-row"><span class="k">تاریخ ارسال</span><span class="v"><?= e($app['submitted_at_jalali']) ?></span></div>
        <div class="info-row"><span class="k">کد پیگیری</span><span class="v">APP-<?= str_pad((string)$app['id'], 6, '0', STR_PAD_LEFT) ?></span></div>
      </div>

      <?php if ($app['message']): ?>
        <div class="info-card">
          <label style="margin-bottom:8px;">توضیحات متقاضی</label>
          <div class="msg-box"><?= nl2br(e($app['message'])) ?></div>
        </div>
      <?php endif; ?>

      <div class="info-card">
        <label style="margin-bottom:10px;">فایل رزومه</label>
        <?php if ($app['resume_path']): ?>
          <a class="resume-btn" href="../uploads/resumes/<?= e($app['resume_path']) ?>" download="<?= e($app['resume_original_name']) ?>">
            📄 دانلود / مشاهده رزومه (<?= e($app['resume_original_name']) ?>)
          </a>
        <?php else: ?>
          <div style="color:var(--ink-soft);font-size:13px;">رزومه‌ای ارسال نشده است.</div>
        <?php endif; ?>
      </div>

      <form method="post" class="info-card">
        <label style="margin-bottom:8px;">وضعیت بررسی</label>
        <select name="status">
          <?php foreach (['جدید','در حال بررسی','پذیرفته شد','رد شد'] as $s): ?>
            <option value="<?= e($s) ?>" <?= $app['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
          <?php endforeach; ?>
        </select>
        <div style="margin-top:14px;">
          <label style="margin-bottom:8px;">یادداشت داخلی ادمین</label>
          <textarea name="admin_note" placeholder="یادداشت خود را بنویسید..."><?= e($app['admin_note'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:14px;">ذخیره تغییرات</button>
      </form>
    </div>
  </div>
</div>

<div class="modal-overlay" id="interviewModal">
  <div class="modal-box">
    <h3>تعیین زمان مصاحبه</h3>
    <p>تاریخ و ساعت مصاحبه با «<?= e($app['full_name']) ?>» را مشخص کنید.</p>
    <form method="post" action="interview.php">
      <input type="hidden" name="id" value="<?= (int)$app['id'] ?>">
      <div class="field">
        <label>تاریخ مصاحبه (شمسی)</label>
        <div class="grid3">
          <select name="jd">
            <?php for ($d = 1; $d <= 31; $d++): ?>
              <option value="<?= $d ?>" <?= $d === $defJd ? 'selected' : '' ?>><?= e(interview_fa_digits($d)) ?></option>
            <?php endfor; ?>
          </select>
          <select name="jm">
            <?php foreach ($jMonths as $mi => $mName): ?>
              <option value="<?= $mi + 1 ?>" <?= ($mi + 1) === $defJm ? 'selected' : '' ?>><?= e($mName) ?></option>
            <?php endforeach; ?>
          </select>
          <select name="jy">
            <?php for ($y = $defJy - 1; $y <= $defJy + 2; $y++): ?>
              <option value="<?= $y ?>" <?= $y === $defJy ? 'selected' : '' ?>><?= e(interview_fa_digits($y)) ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>
      <div class="field">
        <label>ساعت مصاحبه</label>
        <input type="time" name="interview_time" value="<?= e(substr($app['interview_time'] ?? '', 0, 5)) ?>" required>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" onclick="closeInterviewModal()">انصراف</button>
        <button type="submit" class="btn btn-invite">ثبت زمان مصاحبه و ارسال پیامک</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="deleteModal">
  <div class="modal-box">
    <h3>حذف پرونده متقاضی</h3>
    <p>آیا از حذف کامل پرونده‌ی «<?= e($app['full_name']) ?>» اطمینان دارید؟ این عمل شامل حذف عکس و رزومه از سرور نیز می‌شود و غیرقابل بازگشت است.</p>
    <form method="post" action="delete.php" id="deleteForm">
      <input type="hidden" name="id" value="<?= (int)$app['id'] ?>">
      <input type="hidden" name="confirm" value="">
      <label class="confirm-check">
        <input type="checkbox" id="confirmCheck" onchange="toggleDeleteBtn(this)">
        متوجه هستم این عمل غیرقابل بازگشت است.
      </label>
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" onclick="closeDeleteModal()">انصراف</button>
        <button type="submit" class="btn btn-danger" id="confirmDeleteBtn" disabled style="background:#c40442;color:#fff;">بله، حذف کن</button>
      </div>
    </form>
  </div>
</div>

<script>
function openInterviewModal(){ document.getElementById('interviewModal').classList.add('open'); }
function closeInterviewModal(){ document.getElementById('interviewModal').classList.remove('open'); }
function openDeleteModal(){ document.getElementById('deleteModal').classList.add('open'); }
function closeDeleteModal(){ document.getElementById('deleteModal').classList.remove('open'); }
function toggleDeleteBtn(cb){
  document.getElementById('confirmDeleteBtn').disabled = !cb.checked;
  document.querySelector('#deleteForm input[name=confirm]').value = cb.checked ? 'yes' : '';
}
document.querySelectorAll('.modal-overlay').forEach(function(ov){
  ov.addEventListener('click', function(e){ if(e.target === ov) ov.classList.remove('open'); });
});
</script>
</body>
</html>
