<?php



define('DB_HOST', 'localhost'); 
define('DB_NAME', 'DB_NAME'); 
define('DB_USER', 'DB_USER');
define('DB_PASS', 'DB_PASS');      


define('ADMIN_PASSWORD_HASH', 'ADMIN_PASSWORD_HASH');

// حداکثر حجم مجاز فایل‌ها (به مگابایت)
define('MAX_PHOTO_MB', 2);
define('MAX_RESUME_MB', 3);

// نام شرکت  در هدر و ایمیل‌ها استفاده می‌شود
define('COMPANY_NAME', 'COMPANY NAME');

define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

date_default_timezone_set('Asia/Tehran');
session_start();
