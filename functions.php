<?php
require_once __DIR__ . '/config.php';

function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function is_admin_logged_in() {
    return !empty($_SESSION['is_admin']);
}

function require_admin() {
    if (!is_admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}


function handle_upload($fileField, $destDir, $allowedExt, $maxBytes, &$error) {
    if (!isset($_FILES[$fileField]) || $_FILES[$fileField]['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'فایلی انتخاب نشده است.';
        return false;
    }
    $file = $_FILES[$fileField];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'خطا در آپلود فایل.';
        return false;
    }
    if ($file['size'] > $maxBytes) {
        $error = 'حجم فایل بیش از حد مجاز است.';
        return false;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        $error = 'فرمت فایل مجاز نیست.';
        return false;
    }
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    $randomName = bin2hex(random_bytes(16)) . '.' . $ext;
    $destPath = rtrim($destDir, '/') . '/' . $randomName;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        $error = 'ذخیره فایل با خطا مواجه شد.';
        return false;
    }
    return $randomName;
}
