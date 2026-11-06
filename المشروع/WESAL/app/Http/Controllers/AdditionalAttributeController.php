<?php

namespace App\Http\Controllers;

use App\Models\AdditionalAttribute;
use Illuminate\Http\Request;
use Redirect;
use Validator;

class AdditionalAttributeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:show_additional_profile_attributes'])->only('index');

        $this->rules = [
            'title' => ['required','max:255'],
        ];

        $this->messages = [
            'title.required'             => translate('Name is required'),
            'title.max'                  => translate('Max 255 characters'),
        ];
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $additionalAttributes = AdditionalAttribute::get();
        return view('admin.member_profile_attributes.additioal_attributes.index', compact('additionalAttributes'));
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
        $rules      = $this->rules;
        $messages   = $this->messages;
        $validator  = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            flash(translate('Sorry! Something went wrong'))->error();
            return Redirect::back()->withErrors($validator);
        }

        $attribute       = new AdditionalAttribute();
        $attribute->title = $request->title;
        if($attribute->save()){
            flash(translate('New Attribute has been added successfully'))->success();
            return back();
        } else {
            flash(translate('Sorry! Something went wrong.'))->error();
            return back();
        }
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
    public function update(Request $request)
    { 
        $attribute       = AdditionalAttribute::findOrFail($request->id);
        $attribute->title = $request->title;
        $attribute->status = $request->status;
        if($attribute->save()){
            return 1;
        }
        return 0;
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
