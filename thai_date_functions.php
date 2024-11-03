<?php
function thaiMonth($month) {
    $thaiMonths = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    return $thaiMonths[$month];
}

function convertToThaiNumerals($number) {
    $thaiNumerals = ['๐', '๑', '๒', '๓', '๔', '๕', '๖', '๗', '๘', '๙'];
    return preg_replace_callback('/\d/', function($match) use ($thaiNumerals) {
        return $thaiNumerals[$match[0]];
    }, $number);
}

function formatThaiDate($timestamp) {
    $date = new DateTime($timestamp);
    $day = convertToThaiNumerals($date->format('j'));
    $month = thaiMonth($date->format('n'));
    $year = convertToThaiNumerals($date->format('Y') + 543);
    return "$day $month $year";
}
function formatThaiDateTime($timestamp) {
    $date = new DateTime($timestamp);
    $thaiMonth = thaiMonth($date->format('n'));
    $thaiYear = convertToThaiNumerals($date->format('Y') + 543);
    $thaiDay = convertToThaiNumerals($date->format('j'));
    $thaiHour = convertToThaiNumerals($date->format('H'));
    $thaiMinute = convertToThaiNumerals($date->format('i'));
    return "$thaiDay $thaiMonth $thaiYear เวลา $thaiHour:$thaiMinute น.";
}
?>