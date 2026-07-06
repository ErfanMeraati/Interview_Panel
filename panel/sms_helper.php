<?php
require_once __DIR__ . '/sms_config.php';

// شماره را به فرمت بین‌المللی موردنیاز کاوه‌نگار تبدیل می‌کند: 09121234567 → 989121234567
if (!function_exists('normalize_kavenegar_phone')) {
    function normalize_kavenegar_phone($number) {
        $number = trim((string)$number);
        $number = str_replace([' ', '-'], '', $number);

        if (strpos($number, '+98') === 0) {
            $number = '98' . substr($number, 3);
        } elseif (strpos($number, '0') === 0) {
            $number = '98' . substr($number, 1);
        } elseif (strpos($number, '98') !== 0) {
            $number = '98' . $number;
        }

        return $number;
    }
}

// ارسال پیامک با استفاده از سرویس Lookup (الگوی از پیش تأییدشده) کاوه‌نگار
// $tokens مثل ['token10' => 'نام', 'token20' => 'تاریخ', 'token3' => 'ساعت']
// خروجی: ['success' => bool, 'error' => string|null, 'raw' => array|null]
if (!function_exists('send_kavenegar_lookup')) {
    function send_kavenegar_lookup($receptor, $template, $tokens = []) {
        $apiKey = defined('KAVENEGAR_API_KEY') ? trim(KAVENEGAR_API_KEY) : '';
        if ($apiKey === '' || strpos($apiKey, 'XXXX') !== false) {
            return ['success' => false, 'error' => 'کلید API کاوه‌نگار هنوز تنظیم نشده است (فایل sms_config.php).', 'raw' => null];
        }

        $phone = normalize_kavenegar_phone($receptor);
        if (!ctype_digit($phone) || strlen($phone) !== 12) {
            return ['success' => false, 'error' => 'شماره موبایل متقاضی نامعتبر است: ' . $receptor, 'raw' => null];
        }

        $url = 'https://api.kavenegar.com/v1/' . $apiKey . '/verify/lookup.json';
        $params = array_merge([
            'receptor' => $phone,
            'template' => $template,
        ], $tokens);

        $ch = curl_init($url . '?' . http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return ['success' => false, 'error' => 'خطای اتصال به کاوه‌نگار: ' . $curlErr, 'raw' => null];
        }

        $data = json_decode($response, true);
        $status = $data['return']['status'] ?? null;

        if ($status == 200) {
            return ['success' => true, 'error' => null, 'raw' => $data];
        }

        $msg = $data['return']['message'] ?? 'خطای نامشخص از سرویس پیامک کاوه‌نگار';
        $statusText = $status !== null ? " (کد وضعیت: {$status})" : '';
        return ['success' => false, 'error' => $msg . $statusText, 'raw' => $data];
    }
}