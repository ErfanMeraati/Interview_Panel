<?php
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../db.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

if (($_POST['confirm'] ?? '') !== 'yes') {
    header('Location: dashboard.php?delete_error=1');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: dashboard.php');
    exit;
}

$db = getDb();
$stmt = $db->prepare("SELECT photo_path, resume_path FROM applications WHERE id = :id");
$stmt->execute([':id' => $id]);
$app = $stmt->fetch();

if ($app) {
    // حذف فایل‌های آپلود شده از روی سرور
    if (!empty($app['photo_path'])) {
        $photoFile = __DIR__ . '/../uploads/photos/' . $app['photo_path'];
        if (is_file($photoFile)) { @unlink($photoFile); }
    }
    if (!empty($app['resume_path'])) {
        $resumeFile = __DIR__ . '/../uploads/resumes/' . $app['resume_path'];
        if (is_file($resumeFile)) { @unlink($resumeFile); }
    }

    $del = $db->prepare("DELETE FROM applications WHERE id = :id");
    $del->execute([':id' => $id]);
}

header('Location: dashboard.php?deleted=1');
exit;
