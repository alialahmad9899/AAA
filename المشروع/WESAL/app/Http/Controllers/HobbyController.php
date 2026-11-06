<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hobby;
use Validator;
use Redirect;

class HobbyController extends Controller
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
         // 1. إضافة تحقق مرن لضمان مرور البيانات بسلاسة
         $validator = Validator::make($request->all(), [
             'hobbies'            => ['nullable', 'string'],
             'interests'          => ['nullable', 'string'],
             'music'              => ['nullable', 'string'],
             'books'              => ['nullable', 'string'],
             'movies'             => ['nullable', 'string'],
             'tv_shows'           => ['nullable', 'string'],
             'sports'             => ['nullable', 'string'],
             'fitness_activities' => ['nullable', 'string'],
             'cuisines'           => ['nullable', 'string'],
             'dress_styles'       => ['nullable', 'string'],
         ]);

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

         $hobbies = Hobby::where('user_id', $id)->first();
         if(empty($hobbies)){
             $hobbies = new Hobby;
             $hobbies->user_id = $id;
         }

         // 2. التحديث الذكي: استخدام has لعدم الكتابة فوق البيانات القديمة بقيم فارغة
         if($request->has('hobbies')) { $hobbies->hobbies = $request->hobbies; }
         if($request->has('interests')) { $hobbies->interests = $request->interests; }
         if($request->has('music')) { $hobbies->music = $request->music; }
         if($request->has('books')) { $hobbies->books = $request->books; }
         if($request->has('movies')) { $hobbies->movies = $request->movies; }
         if($request->has('tv_shows')) { $hobbies->tv_shows = $request->tv_shows; }
         if($request->has('sports')) { $hobbies->sports = $request->sports; }
         if($request->has('fitness_activities')) { $hobbies->fitness_activities = $request->fitness_activities; }
         if($request->has('cuisines')) { $hobbies->cuisines = $request->cuisines; }
         if($request->has('dress_styles')) { $hobbies->dress_styles = $request->dress_styles; }

         if($hobbies->save()){
             // 3. دعم طلبات الـ AJAX لظهور الإشعار الأخضر
             if ($request->ajax()) {
                 return response()->json([
                     'status' => 'success',
                     'message' => translate('Hobby and Interests info has been updated successfully')
                 ]);
             }
             flash(translate('Hobby and Interests info has been updated successfully'))->success();
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