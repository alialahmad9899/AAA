<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lifestyle;
use Validator;
use Redirect;
use Auth;

class LifestyleController extends Controller
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
         // 1. جعل جميع الحقول nullable للسماح بالخانات الفارغة والمخفية
         $rules = [
             'diet'          => [ 'nullable', 'max:255'],
             'drink'         => [ 'nullable', 'max:255'],
             'smoke'         => [ 'nullable', 'max:255'],
             'living_with'   => [ 'nullable', 'max:255'],
         ];

         $messages = [
             'diet.max'              => translate('Max 255 characters'),
             'drink.max'             => translate('Max 255 characters'),
             'smoke.max'             => translate('Max 255 characters'),
             'living_with.max'       => translate('Max 255 characters'),
         ];

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

         // البحث عن سجل نمط الحياة باستخدام user_id
         $lifestyle = Lifestyle::where('user_id', $id)->first();
         if(empty($lifestyle)){
             $lifestyle             = new Lifestyle;
             $lifestyle->user_id    = $id;
         }

         // 2. التحديث الذكي: يتم تحديث الحقل فقط إذا كان موجوداً في الطلب (Request)
         // هذا يضمن عدم الكتابة فوق البيانات القديمة إذا كان الحقل مخفياً في الواجهة
         if($request->has('diet')) { $lifestyle->diet = $request->diet; }
         if($request->has('drink')) { $lifestyle->drink = $request->drink; }
         if($request->has('smoke')) { $lifestyle->smoke = $request->smoke; }
         if($request->has('living_with')) { $lifestyle->living_with = $request->living_with; }

         if($lifestyle->save()){
             // 3. دعم الرد لطلبات AJAX لضمان عمل الواجهة بشكل صحيح
             if ($request->ajax()) {
                 return response()->json([
                     'status' => 'success',
                     'message' => translate('Lifestyle info has been updated successfully')
                 ]);
             }

             flash(translate('Lifestyle info has been updated successfully'))->success();
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