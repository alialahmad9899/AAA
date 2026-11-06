<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Family;
use Validator;
use Redirect;

class FamilyController extends Controller
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
        // dd($request->all());
        $this->rules = [
            'father'                => [ 'max:255'],
            'father_occupation'     => [ 'max:255'],
            'mother'                => [ 'max:255'],
            'mother_occupation'     => [ 'max:255'],
            'sibling'               => [ 'max:255'],
            'no_of_brothers'        => [ 'numeric', 'max:30'],
            'no_of_sisters'         => [ 'numeric', 'max:30'],
            'about_parents'         => [ 'max:65535'],
            'about_siblings'        => [ 'max:65535'],
            'about_relatives'       => [ 'max:65535'],
            
        ];
        $this->messages = [
            'father.max'                => translate('Max 255 characters'),
            'father_occupation.max'     => translate('Max 255 characters'),
            'mother.max'                => translate('Max 255 characters'),
            'mother_occupation.max'     => translate('Max 255 characters'),
            'sibling.max'               => translate('Max 255 characters'),
            'no_of_brothers.numeric'    => translate('No. of brothers should be number type'),
            'no_of_brothers.max'        => translate('Max 30 characters'),
            'no_of_sisters.numeric'     => translate('No. of sisters should be number type'),
            'no_of_sisters.max'         => translate('Max 30 characters'),
            'about_parents.max'         => translate('Max 65535 characters'),
            'about_siblings.max'        => translate('Max 65535 characters'),
            'about_relatives.max'       => translate('Max 65535 characters'),
        ];

        $rules = $this->rules;
        $messages = $this->messages;
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            flash(translate('Something went wrong'))->error();
            return Redirect::back()->withErrors($validator);
        }
        
        $family = Family::where('user_id', $id)->first();
        if(empty($family)){
            $family           = new Family;
            $family->user_id  = $id;
        }

        $family->father                 = $request->father;
        $family->father_occupation      = $request->father_occupation;
        $family->mother                 = $request->mother;
        $family->mother_occupation      = $request->mother_occupation;
        $family->sibling                = $request->sibling;
        $family->no_of_sisters          = $request->no_of_sisters;
        $family->no_of_brothers         = $request->no_of_brothers;
        $family->about_parents          = $request->about_parents;
        $family->about_siblings         = $request->about_siblings;
        $family->about_relatives        = $request->about_relatives;

        if($family->save()){
            flash(translate('Family info has been updated successfully'))->success();
            return back();
        }
        else {
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
