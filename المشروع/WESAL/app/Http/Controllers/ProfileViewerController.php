<?php

namespace App\Http\Controllers;

use App\Models\ProfileViewer;
use Illuminate\Http\Request;

class ProfileViewerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $profileViewers = ProfileViewer::where('user_id', auth()->user()->id)
                            ->whereIn("viewed_by", function ($query) {
                                $query->select('id')
                                    ->from('users')
                                    ->where('approved', '1')
                                    ->where('blocked', 0)
                                    ->where('deactivated', 0)
                                    ->where('permanently_delete', 0);
                            })->paginate(10);              
        return view('frontend.member.my_profile_viewers', compact('profileViewers'));
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
        //
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
