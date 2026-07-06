<?php

if (!function_exists('interview_fa_digits')) {
    function interview_fa_digits($n) {
        $map = ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹'];
        return strtr((string)$n, $map);
    }
}

if (!function_exists('interview_jalali_month_names')) {
    function interview_jalali_month_names() {
        return ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
    }
}

if (!function_exists('interview_gregorian_to_jalali_ymd')) {
    function interview_gregorian_to_jalali_ymd($gy, $gm, $gd) {
        $g_d_m = [0,31,59,90,120,151,181,212,243,273,304,334];
        $gy2 = ($gm > 2) ? $gy + 1 : $gy;
        $days = 355666 + (365 * $gy) + (int)(($gy2 + 3) / 4) - (int)(($gy2 + 99) / 100) + (int)(($gy2 + 399) / 400) + $gd + $g_d_m[$gm - 1];
        $jy = -1595 + (33 * (int)($days / 12053));
        $days %= 12053;
        $jy += 4 * (int)($days / 1461);
        $days %= 1461;
        if ($days > 365) { $jy += (int)(($days - 1) / 365); $days = ($days - 1) % 365; }
        if ($days < 186) { $jm = 1 + (int)($days / 31); $jd = 1 + ($days % 31); }
        else { $jm = 7 + (int)(($days - 186) / 30); $jd = 1 + (($days - 186) % 30); }
        return [$jy, $jm, $jd];
    }
}

if (!function_exists('interview_jalali_to_gregorian')) {
    function interview_jalali_to_gregorian($jy, $jm, $jd) {
        $jy += 1595;
        $days = -355668 + (365 * $jy) + ((int)($jy / 33) * 8) + (int)((($jy % 33) + 3) / 4) + $jd
              + (($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);
        $gy = 400 * (int)($days / 146097);
        $days %= 146097;
        if ($days > 36524) {
            $days--;
            $gy += 100 * (int)($days / 36524);
            $days %= 36524;
            if ($days >= 365) $days++;
        }
        $gy += 4 * (int)($days / 1461);
        $days %= 1461;
        if ($days > 365) {
            $gy += (int)(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        $gd = $days + 1;
        $isLeapG = (($gy % 4 == 0) && ($gy % 100 != 0)) || ($gy % 400 == 0);
        $sal_a = [0, 31, $isLeapG ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $gm = 0;
        for ($i = 1; $i <= 12; $i++) {
            if ($gd <= $sal_a[$i]) { $gm = $i; break; }
            $gd -= $sal_a[$i];
        }
        return [$gy, $gm, $gd];
    }
}

if (!function_exists('interview_today_jalali')) {
    function interview_today_jalali() {
        $now = new DateTime('now');
        return interview_gregorian_to_jalali_ymd((int)$now->format('Y'), (int)$now->format('n'), (int)$now->format('j'));
    }
}

if (!function_exists('interview_jalali_display')) {
    function interview_jalali_display($gDate) {
        if (!$gDate) return '';
        list($gy, $gm, $gd) = array_map('intval', explode('-', $gDate));
        list($jy, $jm, $jd) = interview_gregorian_to_jalali_ymd($gy, $gm, $gd);
        $months = interview_jalali_month_names();
        return interview_fa_digits($jd) . ' ' . $months[$jm - 1] . ' ' . interview_fa_digits($jy);
    }
}

if (!function_exists('interview_time_display')) {
    function interview_time_display($time) {
        if (!$time) return '';
        return interview_fa_digits(substr($time, 0, 5));
    }
}
