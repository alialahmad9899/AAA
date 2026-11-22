<?php

namespace Tests\\Unit;

use PHPUnit\\Framework\\TestCase;

class MemberListingFiltersPaginationTest extends TestCase
{
    public function test_member_listing_applies_empty_filter_results_without_broadening_results(): void
    {
        $path = __DIR__ . '/../../app/Http/Controllers/Api/MemberController.php';
        $source = file_get_contents($path);
        self::assertIsString($source);

        $start = strpos($source, 'public function member_listing(Request $request)');
        $end = strpos($source, 'public function ignored_user_list()', $start);
        self::assertNotFalse($start);
        self::assertNotFalse($end);
        $method = substr($source, $start, $end - $start);

        self::assertStringContainsString("$users_query->whereIn('id', $user_ids);", $method);
        self::assertStringNotContainsString('if (count($user_ids) > 0)', $method);
        self::assertStringContainsString("$users_query->whereIn('id', $user_ids);", $method);
    }

    public function test_religion_and_state_filters_use_matching_user_ids_and_search_is_paginated(): void
    {
        $path = __DIR__ . '/../../app/Http/Controllers/Api/MemberController.php';
        $source = file_get_contents($path);
        self::assertIsString($source);

        $start = strpos($source, 'public function member_listing(Request $request)');
        $end = strpos($source, 'public function ignored_user_list()', $start);
        self::assertNotFalse($start);
        self::assertNotFalse($end);
        $method = substr($source, $start, $end - $start);

        self::assertStringContainsString("where('religion_id', $religion_id)", $method);
        self::assertStringContainsString("where('state_id', $state_id)", $method);
        self::assertStringContainsString('$users_query->paginate($perPage', $method);
        self::assertStringContainsString("'has_more' => $membersPage->hasMorePages()", $method);
        self::assertStringContainsString("'per_page' => ['sometimes', 'integer', 'min:1', 'max:50']", $method);
    }
}
