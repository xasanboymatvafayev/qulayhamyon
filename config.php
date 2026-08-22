<?php
date_default_timezone_set('Asia/Tashkent');

// PHP 8.1+ da mysqli standart holatda xatolikda fatal exception otadi.
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
if ($connect) {
    mysqli_set_charset($connect, "utf8mb4");

    // Ba'zi kod qismlari 'settings' jadvalini kutadi (masalan oylik narx),
    // lekin u boshlang'ich sxemada yo'q edi — mavjud bo'lmasa avtomatik yaratamiz.
    mysqli_query($connect, "CREATE TABLE IF NOT EXISTS settings (
        id INT NOT NULL AUTO_INCREMENT,
        `key` VARCHAR(100) NOT NULL,
        value VARCHAR(255) DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_key (`key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Kassa sozlamalari (karta, bank, egasi, email) 'shops' jadvaliga
    // saqlanadi, lekin bu ustunlar boshlang'ich sxemada yo'q edi — shuning
    // uchun "Karta kiritish" va "Email" saqlanmayotgan edi (so'rov jimgina
    // muvaffaqiyatsiz bo'lardi). Yetishmasa avtomatik qo'shamiz.
    $needed_shop_cols = [
        'card_number' => "VARCHAR(30) DEFAULT NULL",
        'card_bank'   => "VARCHAR(30) DEFAULT NULL",
        'card_owner'  => "VARCHAR(100) DEFAULT NULL",
        'email'       => "VARCHAR(150) DEFAULT NULL",
    ];
    $existing_cols = [];
    $col_res = mysqli_query($connect, "SHOW COLUMNS FROM shops");
    if ($col_res) {
        while ($c = mysqli_fetch_assoc($col_res)) {
            $existing_cols[] = $c['Field'];
        }
        foreach ($needed_shop_cols as $col => $def) {
            if (!in_array($col, $existing_cols)) {
                mysqli_query($connect, "ALTER TABLE shops ADD COLUMN `$col` $def");
            }
        }
    }
}

// ============================================================
// XAVFSIZ MYSQLI YORDAMCHILARI
// So'rov muvaffaqiyatsiz bo'lsa (jadval/ustun yo'q, sintaksis xatosi va h.k.)
// mysqli_query 'false' qaytaradi. mysqli_fetch_assoc/mysqli_num_rows esa
// PHP 8+da 'false' qabul qilmaydi va fatal TypeError bilan butun so'rovni
// (masalan bitta tugma bosilganda) jimgina o'ldiradi. Shu funksiyalar
// buni oldini oladi — muvaffaqiyatsiz bo'lsa null/0 qaytaradi va xatoni
// error.log fayliga yozadi, lekin bot ishlashda davom etadi.
// ============================================================
function safe_fetch_assoc($result, $connect = null) {
    if (!($result instanceof mysqli_result)) {
        if ($connect) {
            file_put_contents(__DIR__ . '/error.log',
                date('Y-m-d H:i:s') . " | DB SO'ROV XATO: " . mysqli_error($connect) . "\n",
                FILE_APPEND);
        }
        return null;
    }
    return mysqli_fetch_assoc($result);
}
function safe_num_rows($result, $connect = null) {
    if (!($result instanceof mysqli_result)) {
        if ($connect) {
            file_put_contents(__DIR__ . '/error.log',
                date('Y-m-d H:i:s') . " | DB SO'ROV XATO: " . mysqli_error($connect) . "\n",
                FILE_APPEND);
        }
        return 0;
    }
    return mysqli_num_rows($result);
}

// Har qanday qolgan (kutilmagan) fatal xatoni ham jim o'lim o'rniga
// error.log ga yozib qo'yadi — kelajakda diagnostika uchun foydali.
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        file_put_contents(__DIR__ . '/error.log',
            date('Y-m-d H:i:s') . " | FATAL: {$e['message']} in {$e['file']}:{$e['line']}\n",
            FILE_APPEND);
    }
});
?>
