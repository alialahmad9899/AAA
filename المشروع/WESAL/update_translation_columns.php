<?php
/**
 * ==============================================================================
 * اسم الملف: update_translation_columns.php
 * المشروع: منصة وصال - Wesal Syria
 * الوصف: إضافة حقول الترجمة العربية لجدولي الدول والمحافظات
 * ==============================================================================
 */

$db_host = 'localhost';
$db_name = 'u400357137_wesal';
$db_user = 'u400357137_wesal_user';
$db_pass = 'Malekmalek.1991';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "<div style='font-family: sans-serif; padding: 20px; direction: rtl;'>";
    echo "<h2>⏳ جاري تحديث الجداول لدعم الترجمة العربية الكاملة...</h2>";

    // 1. إضافة عمود name_ar لجدول الدول إن لم يكن موجوداً
    $pdo->exec("ALTER TABLE `countries` ADD COLUMN IF NOT EXISTS `name_ar` VARCHAR(150) NULL AFTER `name`;");
    echo "<p style='color: green;'>✓ تم تجهيز حقل name_ar في جدول الدول بنجاح!</p>";

    // 2. إضافة عمود name_ar لجدول المحافظات إن لم يكن موجوداً
    $pdo->exec("ALTER TABLE `states` ADD COLUMN IF NOT EXISTS `name_ar` VARCHAR(150) NULL AFTER `name`;");
    echo "<p style='color: green;'>✓ تم تجهيز حقل name_ar في جدول المحافظات بنجاح!</p>";

    echo "<h3 style='color: blue;'>🎉 تم تحديث الهيكلية! يمكنك الآن تشغيل السكريبت وحذفه من السيرفر.</h3>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div style='color: red; padding: 20px; direction: rtl;'>";
    echo "<h3>خطأ:</h3> <p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>