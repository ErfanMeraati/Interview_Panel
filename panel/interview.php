<?php
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/interview_helpers.php';
require_once __DIR__ . '/sms_helper.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$id   = (int)($_POST['id'] ?? 0);
$jy   = (int)($_POST['jy'] ?? 0);
$jm   = (int)($_POST['jm'] ?? 0);
$jd   = (int)($_POST['jd'] ?? 0);
$time = trim($_POST['interview_time'] ?? '');

$timeOk = (bool)preg_match('/^\d{2}:\d{2}$/', $time);
$dateOk = $jy >= 1300 && $jy <= 1500 && $jm >= 1 && $jm <= 12 && $jd >= 1 && $jd <= 31;

if ($id <= 0 || !$dateOk || !$timeOk) {
    header('Location: profile.php?id=' . $id . '&interview_error=1');
    exit;
}

[$gy, $gm, $gd] = interview_jalali_to_gregorian($jy, $jm, $jd);
$gregorianDate = sprintf('%04d-%02d-%02d', $gy, $gm, $gd);

$db = getDb();
$upd = $db->prepare("UPDATE applications SET interview_date = :d, interview_time = :t, interview_status = 'scheduled' WHERE id = :id");
$upd->execute([':d' => $gregorianDate, ':t' => $time, ':id' => $id]);

$stmt = $db->prepare("SELECT phone, full_name FROM applications WHERE id = :id");
$stmt->execute([':id' => $id]);
$app = $stmt->fetch();

$smsResult = ['success' => false];
if ($app) {
    $jalaliDisplay = interview_jalali_display($gregorianDate);
    $timeDisplay = interview_time_display($time);
    $smsResult = send_kavenegar_lookup($app['phone'], KAVENEGAR_INTERVIEW_TEMPLATE, [
        'token10'  => $app['full_name'],
        'token20' => $jalaliDisplay,
        'token' => $timeDisplay,
    ]);
}

$redirect = 'profile.php?id=' . $id . '&interview_saved=1';
$redirect .= '&sms=' . ($smsResult['success'] ? '1' : '0');
if (!$smsResult['success'] && !empty($smsResult['error'])) {
    $redirect .= '&sms_err=' . urlencode($smsResult['error']);
}

header('Location: ' . $redirect);
exit;
