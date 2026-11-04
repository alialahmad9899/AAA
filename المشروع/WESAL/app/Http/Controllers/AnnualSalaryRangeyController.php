<?php

namespace App\Http\Controllers;

use App\Models\AnnualSalaryRange;
use Illuminate\Http\Request;
use Redirect;
use Validator;

class AnnualSalaryRangeyController extends Controller
{

    public function __construct()
    {
        // Staff Permissions
        $this->middleware(['permission:show_annual_salary_ranges'])->only('index');
        $this->middleware(['permission:edit_annual_salary_ranges'])->only('edit');
        $this->middleware(['permission:delete_annual_salary_ranges'])->only('destroy');

        $this->annual_salary_range_rules = [
            'min_salary' => ['required', 'numeric', 'max:100000000000'],
            'max_salary' => ['required', 'numeric', 'max:100000000000'],
        ];

        $this->annual_salary_range_messages = [
            'min_salary.required' => translate('Minimum Salary is required'),
            'min_salary.integer'  => translate('Minimum Salary should be Number Type'),
            'min_salary.max'      => translate('Minimum Salary max value is 100000000000'),

            'max_salary.required' => translate('Maximum Salary is required'),
            'max_salary.integer'  => translate('Maximum Salary should be Number Type'),
            'max_salary.max'      => translate('Maximum Salary max value is 100000000000'),
        ];
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $sort_search   = null;
        $annual_salaries = AnnualSalaryRange::orderBy('min_salary','asc')->paginate(10);
        return view('admin.member_profile_attributes.annual_salary_ranges.index', compact('annual_salaries'));
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
        $rules      = $this->annual_salary_range_rules;
        $messages   = $this->annual_salary_range_messages;
        $validator  = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            flash(translate('Sorry! Something went wrong'))->error();
            return Redirect::back()->withErrors($validator);
        }

        $annual_salary       = new AnnualSalaryRange();
        $annual_salary->min_salary = $request->min_salary;
        $annual_salary->max_salary = $request->max_salary;
        if($annual_salary->save()){
            flash(translate('New annual salary range has been added successfully'))->success();
        } else {
            flash(translate('Sorry! Something went wrong.'))->error();
        }
        return back();
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
        $annual_salary   = AnnualSalaryRange::findOrFail(decrypt($id));
        return view('admin.member_profile_attributes.annual_salary_ranges.edit', compact('annual_salary'));
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
        $rules      = $this->annual_salary_range_rules;
        $messages   = $this->annual_salary_range_messages;
        $validator  = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            flash(translate('Sorry! Something went wrong'))->error();
            return Redirect::back()->withErrors($validator);
        }

        $annual_salary       = AnnualSalaryRange::findOrFail($id);
        $annual_salary->min_salary = $request->min_salary;
        $annual_salary->max_salary = $request->max_salary;
        if($annual_salary->save()){
            flash(translate('Annual salary Range has been updated successfully'))->success();
        } else {
            flash(translate('Sorry! Something went wrong.'))->error();
        }
        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (AnnualSalaryRange::destroy($id)) {
            flash(translate('Annual salary range has been deleted successfully'))->success();
        } else {
            flash(translate('Sorry! Something went wrong.'))->error();
        }
        return back();
    }
}
