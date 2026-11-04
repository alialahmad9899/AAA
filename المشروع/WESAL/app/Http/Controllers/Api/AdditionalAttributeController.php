<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AdditionalAttributeResource;
use App\Models\AdditionalAttribute;
use App\Models\AdditionalMemberInfo;
use Illuminate\Http\Request;

class AdditionalAttributeController extends Controller
{
    public function index() {
        $additionalAttributes = AdditionalAttribute::where('status',1)->get();
        return  AdditionalAttributeResource::collection($additionalAttributes)->additional([
            'result' => true
        ]);
    }

    public function additional_member_info_update(Request $request) {
        if($request->attributes != null){
            foreach($request['attributes'] as $attribute){
                AdditionalMemberInfo::UpdateOrCreate([
                    'user_id' => $request->member_id,
                    'additional_attribute_id' => $attribute,
                ], [
                    'user_id' => $request->member_id,
                    'additional_attribute_id' => $attribute,
                    'value'  => $request[$attribute]
                ]);
            }
        }
        return $this->success_message('Member Additional Info has been updated successfully');
    }
}
