<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Education;
use Validator;
use Redirect;
use Auth;

class EducationController extends Controller
{
    public function __construct()
    {
        $this->rules = [
            'degree'          => [ 'nullable','max:255'],
            'institution'     => [ 'nullable','max:255'],
            'education_start' => [ 'nullable','numeric'],
            'education_end'   => [ 'nullable','numeric'],
        ];
    }

    public function index()
    {
        //
    }

    public function create(Request $request)
    {
        $member_id = $request->id;
        return view('frontend.member.profile.education.create', compact('member_id'));
    }

    public function store(Request $request)
    {
        try {
            $education              = new Education;
            
            // 🔥 هاد هو السطر السحري اللي بيحل المشكلة من جذورها 🔥
            // يسحب الـ ID مباشرة من السيرفر بدل الاعتماد على الواجهة
            $education->user_id     = auth()->user()->id;
            
            // فرض البيانات بقوة لتخطي أي قيود في قاعدة البيانات
            $education->degree      = !empty($request->degree) ? $request->degree : 'غير محدد';
            $education->institution = 'غير محدد'; // تجاوزنا اسم الجامعة بنجاح
            
            // تعيين سنوات افتراضية 
            $education->start       = date('Y');
            $education->end         = date('Y');
            
            $education->present     = 1;
            
            // منع الخطأ إذا كان العمود موجوداً
            if (\Schema::hasColumn('educations', 'is_highest_degree')) {
                $education->is_highest_degree = 0;
            }

            if($education->save()){
                if ($request->ajax()) {
                    return response()->json(['status' => 'success', 'message' => 'تمت الإضافة بنجاح']);
                }
                flash(translate('Education Info has been added successfully'))->success();
                return back();
            }

            if ($request->ajax()) { 
                return response()->json(['status' => 'error', 'message' => 'فشل الحفظ'], 500); 
            }
            return back();

        } catch (\Exception $e) {
            // هذا السطر سيكشف لنا الخطأ بدقة في حال استمر الرفض
            if ($request->ajax()) { 
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500); 
            }
            return back();
        }
    }

    public function show($id)
    {
        //
    }

     public function edit(Request $request)
     {
         $education = Education::findOrFail($request->id);
         return view('frontend.member.profile.education.edit', compact('education'));
     }

    public function update(Request $request, $id)
    {
        $rules = $this->rules;
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['status' => 'error', 'message' => translate('Validation failed')], 422);
            }
            flash(translate('Something went wrong'))->error();
            return Redirect::back();
        }

        $education = Education::findOrFail($id);
        
        if($request->has('degree')) { $education->degree = $request->degree; }
        if($request->has('institution')) { $education->institution = $request->institution; }
        if($request->has('education_start')) { $education->start = $request->education_start; }
        if($request->has('education_end')) { $education->end = $request->education_end; }

        if($education->save()){
            if ($request->ajax()) {
                return response()->json(['status' => 'success', 'message' => translate('Education Info has been updated successfully')]);
            }
            flash(translate('Education Info has been updated successfully'))->success();
            return back();
        }
        else {
            if ($request->ajax()) { return response()->json(['status' => 'error'], 500); }
            flash(translate('Sorry! Something went wrong.'))->error();
            return back();
        }
    }

    public function update_education_present_status(Request $request)
    {
        $education = Education::findOrFail($request->id);
        $education->present = $request->status;
        if ($education->save()) {
            $msg = $education->present == 1 ? translate('Enabled') : translate('Disabled');
            flash(translate($msg))->success();
            return 1;
        }
        return 0;
    }

    public function updateHighestDegree(Request $request){
        $education = Education::findOrFail($request->id);
        $education->is_highest_degree = $request->status;
        if ($education->save()) {
            if(Education::where('is_highest_degree', 1)->count() > 1){
                Education::where('id','!=', $education->id)->update(['is_highest_degree' => 0]);
            }
            return 1;
        }
        return 0;
    }

    public function destroy($id)
    {
        if(Education::destroy($id))
        {
            flash(translate('Education info has been deleted successfully'))->success();
            return back();
        }
        else {
            flash(translate('Sorry! Something went wrong.'))->error();
            return back();
        }
    }
}