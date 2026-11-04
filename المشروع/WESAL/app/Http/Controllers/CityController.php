<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Redirect;
use Validator;

class CityController extends Controller
{
    /**
     * البناء الأولي: تحديد صلاحيات الوصول وشروط التحقق من البيانات
     */
    public function __construct()
    {
        $this->middleware(['permission:show_member_languages'])->only('index');
        $this->middleware(['permission:edit_member_language'])->only('edit');
        $this->middleware(['permission:delete_member_language'])->only('destroy');

        // قواعد التحقق من الحقول عند الإضافة والتعديل
        $this->city_rules = [
            'name'     => ['required', 'max:255'],
            'name_ar'  => ['nullable', 'max:255'],
            'state_id' => ['required'],
        ];

        // رسائل الخطأ المترجمة عند عدم تحقق الشروط
        $this->city_messages = [
            'name.required'     => translate('City Name is required'),
            'name.max'          => translate('Max 255 characters'),
            'state_id.required' => translate('State is required'),
        ];
    }

    /**
     * عرض قائمة المدن في لوحة التحكم مع إمكانية البحث
     */
    public function index(Request $request)
    {
        $sort_search = null;
        $cities      = City::orderBy('id', 'asc');
        $state       = State::get();
        $countries   = Country::where('status', 1)->get();

        if ($request->has('search')) {
            $sort_search = $request->search;
            $cities      = $cities->where(function($query) use ($sort_search) {
                $query->where('name', 'like', '%'.$sort_search.'%')
                      ->orWhere('name_ar', 'like', '%'.$sort_search.'%');
            });
        }
        $cities = $cities->paginate(10);
        return view('admin.member_profile_attributes.cities.index', compact('cities', 'state', 'countries', 'sort_search'));
    }

    public function create()
    {
        //
    }

    /**
     * حفظ مدينة جديدة في قاعدة البيانات
     */
    public function store(Request $request)
    {
        $rules     = $this->city_rules;
        $messages  = $this->city_messages;
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            flash(translate('Sorry! Something went wrong'))->error();
            return Redirect::back()->withErrors($validator);
        }

        $city           = new City;
        $city->name     = $request->name;
        $city->name_ar  = $request->name_ar ?? $request->name;
        $city->state_id = $request->state_id;

        if ($city->save()) {
            flash(translate('New City has been added successfully'))->success();
            return redirect()->route('cities.index');
        } else {
            flash(translate('Sorry! Something went wrong.'))->error();
            return back();
        }
    }

    public function show($id)
    {
        //
    }

    /**
     * عرض نموذج تعديل المدينة
     */
    public function edit($id)
    {
        $city      = City::findOrFail(decrypt($id));
        $countries = Country::where('status', 1)->get();
        return view('admin.member_profile_attributes.cities.edit', compact('city', 'countries'));
    }

    /**
     * تحديث بيانات مدينة موجودة
     */
    public function update(Request $request, $id)
    {
        $rules     = $this->city_rules;
        $messages  = $this->city_messages;
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            flash(translate('Sorry! Something went wrong'))->error();
            return Redirect::back()->withErrors($validator);
        }

        $city           = City::findOrFail($id);
        $city->name     = $request->name;
        if ($request->has('name_ar')) {
            $city->name_ar = $request->name_ar;
        }
        $city->state_id = $request->state_id;

        if ($city->save()) {
            flash(translate('City info has been updated successfully'))->success();
            return redirect()->route('cities.index');
        } else {
            flash(translate('Sorry! Something went wrong.'))->error();
            return back();
        }
    }

    /**
     * حذف مدينة من قاعدة البيانات
     */
    public function destroy($id)
    {
        if (City::destroy($id)) {
            flash(translate('City info has been deleted successfully'))->success();
            return redirect()->route('cities.index');
        } else {
            flash(translate('Sorry! Something went wrong.'))->error();
            return back();
        }
    }

/**
     * جلب قائمة المدن لـ API فلاتر ولوحة التحكم مع استخراج الترجمة العربية بذكاء ودقة
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int|null  $state_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function get_cities_by_state(Request $request, $state_id = null)
    {
        // 1. استخراج رقم المحافظة الممرر من مسار API أو من المعلمات
        $targetStateId = $state_id ?? $request->route('id') ?? $request->route('state_id') ?? $request->state_id;

        // 2. تحديد لغة الطلب (الافتراضي هو العربية ar)
        $lang = $request->query('lang')
                ?? $request->header('lang') 
                ?? $request->header('app_language') 
                ?? $request->header('Accept-Language') 
                ?? 'ar';

        // 3. جلب جميع المدن المرتبطة بالمحافظة من قاعدة البيانات
        $cities = City::where('state_id', $targetStateId)->get();

        // 4. معالجة وتنسيق كل مدينة لجلب الترجمة الصحيحة من حقل translations أو name_ar
        $formattedCities = $cities->map(function ($city) use ($lang) {
            
            // الاسم الافتراضي بالإنجليزية
            $localizedName = $city->name;
            $extractedArName = null;

            // أ) محاولة قراءة الاسم العربي أولاً من حقل translations المترجم جاهزاً
            if (!empty($city->translations)) {
                $translations = is_array($city->translations) 
                    ? $city->translations 
                    : json_decode($city->translations, true);

                if (isset($translations['ar']) && !empty($translations['ar'])) {
                    $extractedArName = trim($translations['ar']);
                }
            }

            // ب) إذا لم نجد ترجمة داخل translations، نفحص حقل name_ar بشرط أن يحتوي على حروف عربية
            if (empty($extractedArName) && !empty($city->name_ar)) {
                if (preg_match('/[\x{0600}-\x{06FF}]/u', $city->name_ar)) {
                    $extractedArName = trim($city->name_ar);
                }
            }

            // ج) تطبيق الاسم العربي المترجم إذا كانت اللغة المطلوبة هي العربية
            if (str_contains(strtolower($lang), 'ar')) {
                if (!empty($extractedArName)) {
                    $localizedName = $extractedArName;
                }
            }

            // الاسم العربي النهائي لإرجاعه في حقل name_ar
            $finalAr = !empty($extractedArName) ? $extractedArName : $localizedName;

            return [
                'id'       => $city->id,
                'name'     => $localizedName,  // الاسم الذي يظهر في قائمة التطبيق
                'name_ar'  => $finalAr,         // الاسم العربي الصريح
                'state_id' => $city->state_id,
            ];
        });

        // 5. إرجاع النتيجة للتطبيق بصيغة JSON خفيفة وسريعة
        return response()->json([
            'result' => true,
            'cities' => $formattedCities
        ], 200);
    }
}