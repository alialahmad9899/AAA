<?php
/**
 * سكربت تفريغ ذاكرة التخزين المؤقت للمسارات والإعدادات - منصة وصال
 */

// تحميل بيئة عمل Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // تنفيذ أمر تفريغ كاش المسارات وكاش الإعدادات برمجياً
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('cache:clear');

    echo "<div style='font-family: Arial, sans-serif; direction: rtl; text-align: center; padding: 40px;'>";
    echo "<div style='background-color: #d4edda; color: #155724; padding: 20px; border-radius: 10px; display: inline-block;'>";
    echo "<h2>✅ تم مسح ذاكرة التخزين المؤقت للمسارات (Route Cache) بنجاح!</h2>";
    echo "<p>أصبحت المسارات الجديدة مفعلة وجاهزة للاستخدام في التطبيق والموقع.</p>";
    echo "</div>";
    echo "</div>";

} catch (\Exception $e) {
    echo "<div style='font-family: Arial; direction: rtl; text-align: center; padding: 20px; color: red;'>";
    echo "<h3>❌ حدث خطأ أثناء تنفيذ الأمر:</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "</div>";
}