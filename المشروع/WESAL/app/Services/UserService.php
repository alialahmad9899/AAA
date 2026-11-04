<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function store(array $data)
    {
        $collection = collect($data);
        $password = Hash::make($data['password']);
        $code = unique_code();
        $approved = get_setting('member_verification') == 1 ? 0 : 1;
        $verification_code = rand(100000, 999999);
        $referred_by = null;

        $data = $collection->merge(compact('password', 'code', 'approved', 'verification_code'))->toArray();

        if (addon_activation('referral_system')) {

            $reffered_user = User::where('code', '!=', null)->where('code', $data['referral_code'])->first();
            if ($reffered_user) {
                $referred_by['referred_by'] = $reffered_user->id;
                $data = array_merge($data, $referred_by);
            }
        }

        return User::create($data);
    }
}
