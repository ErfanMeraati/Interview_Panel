<?php

function jalali_persian_digits($n) {
    $map = ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹'];
    return strtr((string)$n, $map);
}

function gregorian_to_jalali($gy, $gm, $gd) {
    $g_d_m = [0,31,59,90,120,151,181,212,243,273,304,334];
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) + $gd + $g_d_m[$gm - 1];
    $jy = -1595 + (33 * intdiv($days, 12053));
    $days %= 12053;
    $jy += 4 * intdiv($days, 1461);
    $days %= 1461;
    if ($days > 365) {
        $jy += intdiv($days - 1, 365);
        $days = ($days - 1) % 365;
    }
    if ($days < 186) {
        $jm = 1 + intdiv($days, 31);
        $jd = 1 + ($days % 31);
    } else {
        $jm = 7 + intdiv($days - 186, 30);
        $jd = 1 + (($days - 186) % 30);
    }
    return [$jy, $jm, $jd];
}

function jalali_month_names() {
    return ["فروردین","اردیبهشت","خرداد","تیر","مرداد","شهریور","مهر","آبان","آذر","دی","بهمن","اسفند"];
}

function jalali_today_string() {
    [$jy, $jm, $jd] = gregorian_to_jalali((int)date('Y'), (int)date('n'), (int)date('j'));
    $months = jalali_month_names();
    return jalali_persian_digits($jd) . ' ' . $months[$jm - 1] . ' ' . jalali_persian_digits($jy);
}

function jalali_from_datetime($datetimeStr) {
    $ts = strtotime($datetimeStr);
    [$jy, $jm, $jd] = gregorian_to_jalali((int)date('Y', $ts), (int)date('n', $ts), (int)date('j', $ts));
    $months = jalali_month_names();
    return jalali_persian_digits($jd) . ' ' . $months[$jm - 1] . ' ' . jalali_persian_digits($jy);
}
