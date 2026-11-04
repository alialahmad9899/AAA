<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\Page\PageResource;
use App\Models\Page;

class CustomPageController extends Controller
{
    public function custom_page()
    {
        $pages = Page::whereIn('type', ['terms_conditions_page', 'privacy_policy_page', 'faq'])->get();
        return PageResource::collection($pages);
    }
}
