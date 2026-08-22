<?php
date_default_timezone_set('Asia/Tashkent');

// PHP 8.1+ da mysqli standart holatda xatoликда fatal exception otadi.
// Bu loyihadagi barcha kod eski uslubda (@ va false tekshiruvlari bilan)
// yozilgan, shuning uchun exception rejimini o'chiramiz.
mysqli_report(MYSQLI_REPORT_OFF);

define('API_KEY', getenv('TELEGRAM_BOT_TOKEN'));

$sana = date("d.m.Y");
$soat = date("H:i");

define("DB_SERVER",   getenv('DB_HOST'));
define("DB_USERNAME", getenv('DB_USER'));
define("DB_PASSWORD", getenv('DB_PASS'));
define("DB_NAME",     getenv('DB_NAME'));

$connect = mysqli_connect('p:'.DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
mysqli_set_charset($connect, "utf8mb4");
?>
