<?php

namespace App\Http\Controllers;

use App\Models\ManualPaymentMethod;
use Illuminate\Http\Request;

class ManualPaymentMethodController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $manual_payment_methods = ManualPaymentMethod::latest()->get();
        return view('admin.manual_payment_methods.index', compact('manual_payment_methods'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.manual_payment_methods.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $manual_payment_method = new ManualPaymentMethod;
        $manual_payment_method->type = $request->type;
        $manual_payment_method->photo = $request->photo;
        $manual_payment_method->heading = $request->heading;
        $manual_payment_method->description = $request->description;

        if ($request->type == 'bank_payment') {
            $banks_informations = array();
            for ($i = 0; $i < count($request->bank_name); $i++) {
                $item = array();
                $item['bank_name'] = $request->bank_name[$i];
                $item['account_name'] = $request->account_name[$i];
                $item['account_number'] = $request->account_number[$i];
                $item['routing_number'] = $request->routing_number[$i];
                array_push($banks_informations, $item);
            }

            $manual_payment_method->bank_info = json_encode($banks_informations);
        }

        $manual_payment_method->save();
        flash(translate('Method has been inserted successfully'))->success();
        return redirect()->route('manual_payment_methods.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ManualPaymentMethod  $manualPaymentMethod
     * @return \Illuminate\Http\Response
     */
    public function show(ManualPaymentMethod $manualPaymentMethod)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ManualPaymentMethod  $manualPaymentMethod
     * @return \Illuminate\Http\Response
     */
    public function edit(ManualPaymentMethod $manualPaymentMethod)
    {
        $manual_payment_method = ManualPaymentMethod::find(($manualPaymentMethod))->first();
        return view('admin.manual_payment_methods.edit', compact('manual_payment_method'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ManualPaymentMethod  $manualPaymentMethod
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ManualPaymentMethod $manualPaymentMethod)
    {
        $manual_payment_method = ManualPaymentMethod::find($manualPaymentMethod)->first();
        $manual_payment_method->type = $request->type;
        $manual_payment_method->heading = $request->heading;
        $manual_payment_method->description = $request->description;

        if ($request->type == 'bank_payment') {
            $banks_informations = array();
            for ($i = 0; $i < count($request->bank_name); $i++) {
                $item = array();
                $item['bank_name'] = $request->bank_name[$i];
                $item['account_name'] = $request->account_name[$i];
                $item['account_number'] = $request->account_number[$i];
                $item['routing_number'] = $request->routing_number[$i];
                array_push($banks_informations, $item);
            }

            $manual_payment_method->bank_info = json_encode($banks_informations);
        }
        $manual_payment_method->photo = $request->photo;
        $manual_payment_method->save();
        flash(translate('Method has been updated successfully'))->success();
        return redirect()->route('manual_payment_methods.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ManualPaymentMethod  $manualPaymentMethod
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (ManualPaymentMethod::destroy($id)) {
            flash(translate('Method has been deleted successfully'))->success();
        } else {
            flash(translate('Something went wrong'))->error();
        }
        return redirect()->route('manual_payment_methods.index');
    }
}
