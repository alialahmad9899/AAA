<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * المتحكم الرئيسي للقوائم المنسدلة والبيانات الجغرافية والاجتماعية لتطبيق وصال
 * 
 * يتولى إدارة وجلب كافة البيانات المنسدلة لدعم الترجمة ثنائية اللغة (عربي / إنجليزي)،
 * مع دعم الأعلام الرسمية للدول والترجمة التلقائية وحفظها في قاعدة البيانات مع حماية كاملة من الأخطاء.
 */
class ProfileDropdownController extends Controller
{
    /**
     * دالة مساعدة معززة لالتقاط لغة التطبيق الحالية من جميع الترويسات والبارامترات الممكنة
     * 
     * @return string 'ar' للغة العربية، أو 'en' للغة الإنجليزية
     */
    private function getAppLanguage()
    {
        // فحص كافة الطرق التي قد يرسل بها تطبيق الهاتف المحمول رمز اللغة في الهيدر أو الرابط
        $lang = request()->header('Accept-Language') 
             ?? request()->header('app_language') 
             ?? request()->header('lang')
             ?? request()->header('locale')
             ?? request()->get('lang')
             ?? request()->get('locale')
             ?? app()->getLocale();

        $lang = strtolower(trim((string)$lang));

        // إذا احتوى رمز اللغة على 'en' (مثل en, en-US, en_US) يتم اعتماد اللغة الإنجليزية
        if (str_contains($lang, 'en')) {
            return 'en';
        }

        // افتراضياً تعتمد اللغة العربية
        return 'ar';
    }

    /**
     * دالة مساعدة لتوليد رابط علم الدولة عالي الدقة بحجم خفيف جداً عبر شبكة FlagCDN
     * 
     * @param string $code رمز الدولة ISO المكون من حرفين (مثل SY, SA, DE, US)
     * @return string رابط الصورة المباشر بصيغة PNG
     */
    private function getCountryFlagUrl($code)
    {
        if (empty($code)) {
            return '';
        }
        
        // تحويل رمز الدولة إلى أحرف صغيرة لمطابقة المعايير العالمية لشبكة FlagCDN
        $countryCode = strtolower(trim($code));
        
        return "https://flagcdn.com/w80/{$countryCode}.png";
    }

    /**
     * دالة ذكية للترجمة التلقائية الفورية للنصوص وحفظ الناتج في قاعدة البيانات بشكل دائم
     * 
     * @param string $table اسم الجدول في قاعدة البيانات (countries, states, cities)
     * @param int|string $id رقم العنصر المراد تحديث اسمه العربي
     * @param string $englishName النص باللغة الإنجليزية المراد ترجمته
     * @return string الاسم المترجم باللغة العربية
     */
    private function autoTranslateAndSave($table, $id, $englishName)
    {
        if (empty($englishName) || empty($id)) {
            return $englishName ?? '';
        }

        try {
            // إرسال طلب ترجمة فورية وسريعة عبر cURL إلى المحرك
            $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=ar&dt=t&q=" . urlencode($englishName);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3); // مهلة أقصاها 3 ثوانٍ لمنع تعليق الطلب
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            curl_close($ch);

            if ($response) {
                $result = json_decode($response, true);
                if (isset($result[0][0][0]) && !empty($result[0][0][0])) {
                    $translatedArabicName = trim($result[0][0][0]);

                    // 💾 حفظ الاسم العربي المترجم فوراً في قاعدة البيانات كي لا يتكرر طلب الترجمة مطلقاً
                    if (Schema::hasColumn($table, 'name_ar')) {
                        DB::table($table)->where('id', $id)->update([
                            'name_ar' => $translatedArabicName
                        ]);
                    }

                    return $translatedArabicName;
                }
            }
        } catch (\Throwable $e) {
            // في حال وجود خلل في الاتصال يتم إرجاع الاسم الإنجليزي كخيار احتياطي
        }

        return $englishName;
    }

    /**
     * قاموس موحد ومزدوج لترجمة المصطلحات والسمات والمدن عند غياب الاسم العربي من قاعدة البيانات
     * 
     * @param string $name النص المراد ترجمته
     * @param string $lang اللغة المطلوبة 'ar' أو 'en'
     * @return string النص بعد الترجمة
     */
    private function translateAttribute($name, $lang)
    {
        if (empty($name)) {
            return '';
        }

        $trimmedName = trim($name);

        // قاموس الترجمة المباشر (عربي -> إنجليزي)
        $arToEn = [
            // الأبراج الفلكية (Zodiac Signs)
            'الحمل' => 'Aries',
            'الثور' => 'Taurus',
            'الجوزاء' => 'Gemini',
            'السرطان' => 'Cancer',
            'الأسد' => 'Leo',
            'العذراء' => 'Virgo',
            'الميزان' => 'Libra',
            'العقرب' => 'Scorpio',
            'القوس' => 'Sagittarius',
            'الجدي' => 'Capricorn',
            'الدلو' => 'Aquarius',
            'الحوت' => 'Pisces',

            // ألوان العيون (Eye Colors)
            'أسود' => 'Black',
            'بني غامق' => 'Dark Brown',
            'بني فاتح' => 'Light Brown',
            'بني' => 'Brown',
            'عسلي' => 'Hazel',
            'أخضر' => 'Green',
            'أزرق' => 'Blue',
            'رمادي' => 'Grey',

            // ألوان الشعر (Hair Colors)
            'أشقر' => 'Blonde',
            'أحمر' => 'Red',
            'شيب / أبيض' => 'Grey / White',
            'صلع' => 'Bald',

            // لون البشرة (Skin Tones)
            'بيضاء' => 'White / Fair',
            'أبيض' => 'White / Fair',
            'حنطية' => 'Wheatish / Olive',
            'حنطي' => 'Wheatish / Olive',
            'سمراء' => 'Tan / Brown',
            'أسمر' => 'Tan / Brown',
            'سمراء داكنة' => 'Dark / Black',

            // خيارات الوشم (Tattoos)
            'لا يوجد وشم' => 'No Tattoos',
            'لا يوجد' => 'None',
            'وشم بسيط' => 'Small Tattoo',
            'وشم ناعم' => 'Small Tattoo',
            'وشم كبير' => 'Large Tattoo',
            'يرغب في إزالته' => 'Wants to remove it',

            // الحالة الاجتماعية (Marital Statuses)
            'أعزب' => 'Single',
            'عزباء' => 'Single',
            'مطلق' => 'Divorced',
            'مطلقة' => 'Divorced',
            'أرمل' => 'Widowed',
            'أرملة' => 'Widowed',
            'متزوج' => 'Married',
            'متزوجة' => 'Married',

            // صاحب الحساب (On Behalf)
            'شخصي' => 'Self',
            'الوالد' => 'Father',
            'الوالدة' => 'Mother',
            'الأخ' => 'Brother',
            'الأخت' => 'Sister',
            'صديق' => 'Friend',
            'قريب' => 'Relative',

            // المحافظات والمدن الرئيسية
            'دمشق' => 'Damascus',
            'ريف دمشق' => 'Rif Dimashq',
            'حلب' => 'Aleppo',
            'حمص' => 'Homs',
            'حماة' => 'Hama',
            'اللاذقية' => 'Latakia',
            'طرطوس' => 'Tartus',
            'إدلب' => 'Idlib',
            'درعا' => 'Daraa',
            'السويداء' => 'As-Suwayda',
            'دير الزور' => 'Deir ez-Zor',
            'الحسكة' => 'Al-Hasakah',
            'الرقة' => 'Raqqa',
            'القنيطرة' => 'Quneitra',
        ];

        // قاموس الترجمة العكسي (إنجليزي -> عربي) المباشر لمنع تضارب المفاتيح المكررة
        $enToAr = [
            'Aries' => 'الحمل',
            'Taurus' => 'الثور',
            'Gemini' => 'الجوزاء',
            'Cancer' => 'السرطان',
            'Leo' => 'الأسد',
            'Virgo' => 'العذراء',
            'Libra' => 'الميزان',
            'Scorpio' => 'العقرب',
            'Sagittarius' => 'القوس',
            'Capricorn' => 'الجدي',
            'Aquarius' => 'الدلو',
            'Pisces' => 'الحوت',
            'Black' => 'أسود',
            'Dark Brown' => 'بني غامق',
            'Light Brown' => 'بني فاتح',
            'Brown' => 'بني',
            'Hazel' => 'عسلي',
            'Green' => 'أخضر',
            'Blue' => 'أزرق',
            'Grey' => 'رمادي',
            'Blonde' => 'أشقر',
            'Red' => 'أحمر',
            'Bald' => 'صلع',
            'Single' => 'أعزب / عزباء',
            'Divorced' => 'مطلق / مطلقة',
            'Widowed' => 'أرمل / أرملة',
            'Married' => 'متزوج / متزوجة',
            'Self' => 'شخصي',
            'Father' => 'الوالد',
            'Mother' => 'الوالدة',
            'Brother' => 'الأخ',
            'Sister' => 'الأخت',
            'Friend' => 'صديق',
            'Relative' => 'قريب',
            'Damascus' => 'دمشق',
            'Rif Dimashq' => 'ريف دمشق',
            'Aleppo' => 'حلب',
            'Homs' => 'حمص',
            'Hama' => 'حماة',
            'Latakia' => 'اللاذقية',
            'Tartus' => 'طرطوس',
            'Idlib' => 'إدلب',
            'Daraa' => 'درعا',
            'As-Suwayda' => 'السويداء',
            'Deir ez-Zor' => 'دير الزور',
            'Al-Hasakah' => 'الحسكة',
            'Raqqa' => 'الرقة',
            'Quneitra' => 'القنيطرة',
        ];

        if ($lang === 'en') {
            return $arToEn[$trimmedName] ?? $trimmedName;
        }

        if ($lang === 'ar') {
            return $enToAr[$trimmedName] ?? $trimmedName;
        }

        return $trimmedName;
    }

    /**
     * دالة مساعدة لاختيار الاسم المناسب من عنصر قاعدة البيانات بناءً على اللغة المحددة مع التحقق الأمني وفحص حقل JSON
     * 
     * @param object|null $item العنصر القادم من قاعدة البيانات
     * @param string $lang رمز اللغة الحالية ('ar' أو 'en')
     * @param string $table اسم الجدول لارتباط الترجمة المباشرة
     * @return string الاسم النهائي المعروض للمستخدم
     */
    private function getItemName($item, $lang, $table = 'cities')
    {
        if (!$item || !is_object($item)) {
            return '';
        }

        $nameEn = isset($item->name) && !empty($item->name) ? trim((string)$item->name) : '';
        $nameAr = property_exists($item, 'name_ar') && !empty($item->name_ar) ? trim((string)$item->name_ar) : '';
        $itemId = isset($item->id) ? $item->id : null;

        // 1. استخراج الترجمة من حقل translations (JSON) إذا كان متوفراً
        $translationAr = '';
        if (isset($item->translations) && !empty($item->translations)) {
            $translations = is_array($item->translations) 
                ? $item->translations 
                : json_decode($item->translations, true);

            if (is_array($translations) && isset($translations['ar']) && !empty($translations['ar'])) {
                $translationAr = trim((string)$translations['ar']);
            }
        }

        // 2. إذا كانت لغة التطبيق هي الإنجليزية
        if ($lang === 'en') {
            if (!empty($nameEn)) {
                return $this->translateAttribute($nameEn, 'en');
            }
            return !empty($nameAr) ? $this->translateAttribute($nameAr, 'en') : ($translationAr ? $this->translateAttribute($translationAr, 'en') : '');
        } 

        // 3. إذا كانت لغة التطبيق هي العربية والاسم متوفر في قاعدة البيانات
        if (!empty($nameAr)) {
            return $nameAr;
        }

        if (!empty($translationAr)) {
            return $translationAr;
        }

        // 4. في حال كان الاسم العربي غير موجود، يتم ترجمته تلقائياً وحفظه فوراً في قاعدة البيانات
        if (!empty($nameEn)) {
            $translated = $this->translateAttribute($nameEn, 'ar');
            if ($translated !== $nameEn) {
                return $translated;
            }
            if ($itemId !== null && Schema::hasColumn($table, 'name_ar')) {
                return $this->autoTranslateAndSave($table, $itemId, $nameEn);
            }
        }

        return $nameEn;
    }

    /**
     * جلب قائمة جميع الدول مع دعم اللغة الحالية وأعلام الدول المباشرة
     */
    public function country_list()
    {
        try {
            $lang = $this->getAppLanguage();

            $countries = DB::table('countries')
                ->whereNotNull('id')
                ->get()
                ->map(function ($item) use ($lang) {
                    if (!$item) return null;
                    $code = $item->code ?? '';

                    return [
                        'id'       => $item->id,
                        'name'     => $this->getItemName($item, $lang, 'countries'),
                        'code'     => $code,
                        'flag_url' => $this->getCountryFlagUrl($code),
                    ];
                })
                ->filter()
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

            return response()->json([
                'result'    => true,
                'countries' => $countries
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'result'  => false,
                'message' => 'حدث خطأ أثناء جلب قائمة الدول: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب المحافظات / الولايات التابعة لدولة معينة مع دعم اللغة الحالية والترجمة
     * 
     * @param int $id رقم الدولة المُراد جلب محافظاتها
     */
    public function state_list($id)
    {
        try {
            $lang = $this->getAppLanguage();

            $states = DB::table('states')
                ->where('country_id', $id)
                ->whereNotNull('id')
                ->get()
                ->map(function ($item) use ($lang) {
                    if (!$item) return null;
                    return [
                        'id'   => $item->id,
                        'name' => $this->getItemName($item, $lang, 'states')
                    ];
                })
                ->filter()
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

            return response()->json([
                'result' => true,
                'states' => $states
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'result'  => false,
                'message' => 'حدث خطأ أثناء جلب قائمة المحافظات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب المدن والبلدات التابعة لمحافظة معينة بسرعة وسلاسة مع جلب ترجمات JSON وإرجاع name_ar صريحاً
     * 
     * @param int $id رقم المحافظة أو رقم الدولة
     */
    public function city_list($id)
    {
        try {
            $lang = $this->getAppLanguage();

            // فحص وجود جدول المدن لمنع خطأ توقف الاستعلام
            if (!Schema::hasTable('cities')) {
                return response()->json([
                    'result'  => false,
                    'message' => 'جدول المدن غير موجود في قاعدة البيانات',
                    'cities'  => []
                ], 200);
            }

            $hasNameAr       = Schema::hasColumn('cities', 'name_ar');
            $hasCountryId    = Schema::hasColumn('cities', 'country_id');
            $hasTranslations = Schema::hasColumn('cities', 'translations');

            $selectColumns = ['id', 'name'];
            if ($hasNameAr) {
                $selectColumns[] = 'name_ar';
            }
            if ($hasTranslations) {
                $selectColumns[] = 'translations';
            }

            // استعلام مباشر وسريع على جدول المدن عبر رقم المحافظة state_id
            $query = DB::table('cities')->where('state_id', $id);

            // خيار احتياطي: إذا لم يُعثر على مدن وكان المعرف الممرر هو رقم الدولة
            if ($hasCountryId && (clone $query)->count() === 0) {
                $query = DB::table('cities')->where('country_id', $id);
            }

            $cities = $query->select($selectColumns)
                ->whereNotNull('id')
                ->get()
                ->map(function ($item) use ($lang) {
                    if (!$item) return null;

                    $localizedName = $this->getItemName($item, $lang, 'cities');

                    // استخراج الترجمة من حقل JSON بشكل آمن
                    $translationAr = null;
                    if (isset($item->translations) && !empty($item->translations)) {
                        $trans = is_array($item->translations) 
                            ? $item->translations 
                            : json_decode($item->translations, true);
                        if (is_array($trans) && isset($trans['ar']) && !empty($trans['ar'])) {
                            $translationAr = trim((string)$trans['ar']);
                        }
                    }

                    $explicitNameAr = !empty($item->name_ar) ? $item->name_ar : ($translationAr ?? $localizedName);

                    return [
                        'id'      => $item->id,
                        'name'    => $localizedName,
                        'name_ar' => $explicitNameAr,
                    ];
                })
                ->filter()
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

            return response()->json([
                'result' => true,
                'cities' => $cities
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'result'  => false,
                'message' => 'خطأ بالسيرفر: ' . $e->getMessage(),
                'cities'  => []
            ], 200);
        }
    }

    /**
     * جلب قائمة الحالات الاجتماعية مع دعم اللغة
     */
    public function maritial_status()
    {
        try {
            $lang = $this->getAppLanguage();

            $data = DB::table('marital_statuses')
                ->whereNotNull('id')
                ->get()
                ->map(function ($item) use ($lang) {
                    if (!$item) return null;
                    return [
                        'id'   => $item->id,
                        'name' => $this->getItemName($item, $lang, 'marital_statuses')
                    ];
                })
                ->filter()
                ->values();

            return response()->json(['result' => true, 'data' => $data], 200);

        } catch (\Throwable $e) {
            return response()->json(['result' => false, 'data' => []], 200);
        }
    }

    /**
     * جلب قائمة أصحاب الحسابات (On Behalf) مع دعم اللغة
     */
    public function onbehalf_list()
    {
        try {
            $lang = $this->getAppLanguage();

            $data = DB::table('on_behalves')
                ->whereNotNull('id')
                ->get()
                ->map(function ($item) use ($lang) {
                    if (!$item) return null;
                    return [
                        'id'   => $item->id,
                        'name' => $this->getItemName($item, $lang, 'on_behalves')
                    ];
                })
                ->filter()
                ->values();

            return response()->json(['result' => true, 'onbehalf_list' => $data], 200);

        } catch (\Throwable $e) {
            return response()->json(['result' => false, 'onbehalf_list' => []], 200);
        }
    }

    /**
     * جلب قائمة اللغات المتاحة مع دعم اللغة
     */
    public function language_list()
    {
        try {
            $lang = $this->getAppLanguage();

            $data = DB::table('languages')
                ->whereNotNull('id')
                ->get()
                ->map(function ($item) use ($lang) {
                    if (!$item) return null;
                    return [
                        'id'   => $item->id,
                        'name' => $this->getItemName($item, $lang, 'languages')
                    ];
                })
                ->filter()
                ->values();

            return response()->json(['result' => true, 'languages' => $data], 200);

        } catch (\Throwable $e) {
            return response()->json(['result' => false, 'languages' => []], 200);
        }
    }

    /**
     * جلب قائمة الديانات مع دعم اللغة
     */
    public function religion_list()
    {
        try {
            $lang = $this->getAppLanguage();

            $data = DB::table('religions')
                ->whereNotNull('id')
                ->get()
                ->map(function ($item) use ($lang) {
                    if (!$item) return null;
                    return [
                        'id'   => $item->id,
                        'name' => $this->getItemName($item, $lang, 'religions')
                    ];
                })
                ->filter()
                ->values();

            return response()->json(['result' => true, 'religions' => $data], 200);

        } catch (\Throwable $e) {
            return response()->json(['result' => false, 'religions' => []], 200);
        }
    }

    /**
     * جلب قائمة الطوائف حسب رقم الديانة المحدد مع دعم اللغة
     * 
     * @param int $id رقم الديانة
     */
    public function caste_list($id)
    {
        try {
            $lang = $this->getAppLanguage();

            $data = DB::table('castes')
                ->where('religion_id', $id)
                ->whereNotNull('id')
                ->get()
                ->map(function ($item) use ($lang) {
                    if (!$item) return null;
                    return [
                        'id'   => $item->id,
                        'name' => $this->getItemName($item, $lang, 'castes')
                    ];
                })
                ->filter()
                ->values();

            return response()->json(['result' => true, 'castes' => $data], 200);

        } catch (\Throwable $e) {
            return response()->json(['result' => false, 'castes' => []], 200);
        }
    }

    /**
     * جلب قائمة المذاهب / الفرعيات المترابطة بالطائفة مع دعم اللغة
     * 
     * @param int $id رقم الطائفة
     */
    public function sub_caste_list($id)
    {
        try {
            $lang = $this->getAppLanguage();

            $data = DB::table('sub_castes')
                ->where('caste_id', $id)
                ->whereNotNull('id')
                ->get()
                ->map(function ($item) use ($lang) {
                    if (!$item) return null;
                    return [
                        'id'   => $item->id,
                        'name' => $this->getItemName($item, $lang, 'sub_castes')
                    ];
                })
                ->filter()
                ->values();

            return response()->json(['result' => true, 'sub_castes' => $data], 200);

        } catch (\Throwable $e) {
            return response()->json(['result' => false, 'sub_castes' => []], 200);
        }
    }

    /**
     * جلب قائمة القيم العائلية مع دعم اللغة
     */
    public function family_value_list()
    {
        try {
            $lang = $this->getAppLanguage();

            $data = DB::table('family_values')
                ->whereNotNull('id')
                ->get()
                ->map(function ($item) use ($lang) {
                    if (!$item) return null;
                    return [
                        'id'   => $item->id,
                        'name' => $this->getItemName($item, $lang, 'family_values')
                    ];
                })
                ->filter()
                ->values();

            return response()->json(['result' => true, 'family_values' => $data], 200);

        } catch (\Throwable $e) {
            return response()->json(['result' => false, 'family_values' => []], 200);
        }
    }

    /**
     * جلب جميع القوائم المنسدلة دفعة واحدة لتغذية واجهات التطبيق ومجموعات الاختيار مع دعم اللغة والأعلام
     */
    public function profile_dropdown()
    {
        try {
            $lang = $this->getAppLanguage();

            $countries = DB::table('countries')
                ->whereNotNull('id')
                ->get()
                ->map(function ($item) use ($lang) {
                    if (!$item) return null;
                    $code = $item->code ?? '';

                    return [
                        'id'       => $item->id,
                        'name'     => $this->getItemName($item, $lang, 'countries'),
                        'code'     => $code,
                        'flag_url' => $this->getCountryFlagUrl($code),
                    ];
                })
                ->filter()
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

            $marital_statuses = DB::table('marital_statuses')
                ->whereNotNull('id')
                ->get()
                ->map(function ($item) use ($lang) {
                    if (!$item) return null;
                    return [
                        'id'   => $item->id,
                        'name' => $this->getItemName($item, $lang, 'marital_statuses')
                    ];
                })
                ->filter()
                ->values();

            $onbehalves = DB::table('on_behalves')
                ->whereNotNull('id')
                ->get()
                ->map(function ($item) use ($lang) {
                    if (!$item) return null;
                    return [
                        'id'   => $item->id,
                        'name' => $this->getItemName($item, $lang, 'on_behalves')
                    ];
                })
                ->filter()
                ->values();

            $religions = DB::table('religions')
                ->whereNotNull('id')
                ->get()
                ->map(function ($item) use ($lang) {
                    if (!$item) return null;
                    return [
                        'id'   => $item->id,
                        'name' => $this->getItemName($item, $lang, 'religions')
                    ];
                })
                ->filter()
                ->values();

            $family_values = DB::table('family_values')
                ->whereNotNull('id')
                ->get()
                ->map(function ($item) use ($lang) {
                    if (!$item) return null;
                    return [
                        'id'   => $item->id,
                        'name' => $this->getItemName($item, $lang, 'family_values')
                    ];
                })
                ->filter()
                ->values();

            return response()->json([
                'result'          => true,
                'countries'       => $countries,
                'maritial_status' => $marital_statuses,
                'onbehalf_list'   => $onbehalves,
                'religions'       => $religions,
                'family_values'   => $family_values,
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'result'  => false,
                'message' => 'حدث خطأ أثناء جلب القوائم: ' . $e->getMessage()
            ], 500);
        }
    }
}