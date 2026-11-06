<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Address;
use Validator;
use Redirect;

class AddressController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
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
         $address_type = $request->address_type;
         $rules = [];
         $messages = [];

         // 1. جعل جميع الحقول اختيارية (nullable) حسب نوع العنوان
         if($address_type == 'present'){
             $rules = [
                 'present_country_id'   => [ 'nullable'],
                 'present_state_id'     => [ 'nullable'],
                 'present_city_id'      => [ 'nullable'],
                 'present_postal_code'  => [ 'nullable'],
             ];
         }
         elseif($address_type == 'permanent'){
             $rules = [
                 'permanent_country_id'   => [ 'nullable'],
                 'permanent_state_id'     => [ 'nullable'],
                 'permanent_city_id'      => [ 'nullable'],
                 'permanent_postal_code'  => [ 'nullable'],
             ];
         }

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

         $address = Address::where('user_id', $id)->where('type', $address_type)->first();
         if(empty($address)){
             $address = new Address;
             $address->user_id = $id;
         }

         // 2. التحديث الذكي: يتم تحديث الحقل فقط إذا كان موجوداً في الـ Request
         if($address_type == 'present'){
             if($request->has('present_country_id')) { $address->country_id = $request->present_country_id; }
             if($request->has('present_state_id')) { $address->state_id = $request->present_state_id; }
             if($request->has('present_city_id')) { $address->city_id = $request->present_city_id; }
             if($request->has('present_postal_code')) { $address->postal_code = $request->present_postal_code; }
         }
         elseif($address_type == 'permanent'){
             if($request->has('permanent_country_id')) { $address->country_id = $request->permanent_country_id; }
             if($request->has('permanent_state_id')) { $address->state_id = $request->permanent_state_id; }
             if($request->has('permanent_city_id')) { $address->city_id = $request->permanent_city_id; }
             if($request->has('permanent_postal_code')) { $address->postal_code = $request->permanent_postal_code; }
         }
         
         $address->type = $address_type;

         if($address->save()){
             // 3. دعم الرد عبر AJAX للإشعار الأخضر
             if ($request->ajax()) {
                 return response()->json([
                     'status' => 'success',
                     'message' => translate('Address info has been updated successfully')
                 ]);
             }
             flash(translate('Address info has been updated successfully'))->success();
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