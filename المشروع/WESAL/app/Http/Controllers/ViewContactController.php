<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ViewContact;
use App\Models\Member;
use Auth;

class ViewContactController extends Controller
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
        $request->validate(['id' => ['required', 'integer', 'exists:users,id']]);
        $viewerId = (int) Auth::id();
        if (!$viewerId || $viewerId === (int) $request->id) {
            return false;
        }

        $created = SubscriptionUtility::withQuota(
            $viewerId,
            'remaining_contact_view',
            function ($user, $member) use ($request, $viewerId) {
                if (ViewContact::where('user_id', $request->id)->where('viewed_by', $viewerId)->exists()) {
                    return null;
                }
                $contactRequest = new ViewContact();
                $contactRequest->user_id = (int) $request->id;
                $contactRequest->viewed_by = $viewerId;
                $contactRequest->save();
                return $contactRequest;
            }
        );

        return (bool) $created;
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
