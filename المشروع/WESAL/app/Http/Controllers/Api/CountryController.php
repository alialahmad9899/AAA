<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Profile\CountryResource;
use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    /**
     * جلب قائمة جميع الدول مع دعم التبديل الآلي بين اللغة العربية والإنجليزية
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function countries(Request $request)
    {
        // 1. التقاط لغة الطلب القادمة من ترويسات التطبيق أو البارامترات
        $lang = $request->header('Accept-Language')
             ?? $request->header('app_language')
             ?? $request->header('lang')
             ?? $request->get('lang')
             ?? app()->getLocale();

        $lang = str_contains(strtolower((string)$lang), 'en') ? 'en' : 'ar';

        // 2. ضبط لغة التطبيق لكي يتعرف عليها CountryResource أثناء تحويل البيانات
        app()->setLocale($lang);

        // 3. جلب كافة الدول دون استثناء أو حجب
        $countries = Country::all();

        return CountryResource::collection($countries);
    }
}