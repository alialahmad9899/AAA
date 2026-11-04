<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Attitude;
use Validator;
use Redirect;
use Auth;

class AttitudeController extends Controller
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
         // 1. جعل جميع الحقول اختيارية (nullable)
         $rules = [
             'affection'         => [ 'nullable', 'max:255'],
             'humor'             => [ 'nullable', 'max:255'],
             'political_views'   => [ 'nullable', 'string'],
             'religious_service' => [ 'nullable', 'string'],
         ];

         $messages = [
             'affection.max'      => translate('Max 255 characters'),
             'humor.max'          => translate('Max 255 characters'),
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

         $attitude = Attitude::where('user_id', $id)->first();
         if(empty($attitude)){
             $attitude = new Attitude;
             $attitude->user_id = $id;
         }

         // 2. التحديث الذكي: نحدث فقط إذا أرسل المستخدم قيمة في الطلب
         if($request->has('affection')) { $attitude->affection = $request->affection; }
         if($request->has('humor')) { $attitude->humor = $request->humor; }
         if($request->has('political_views')) { $attitude->political_views = $request->political_views; }
         if($request->has('religious_service')) { $attitude->religious_service = $request->religious_service; }

         if($attitude->save()){
             // 3. الرد بصيغة JSON لنجاح الـ AJAX
             if ($request->ajax()) {
                 return response()->json([
                     'status' => 'success',
                     'message' => translate('Personal Attitude and Behavior Info has been updated successfully')
                 ]);
             }
             flash(translate('Personal Attitude and Behavior Info has been updated successfully'))->success();
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