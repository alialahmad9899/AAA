<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Throwable;

class CityTranslatorController extends Controller
{
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

    public function translateAllCities(Request $request)
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->buildCorsResponse(['result' => true, 'message' => 'CORS Preflight OK']);
        }

        try {
            @set_time_limit(180);
            @ini_set('memory_limit', '512M');

            $limit = (int) $request->input('limit', 15);
            $force = $request->input('force') == '1' || $request->input('force') == 'true';
            $lastId = (int) $request->input('last_id', 0);

            $aiKey = env('GEMINI_API_KEY') ?? env('AI_TRANSLATE_KEY');

            if (empty($aiKey)) {
                return $this->buildCorsResponse([
                    'result' => false,
                    'message' => '⚠️ مفتاح GEMINI_API_KEY غير مضاف بملف .env.',
                    'remaining_cities' => City::count(),
                    'last_id' => $lastId,
                    'should_wait' => true,
                    'wait_time' => 5
                ]);
            }

            // بناء الاستعلام المحصّن لضمان التحرك إلى الأمام دائماً بناءً على last_id
            $query = City::query();

            if ($lastId > 0) {
                $query->where('id', '>', $lastId);
            }

            if (!$force) {
                $query->where(function ($q) {
                    $q->whereNull('name_ar')
                      ->orWhere('name_ar', '')
                      ->orWhereColumn('name_ar', 'name')
                      ->orWhereRaw("name_ar REGEXP '^[a-zA-Z]'");
                });
            }

            $query->orderBy('id', 'asc');
            $cities = $query->limit($limit)->get();
            $totalCities = City::count();

            if ($cities->isEmpty()) {
                return $this->buildCorsResponse([
                    'result' => true,
                    'message' => 'جميع المدن مترجمة ومحدثة بالكامل بالذكاء الاصطناعي! ✨',
                    'remaining_cities' => 0,
                    'total_cities' => $totalCities,
                    'last_id' => $lastId
                ]);
            }

            // تثبيت المؤشر الجديد على أقصى ID موجود في الدفعة الحالية
            $maxBatchId = $cities->max('id');

            $pendingTranslationCities = [];
            $translatedMap = [];

            foreach ($cities as $city) {
                $cleanedName = $this->cleanLatinName($city->name);
                $cleanKey = strtolower($cleanedName);

                if (isset($this->dictionary[$cleanKey])) {
                    $translatedMap[$city->id] = $this->dictionary[$cleanKey];
                } else {
                    $pendingTranslationCities[$city->id] = $cleanedName;
                }
            }

            $aiErrorDetails = '';
            $isRateLimited = false;

            if (!empty($pendingTranslationCities)) {
                $aiResults = $this->translateWithGeminiAI($pendingTranslationCities, trim($aiKey), $aiErrorDetails, $isRateLimited);
                foreach ($aiResults as $cityId => $arName) {
                    $translatedMap[$cityId] = $arName;
                }
            }

            $updatedCount = 0;

            DB::transaction(function () use ($cities, $translatedMap, &$updatedCount) {
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
                        $updatedCount++;
                    }
                }
            });

            // المؤشر الجديد ينتقل بثبات إلى نهاية الدفعة
            $newLastId = $maxBatchId;

            if ($force) {
                $remainingCount = City::where('id', '>', $newLastId)->count();
            } else {
                $remainingCount = City::where('id', '>', $newLastId)
                    ->where(function($q) {
                        $q->whereNull('name_ar')
                          ->orWhere('name_ar', '')
                          ->orWhereColumn('name_ar', 'name')
                          ->orWhereRaw("name_ar REGEXP '^[a-zA-Z]'");
                    })->count();
            }

            $shouldWait = ($isRateLimited);
            $waitTime = $isRateLimited ? 20 : 3;
            
            $message = "تمت معالجة دفعة المدن بنجاح (تحديث {$updatedCount} مدينة).";

            // تم تصحيح الخطأ هنا وإرجاع الاستجابة عبر دالة CORS مباشرة بشكل سليم تماماً
            return $this->buildCorsResponse([
                'result' => true,
                'message' => $message,
                'remaining_cities' => max(0, $remainingCount),
                'last_id' => $newLastId,
                'should_wait' => $shouldWait,
                'wait_time' => $waitTime,
                'total_cities' => $totalCities
            ]);

        } catch (Throwable $e) {
            return $this->buildCorsResponse([
                'result' => false,
                'message' => 'خطأ سيرفر: ' . $e->getMessage(),
                'remaining_cities' => 0,
                'last_id' => $lastId ?? 0,
                'should_wait' => true,
                'wait_time' => 10
            ]);
        }
    }

    private function buildCorsResponse(array $data)
    {
        return response()->json($data, 200)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, x-goog-api-key');
    }

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

    private function translateWithGeminiAI(array $pendingCities, string $apiKey, string &$aiErrorDetails = '', bool &$isRateLimited = false): array
    {
        $translated = [];
        $models = ['gemini-2.5-flash', 'gemini-3.5-flash', 'gemini-3.7-flash'];

        $cachedWorkingModel = Cache::get('working_gemini_model');
        if ($cachedWorkingModel && in_array($cachedWorkingModel, $models)) {
            $models = array_unique(array_merge([$cachedWorkingModel], $models));
        }

        foreach ($models as $model) {
            try {
                $prompt = "You are an official geographical translation expert. Translate the following city names from English/Latin to standard accurate Arabic. Return ONLY a valid JSON object mapping ID to Arabic name without markdown wrappers. Example format: {\"101\": \"دمشق\"}\n\nInput Data:\n" . json_encode($pendingCities, JSON_UNESCAPED_UNICODE);

                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

                $response = Http::timeout(20)
                    ->withHeaders([
                        'x-goog-api-key' => $apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post($url, [
                        'contents' => [
                            ['parts' => [['text' => $prompt]]]
                        ],
                        'generationConfig' => [
                            'response_mime_type' => 'application/json'
                        ]
                    ]);

                if ($response->successful()) {
                    $rawText = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
                    $cleanJson = trim(preg_replace('/^```json\s*|\s*```$/i', '', $rawText));
                    $decoded = json_decode($cleanJson, true);

                    if (is_array($decoded)) {
                        foreach ($decoded as $id => $arName) {
                            if (preg_match('/[\x{0600}-\x{06FF}]/u', $arName)) {
                                $translated[$id] = trim($arName);
                            }
                        }
                    }

                    if (!empty($translated)) {
                        Cache::put('working_gemini_model', $model, now()->addHours(2));

                        $aiErrorDetails = '';
                        $isRateLimited = false;
                        sleep(4); // مهلة تنظيمية آمنة لحماية الحصة المجانية

                        return $translated;
                    }
                } else {
                    $status = $response->status();
                    if ($status == 429) {
                        $isRateLimited = true;
                    }
                    $aiErrorDetails = "النموذج [{$model}] ارجع خطأ [{$status}]";
                    
                    if ($cachedWorkingModel === $model) {
                        Cache::forget('working_gemini_model');
                    }
                }
            } catch (Throwable $e) {
                $aiErrorDetails = "استثناء [{$model}]: " . $e->getMessage();
            }
        }

        return $translated;
    }
}