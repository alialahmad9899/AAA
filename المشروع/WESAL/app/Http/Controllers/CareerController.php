<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Career;
use Validator;
use Redirect;

class CareerController extends Controller
{
    public function __construct()
    {
        $this->rules = [
            'designation'  => [ 'nullable','max:255'],
            'company'      => [ 'nullable','max:255'],
            'career_end'   => [ 'nullable','numeric'],
        ];
    }

    public function index()
    {
        //
    }

    public function create(Request $request)
    {
        $member_id = $request->id;
        return view('frontend.member.profile.career.create', compact('member_id'));
    }

    public function store(Request $request)
    {
        try {
            $career              = new Career;
            
            // الاعتماد المباشر على السيرفر لجلب الـ ID لضمان عدم ضياعه
            $career->user_id     = auth()->id();
            
            // فرض البيانات لتخطي القيود الصارمة
            $career->designation = !empty($request->designation) ? $request->designation : 'غير محدد';
            $career->company     = !empty($request->company) ? $request->company : 'غير محدد';
            
            // تحويل السنة إلى رقم صحيح (int) لتجنب أخطاء الـ MySQL الصارمة
            $career->end         = !empty($request->career_end) ? (int)$request->career_end : (int)date('Y');
            
            $career->present     = 1;

            if($career->save()){
                return response()->json(['status' => 'success', 'message' => 'تم الإضافة بنجاح']);
            }

            return response()->json(['status' => 'error', 'message' => 'فشل الحفظ'], 500);

        } catch (\Exception $e) {
            // صيد الخطأ لإظهاره بدقة
            return response()->json(['status' => 'error', 'message' => 'خطأ بالسيرفر: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        //
    }

    public function edit(Request $request)
    {
        $career = Career::findOrFail($request->id);
        return view('frontend.member.profile.career.edit', compact('career'));
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

        $career = Career::findOrFail($id);
        
        if($request->has('designation')) { $career->designation = $request->designation; }
        if($request->has('company')) { $career->company = $request->company; }
        if($request->has('career_end')) { $career->end = $request->career_end; }

        if($career->save()){
            if ($request->ajax()) {
                return response()->json(['status' => 'success', 'message' => translate('Career Info has been updated successfully')]);
            }
            flash(translate('Career Info has been updated successfully'))->success();
            return back();
        }
        else {
            if ($request->ajax()) { return response()->json(['status' => 'error'], 500); }
            flash(translate('Sorry! Something went wrong.'))->error();
            return back();
        }
    }

    public function update_career_present_status(Request $request)
    {
        $career = Career::findOrFail($request->id);
        $career->present = $request->status;
        if ($career->save()) {
            $msg = $career->present == 1 ? translate('Enabled') : translate('Disabled');
            flash(translate($msg))->success();
            return 1;
        }
        return 0;
    }

    public function destroy($id)
    {
        if(Career::destroy($id))
        {
            flash(translate('Career info has been deleted successfully'))->success();
            return back();
        }
        else {
            flash(translate('Sorry! Something went wrong.'))->error();
            return back();
        }
    }
}