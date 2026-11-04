<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controller مخصص لجلب بيانات المواقع (الدول، المحافظات، المدن) لتطبيق وصال
 */
class LocationController extends Controller
{
    /**
     * جلب قائمة كافة الدول (250 دولة)
     * الرابط: GET /api/v1/countries
     */
    public function getCountries()
    {
        try {
            // جلب كافة السجلات من جدول countries مباشرة وبدون شروط حجب
            $countries = DB::table('countries')->get();

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب قائمة الدول بنجاح',
                'count'   => $countries->count(),
                'data'    => $countries
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ أثناء جلب الدول: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب المحافظات التابعة لدولة معينة
     * الرابط: GET /api/v1/countries/{country_id}/states
     */
    public function getStates($country_id)
    {
        try {
            // جلب المحافظات المربوطة بمعرف الدولة المحدد
            $states = DB::table('states')
                ->where('country_id', $country_id)
                ->get();

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب المحافظات بنجاح',
                'count'   => $states->count(),
                'data'    => $states
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ أثناء جلب المحافظات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب المدن التابعة لمحافظة معينة مع دعم إمكانية البحث
     * الرابط: GET /api/v1/states/{state_id}/cities
     */
    public function getCities(Request $request, $state_id)
    {
        try {
            $query = DB::table('cities')->where('state_id', $state_id);

            // في حال قام المستخدم بالبحث عن اسم مدينة معين داخل المحافظة
            if ($request->has('search') && !empty($request->search)) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            $cities = $query->get();

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب المدن بنجاح',
                'count'   => $cities->count(),
                'data'    => $cities
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ أثناء جلب المدن: ' . $e->getMessage()
            ], 500);
        }
    }
}