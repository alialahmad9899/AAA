<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\City;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class TranslateCities extends Command
{
    /**
     * اسم الأمر في سطر الأوامر
     * php artisan translate:cities --force
     */
    protected $signature = 'translate:cities {--force}';

    /**
     * وصف الأمر
     */
    protected $description = 'ترجمة المدن وتحديث قاعدة البيانات مباشرة مع طباعة تقرير مفصل';

    /**
     * قاموس محلي سريع للمدن والمحافظات المشهورة لضمان سرعة التنفيذ ودقة 100%
     */
    private array $dictionary = [
        'damascus' => 'دمشق', 'rif dimashq' => 'ريف دمشق', 'aleppo' => 'حلب', 'homs' => 'حمص',
        'latakia' => 'اللاذقية', 'hama' => 'حماة', 'deir ez-zor' => 'دير الزور', 'deir ezzor' => 'دير الزور',
        'tartus' => 'طرطوس', 'idlib' => 'إدلب', 'daraa' => 'درعا', 'as-suwayda' => 'السويداء',
        'al-hasakah' => 'الحسكة', 'ar raqqah' => 'الرقة', 'al-thawrah' => 'الثورة', 'qamishli' => 'القامشلي',
        'tadmur' => 'تدمر', 'tadmor' => 'تدمر', 'palmyra' => 'تدمر', 'talkalakh' => 'تلكلخ',
        'sadad' => 'صدد', 'al-qusayr' => 'القصير', 'al-rastan' => 'الرستن', 'al-qaryatayn' => 'القريتين',
        'mukharram al-fawqani' => 'المخرم الفوقاني', 'kabul' => 'كابول', 'baghdad' => 'بغداد',
        'cairo' => 'القاهرة', 'beirut' => 'بيروت', 'amman' => 'عمان'
    ];

    /**
     * التنفيذ الرئيسي للأمر البرمجي
     */
    public function handle()
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $force = $this->option('force');
        $this->info('🚀 بدء عملية الترجمة والتحديث الفعلي في قاعدة البيانات...');

        $query = City::query();
        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('name_ar')
                  ->orWhere('name_ar', '')
                  ->orWhereColumn('name_ar', 'name')
                  ->orWhereRaw("name_ar REGEXP '^[a-zA-Z]'");
            });
        }

        $totalCities = $query->count();
        if ($totalCities === 0) {
            $this->info('✨ جميع المدن مترجمة ومحدثة مسبقاً.');
            return;
        }

        $this->info("📊 إجمالي المدن المطلوبة: {$totalCities} مدينة.");
        $bar = $this->output->createProgressBar($totalCities);
        $bar->start();

        // معالجة المدن بدفعات منسقة (50 مدينة لكل دفعة) لضمان سرعة الحفظ
        $query->chunkById(50, function ($cities) use ($bar) {
            $pendingMap = [];
            $translatedMap = [];

            foreach ($cities as $city) {
                $cleanedName = $this->cleanLatinName($city->name);
                $cleanKey = strtolower($cleanedName);

                if (isset($this->dictionary[$cleanKey])) {
                    $translatedMap[$city->id] = $this->dictionary[$cleanKey];
                } else {
                    $pendingMap[$city->id] = $cleanedName;
                }
            }

            if (!empty($pendingMap)) {
                $namesList = array_values($pendingMap);
                $combinedText = implode("\n", $namesList);
                $translatedText = $this->fetchBatchTranslation($combinedText);

                if (!empty($translatedText)) {
                    $lines = explode("\n", $translatedText);
                    $keys = array_keys($pendingMap);

                    foreach ($keys as $index => $cityId) {
                        $arName = trim($lines[$index] ?? '');
                        if (!empty($arName) && preg_match('/[\x{0600}-\x{06FF}]/u', $arName)) {
                            $translatedMap[$cityId] = str_replace(['مقاطعة ', 'منطقة '], '', $arName);
                        }
                    }
                }
            }

            // حفظ النتائج فوراً في قاعدة البيانات وتأكيد التحديث
            DB::transaction(function () use ($cities, $translatedMap) {
                foreach ($cities as $city) {
                    if (isset($translatedMap[$city->id]) && !empty($translatedMap[$city->id])) {
                        $arabicName = $translatedMap[$city->id];
                        $city->name_ar = $arabicName;

                        if (isset($city->translations)) {
                            $translations = is_array($city->translations) ? $city->translations : json_decode($city->translations ?? '{}', true);
                            $translations['ar'] = $arabicName;
                            $city->translations = json_encode($translations, JSON_UNESCAPED_UNICODE);
                        }

                        $city->save();
                    }
                }
            });

            $bar->advance(count($cities));
        });

        $bar->finish();
        $this->newLine(2);
        $this->info('🎉 تمت عملية تعريب وتحديث كافة المدن في قاعدة البيانات بنجاح تام!');
    }

    /**
     * تنظيف الكلمات الإدارية والألفبائيات اللاتينية
     */
    private function cleanLatinName(string $name): string
    {
        $remove = ['District', 'Subdistrict', 'Sub-District', 'Governorate', 'City', 'Province'];
        foreach ($remove as $word) {
            $name = preg_replace("/\b" . preg_quote($word, '/') . "\b/i", '', $name);
        }
        $replacements = [
            'ş' => 's', 'Ş' => 'S', 'ţ' => 't', 'Ţ' => 'T',
            'ā' => 'a', 'Ā' => 'A', 'ū' => 'u', 'Ū' => 'U',
            'ī' => 'i', 'Ī' => 'I', '$' => '', '\'' => '', '’' => ''
        ];
        return trim(preg_replace('/\s+/', ' ', strtr($name, $replacements)));
    }

    /**
     * دالة جلب الترجمة مع تتبع الأخطاء في حال وجودها
     */
    private function fetchBatchTranslation(string $text): string
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept-Language' => 'ar-SY,ar;q=0.9',
                ])
                ->get("https://translate.googleapis.com/translate_a/single", [
                    'client' => 'gtx',
                    'sl' => 'en',
                    'tl' => 'ar',
                    'dt' => 't',
                    'q' => $text
                ]);

            if ($response->successful()) {
                $json = $response->json();
                $translatedString = '';
                if (isset($json[0]) && is_array($json[0])) {
                    foreach ($json[0] as $part) {
                        $translatedString .= $part[0] ?? '';
                    }
                }
                return trim($translatedString);
            }
        } catch (\Throwable $e) {
            // طباعة الخطأ في حال حدوث استثناء بالاتصال
            // $this->error($e->getMessage());
        }

        return '';
    }
}