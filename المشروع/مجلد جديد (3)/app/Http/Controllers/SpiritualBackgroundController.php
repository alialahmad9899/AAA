<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SpiritualBackground;
use Validator;
use Redirect;
use Auth;

class SpiritualBackgroundController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
     public function update(Request $request, $id)
     {
         // 1. تعديل القواعد لتصبح nullable (اختيارية) للسماح بالحقول المخفية أو الفارغة
         $this->rules = [
             'member_religion_id'   => [ 'nullable','max:255'],
             'member_caste_id'      => [ 'nullable','max:255'],
             'ethnicity'            => [ 'nullable','max:255'],
             'personal_value'       => [ 'nullable','max:255'],
             'community_value'      => [ 'nullable','max:255'],
         ];

         $this->messages = [
             'member_religion_id.max'        => translate('Max 255 characters'),
             'member_caste_id.max'           => translate('Max 255 characters'),
             'ethnicity.max'                 => translate('Max 255 characters'),
             'personal_value.max'            => translate('Max 255 characters'),
             'community_value.max'           => translate('Max 255 characters'),
         ];

         $rules = $this->rules;
         $messages = $this->messages;
         $validator = Validator::make($request->all(), $rules, $messages);

         if ($validator->fails()) {
             if ($request->ajax()) {
                 return response()->json([
                     'status' => 'error',
                     'message' => translate('Validation failed'),
                     'errors' => $validator->errors()->all()
                 ], 422);
             }
             flash(translate('Something went wrong'))->error();
             return Redirect::back()->withErrors($validator);
         }

         // 2. البحث عن السجل باستخدام user_id
         $spiritual_backgrounds = SpiritualBackground::where('user_id', $id)->first();
         if(empty($spiritual_backgrounds)){
             $spiritual_backgrounds          = new SpiritualBackground;
             $spiritual_backgrounds->user_id = $id;
         }

         // 3. التحديث الذكي: يتم تحديث الحقل فقط إذا كان موجوداً في الطلب (Request)
         // هذا يضمن بقاء البيانات القديمة إذا كان الحقل مخفياً في الواجهة
         if($request->has('member_religion_id')) { $spiritual_backgrounds->religion_id = $request->member_religion_id; }
         if($request->has('member_caste_id')) { $spiritual_backgrounds->caste_id = $request->member_caste_id; }
         if($request->has('member_sub_caste_id')) { $spiritual_backgrounds->sub_caste_id = $request->member_sub_caste_id; }
         if($request->has('ethnicity')) { $spiritual_backgrounds->ethnicity = $request->ethnicity; }
         if($request->has('personal_value')) { $spiritual_backgrounds->personal_value = $request->personal_value; }
         if($request->has('family_value_id')) { $spiritual_backgrounds->family_value_id = $request->family_value_id; }
         if($request->has('community_value')) { $spiritual_backgrounds->community_value = $request->community_value; }

         if($spiritual_backgrounds->save()){
             // 4. دعم الرد لطلبات AJAX لضمان ظهور رسالة النجاح الخضراء
             if ($request->ajax()) {
                 return response()->json([
                     'status' => 'success',
                     'message' => translate('Spiritual Background info has been updated successfully')
                 ]);
             }

             flash(translate('Spiritual Background info has been updated successfully'))->success();
             return back();
         }
         else {
             if ($request->ajax()) {
                 return response()->json(['status' => 'error', 'message' => translate('Sorry! Something went wrong.')], 500);
             }
             flash(translate('Sorry! Something went wrong.'))->error();
             return back();
         }
     }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}