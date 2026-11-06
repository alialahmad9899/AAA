<?php
/**
 * ==============================================================================
 * اسم الملف: api_locations.php
 * المشروع: منصة وصال - Wesal Syria
 * الوصف: REST API مخصص للجلب والبحث مع حل مشكلة تعارض الترميزات (Collation 1267)
 * ==============================================================================
 */

// 1. الترويسات وإعدادات التخزين المؤقت
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Cache-Control: public, max-age=43200");

// 2. إعدادات الاتصال بقاعدة البيانات
$db_host = 'localhost';
$db_name = 'u400357137_wesal';
$db_user = 'u400357137_wesal_user';
$db_pass = 'Malekmalek.1991';

try {
    // الاتصال مع ضبط الترميز لـ utf8mb4 وتحديد الـ collation الافتراضي للجلسة
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false
    ]);

    // توحيد ترميز مقارنة الجلسة داخل MySQL لمنع التعارض
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;");

} catch (PDOException $e) {
    echo json_encode(['status' => false, 'message' => 'فشل الاتصال بقاعدة البيانات: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit();
}

// 3. قراءة المعاملات الإضافية
$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$lang   = isset($_GET['lang']) ? strtolower(trim($_GET['lang'])) : 'ar';

switch ($action) {

    // ==========================================================================
    // جلب قائمة الدول
    // ==========================================================================
    case 'get_countries':
        try {
            $stmt = $pdo->prepare("
                SELECT `id`, `code`, `name` AS `name_en`, 
                       IFNULL(NULLIF(`name_ar`, ''), `name`) AS `name_ar`,
                       CASE WHEN :lang = 'ar' THEN IFNULL(NULLIF(`name_ar`, ''), `name`) ELSE `name` END AS `display_name`
                FROM `countries` 
                WHERE `status` = 1 
                ORDER BY `display_name` COLLATE utf8mb4_unicode_ci ASC
            ");
            $stmt->execute([':lang' => $lang]);
            $countries = $stmt->fetchAll();

            echo json_encode(['status' => true, 'count' => count($countries), 'data' => $countries], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['status' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    // ==========================================================================
    // جلب المحافظات
    // ==========================================================================
    case 'get_states':
        $country_id = isset($_GET['country_id']) ? intval($_GET['country_id']) : 0;

        if ($country_id <= 0) {
            echo json_encode(['status' => false, 'message' => 'معرّف الدولة (country_id) مطلوب.'], JSON_UNESCAPED_UNICODE);
            exit();
        }

        try {
            $stmt = $pdo->prepare("
                SELECT `id`, `country_id`, `name` AS `name_en`,
                       IFNULL(NULLIF(`name_ar`, ''), `name`) AS `name_ar`,
                       CASE WHEN :lang = 'ar' THEN IFNULL(NULLIF(`name_ar`, ''), `name`) ELSE `name` END AS `display_name`
                FROM `states` 
                WHERE `country_id` = :country_id AND `status` = 1 
                ORDER BY `display_name` COLLATE utf8mb4_unicode_ci ASC
            ");
            $stmt->execute([':country_id' => $country_id, ':lang' => $lang]);
            $states = $stmt->fetchAll();

            echo json_encode(['status' => true, 'count' => count($states), 'data' => $states], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['status' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    // ==========================================================================
    // جلب المدن (مع توحيد المقارنة COLLATE لمنع خطأ 1267)
    // ==========================================================================
    case 'get_cities':
        $state_id = isset($_GET['state_id']) ? intval($_GET['state_id']) : 0;
        $search   = isset($_GET['search']) ? trim($_GET['search']) : '';

        if ($state_id <= 0) {
            echo json_encode(['status' => false, 'message' => 'معرّف المحافظة (state_id) مطلوب.'], JSON_UNESCAPED_UNICODE);
            exit();
        }

        try {
            $sql = "SELECT `id`, `state_id`, `country_id`, 
                           `name` AS `name_en`,
                           IFNULL(NULLIF(`name_ar`, ''), `name`) AS `name_ar`,
                           CASE WHEN :lang = 'ar' THEN IFNULL(NULLIF(`name_ar`, ''), `name`) ELSE `name` END AS `display_name`,
                           `latitude`, `longitude`, `timezone` 
                    FROM `cities` 
                    WHERE `state_id` = :state_id AND `status` = 1";

            // إضافة الفحص وتخصيص COLLATE utf8mb4_unicode_ci صراحةً لحل التعارض
            if (!empty($search)) {
                $sql .= " AND (`name` COLLATE utf8mb4_unicode_ci LIKE :search 
                            OR `name_ar` COLLATE utf8mb4_unicode_ci LIKE :search)";
            }

            $sql .= " ORDER BY `name` ASC";

            $stmt = $pdo->prepare($sql);
            
            $params = [':state_id' => $state_id, ':lang' => $lang];
            if (!empty($search)) {
                $params[':search'] = '%' . $search . '%';
            }

            $stmt->execute($params);
            $cities = $stmt->fetchAll();

            echo json_encode(['status' => true, 'count' => count($cities), 'data' => $cities], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            echo json_encode(['status' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        echo json_encode(['status' => false, 'message' => 'الإجراء (action) غير صالح.'], JSON_UNESCAPED_UNICODE);
        break;
}
?>