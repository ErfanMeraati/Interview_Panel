<?php
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../jalali.php';
require_once __DIR__ . '/interview_helpers.php';
require_admin();

$statusFilter = $_GET['status'] ?? 'all';
$search = trim($_GET['q'] ?? '');
$statuses = ['جدید','در حال بررسی','پذیرفته شد','رد شد'];

$db = getDb();

$counts = ['total' => 0, 'جدید' => 0, 'در حال بررسی' => 0, 'پذیرفته شد' => 0, 'رد شد' => 0];
$counts['total'] = (int)$db->query("SELECT COUNT(*) c FROM applications")->fetch()['c'];
foreach ($statuses as $s) {
    $stmt = $db->prepare("SELECT COUNT(*) c FROM applications WHERE status = :s");
    $stmt->execute([':s' => $s]);
    $counts[$s] = (int)$stmt->fetch()['c'];
}

$todayG = date('Y-m-d');
$stmt = $db->prepare("SELECT id, full_name, position, interview_time FROM applications WHERE interview_date = :d AND interview_status = 'scheduled' ORDER BY interview_time ASC");
$stmt->execute([':d' => $todayG]);
$todayInterviews = $stmt->fetchAll();

$sql = "SELECT id, full_name, position, status, submitted_at_jalali, photo_path FROM applications WHERE 1=1";
$params = [];
if ($statusFilter !== 'all' && in_array($statusFilter, $statuses, true)) {
    $sql .= " AND status = :status";
    $params[':status'] = $statusFilter;
}
if ($search !== '') {
    $sql .= " AND (full_name LIKE :q OR position LIKE :q)";
    $params[':q'] = '%' . $search . '%';
}
$sql .= " ORDER BY id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$list = $stmt->fetchAll();

$deleted = isset($_GET['deleted']);
$deleteError = isset($_GET['delete_error']);

function badgeClass($status) {
    return ['جدید'=>'badge-new','در حال بررسی'=>'badge-review','پذیرفته شد'=>'badge-accept','رد شد'=>'badge-reject'][$status] ?? 'badge-new';
}
function initials($name) {
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
<title>داشبورد ادمین — <?= e(COMPANY_NAME) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700;800&family=Amiri:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/main.css">
<style>
  .banner{padding:12px 16px;border-radius:10px;font-size:13.5px;font-weight:700;margin-bottom:16px;}
  .banner-ok{background:#e5f6ec;color:#0f7a42;}
  .banner-err{background:#fdecf1;color:#c40442;}

  .today-box{
    background:#161d54;color:#fff;border-radius:16px;padding:20px 22px;margin-bottom:22px;
    box-shadow:0 12px 30px -16px rgba(22,29,84,.5);
  }
  .today-box h3{margin:0 0 14px;font-size:15px;display:flex;align-items:center;gap:8px;}
  .today-list{display:flex;flex-direction:column;gap:8px;}
  .today-item{
    display:flex;align-items:center;justify-content:space-between;background:rgba(255,255,255,.08);
    border-radius:10px;padding:11px 14px;text-decoration:none;color:#fff;transition:background .15s;
  }
  .today-item:hover{background:rgba(255,255,255,.18);}
  .today-item .name{font-weight:700;font-size:13.5px;}
  .today-item .pos{font-size:11.5px;color:rgba(255,255,255,.65);margin-right:8px;}
  .today-item .time{background:#c40442;padding:4px 12px;border-radius:20px;font-size:12.5px;font-weight:800;}
  .today-empty{color:rgba(255,255,255,.6);font-size:13px;}

  .cand-card{position:relative;}
  .card-delete{
    position:absolute;top:12px;left:12px;width:28px;height:28px;border-radius:50%;
    background:#fff;border:1.5px solid #f1d3db;color:#c40442;display:flex;align-items:center;
    justify-content:center;cursor:pointer;font-size:13px;z-index:2;box-shadow:0 4px 10px -4px rgba(0,0,0,.2);
  }
  .card-delete:hover{background:#fdecf1;}

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
  <div class="admin-top">
    <div class="admin-brand">
      <div class="seal"><span>ا</span></div>
      <div><b>پنل مدیریت استخدام</b><small>مشاهده و بررسی درخواست‌های شغلی</small></div>
    </div>
    <div style="display:flex;gap:10px;">
      <a href="../index.php" class="btn btn-ghost btn-sm" style="color:#fff;border-color:rgba(255,255,255,.4);">فرم متقاضی</a>
      <a href="logout.php" class="btn btn-outline-crimson btn-sm" style="border-color:rgba(255,255,255,.5);color:#fff;">خروج</a>
    </div>
  </div>
  <div class="admin-wrap">

    <?php if ($deleted): ?>
      <div class="banner banner-ok">پرونده با موفقیت حذف شد ✓</div>
    <?php endif; ?>
    <?php if ($deleteError): ?>
      <div class="banner banner-err">برای حذف باید تأییدیه را علامت بزنید.</div>
    <?php endif; ?>

    <div class="today-box">
      <h3>📅 مصاحبه‌های امروز</h3>
      <?php if (empty($todayInterviews)): ?>
        <div class="today-empty">برای امروز مصاحبه‌ای ثبت نشده است.</div>
      <?php else: ?>
        <div class="today-list">
          <?php foreach ($todayInterviews as $iv): ?>
            <a href="profile.php?id=<?= (int)$iv['id'] ?>" class="today-item">
              <span><span class="name"><?= e($iv['full_name']) ?></span><span class="pos"><?= e($iv['position']) ?></span></span>
              <span class="time"><?= e(interview_time_display($iv['interview_time'])) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="admin-stats">
      <div class="stat"><b><?= jalali_persian_digits($counts['total']) ?></b><span>کل درخواست‌ها</span></div>
      <div class="stat accent"><b><?= jalali_persian_digits($counts['جدید']) ?></b><span>درخواست جدید</span></div>
      <div class="stat"><b><?= jalali_persian_digits($counts['در حال بررسی']) ?></b><span>در حال بررسی</span></div>
      <div class="stat"><b><?= jalali_persian_digits($counts['پذیرفته شد']) ?></b><span>پذیرفته‌شده</span></div>
    </div>

    <form method="get" class="toolbar">
      <div class="filters">
        <a href="?status=all" class="chip <?= $statusFilter==='all'?'active':'' ?>">همه</a>
        <a href="?status=<?= urlencode('جدید') ?>" class="chip <?= $statusFilter==='جدید'?'active':'' ?>">جدید</a>
        <a href="?status=<?= urlencode('در حال بررسی') ?>" class="chip <?= $statusFilter==='در حال بررسی'?'active':'' ?>">در حال بررسی</a>
        <a href="?status=<?= urlencode('پذیرفته شد') ?>" class="chip <?= $statusFilter==='پذیرفته شد'?'active':'' ?>">پذیرفته</a>
        <a href="?status=<?= urlencode('رد شد') ?>" class="chip <?= $statusFilter==='رد شد'?'active':'' ?>">رد شده</a>
      </div>
      <div class="search-box">
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="جستجوی نام یا موقعیت شغلی..." onchange="this.form.submit()">
      </div>
      <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
    </form>

    <?php if (empty($list)): ?>
      <div class="empty-state"><div class="ic">📭</div>هیچ درخواستی با این شرایط یافت نشد.</div>
    <?php else: ?>
      <div class="cand-grid">
        <?php foreach ($list as $item): ?>
          <a href="profile.php?id=<?= (int)$item['id'] ?>" class="cand-card">
            <span class="card-delete" title="حذف پرونده"
                  onclick="event.preventDefault();event.stopPropagation();openDeleteModal(<?= (int)$item['id'] ?>, '<?= e(addslashes($item['full_name'])) ?>');">✕</span>
            <div class="cand-top">
              <?php if ($item['photo_path']): ?>
                <img class="avatar" src="../uploads/photos/<?= e($item['photo_path']) ?>">
              <?php else: ?>
                <div class="avatar-fallback"><?= e(initials($item['full_name'])) ?></div>
              <?php endif; ?>
              <div>
                <div class="cand-name"><?= e($item['full_name']) ?></div>
                <div class="cand-pos"><?= e($item['position']) ?></div>
              </div>
            </div>
            <div class="cand-meta">
              <span><?= e($item['submitted_at_jalali']) ?></span>
              <span class="badge <?= badgeClass($item['status']) ?>"><?= e($item['status']) ?></span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="modal-overlay" id="deleteModal">
  <div class="modal-box">
    <h3>حذف پرونده متقاضی</h3>
    <p>آیا از حذف کامل پرونده‌ی «<span id="deleteName"></span>» اطمینان دارید؟ این عمل شامل حذف عکس و رزومه از سرور نیز می‌شود و غیرقابل بازگشت است.</p>
    <form method="post" action="delete.php" id="deleteForm">
      <input type="hidden" name="id" id="deleteId" value="">
      <input type="hidden" name="confirm" value="">
      <label class="confirm-check">
        <input type="checkbox" id="confirmCheck" onchange="toggleDeleteBtn(this)">
        متوجه هستم این عمل غیرقابل بازگشت است.
      </label>
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" onclick="closeDeleteModal()">انصراف</button>
        <button type="submit" class="btn" id="confirmDeleteBtn" disabled style="background:#c40442;color:#fff;">بله، حذف کن</button>
      </div>
    </form>
  </div>
</div>

<script>
function openDeleteModal(id, name){
  document.getElementById('deleteId').value = id;
  document.getElementById('deleteName').textContent = name;
  document.getElementById('confirmCheck').checked = false;
  document.getElementById('confirmDeleteBtn').disabled = true;
  document.querySelector('#deleteForm input[name=confirm]').value = '';
  document.getElementById('deleteModal').classList.add('open');
}
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
