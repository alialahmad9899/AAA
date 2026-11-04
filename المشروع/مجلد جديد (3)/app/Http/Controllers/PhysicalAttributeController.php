<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PhysicalAttribute;
use Validator;
use Redirect;
use Auth;

class PhysicalAttributeController extends Controller
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
     * التحديث الذكي للمواصفات والسمات الجسدية
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // 1. تحويل كافة الحقول إلى nullable للسماح بالحقول المخفية أو الفارغة
        $rules = [
            'height'       => ['nullable', 'numeric'],
            'weight'       => ['nullable', 'numeric'],
            'eye_color'    => ['nullable', 'max:50'],
            'hair_color'   => ['nullable', 'max:50'],
            'complexion'   => ['nullable', 'max:50'],
            'blood_group'  => ['nullable', 'max:3'],
            'body_type'    => ['nullable', 'max:50'],
            'body_art'     => ['nullable', 'max:50'],
            'disability'   => ['nullable', 'max:255'],
        ];

        $messages = [
            'height.numeric'       => translate('It should be numeric type'),
            'weight.numeric'       => translate('It should be numeric type'),
            'eye_color.max'        => translate('Max 50 characters'),
            'hair_color.max'       => translate('Max 50 characters'),
            'complexion.max'       => translate('Max 50 characters'),
            'blood_group.max'      => translate('Max 3 characters'),
            'body_type.max'        => translate('Max 50 characters'),
            'body_art.max'         => translate('Max 50 characters'),
            'disability.max'       => translate('Max 255 characters'),
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => translate('Validation failed'),
                    'errors'  => $validator->errors()->all()
                ], 422);
            }
            flash(translate('Something went wrong'))->error();
            return Redirect::back()->withErrors($validator);
        }

        // جلب سجل المستخدم لضمان الأمان
        $physical_attribute = PhysicalAttribute::where('user_id', $id)->first();
        if (empty($physical_attribute)) {
            $physical_attribute = new PhysicalAttribute;
            $physical_attribute->user_id = $id;
        }

        // 2. التحديث الذكي: نحدث فقط الحقول التي أرسلها المستخدم فعلياً في الطلب
        if ($request->has('height')) { $physical_attribute->height = $request->height; }
        if ($request->has('weight')) { $physical_attribute->weight = $request->weight; }
        if ($request->has('eye_color')) { $physical_attribute->eye_color = $request->eye_color; }
        if ($request->has('hair_color')) { $physical_attribute->hair_color = $request->hair_color; }
        if ($request->has('complexion')) { $physical_attribute->complexion = $request->complexion; }
        if ($request->has('blood_group')) { $physical_attribute->blood_group = $request->blood_group; }
        if ($request->has('body_type')) { $physical_attribute->body_type = $request->body_type; }
        if ($request->has('body_art')) { $physical_attribute->body_art = $request->body_art; }
        if ($request->has('disability')) { $physical_attribute->disability = $request->disability; }

        if ($physical_attribute->save()) {
            // 3. دعم الرد لطلبات AJAX وواجهات التطبيق
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => translate('Physical Attribute Info has been updated successfully')
                ]);
            }

            flash(translate('Physical Attribute Info has been updated successfully'))->success();
            return back();
        } else {
            if ($request->ajax() || $request->wantsJson()) {
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