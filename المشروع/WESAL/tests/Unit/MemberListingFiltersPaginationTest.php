<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MemberListingFiltersPaginationTest extends TestCase
{
    private function listingMethod(): string
    {
        $path = __DIR__ . '/../../app/Http/Controllers/Api/MemberController.php';
        $source = file_get_contents($path);
        self::assertIsString($source);
        $start = strpos($source, 'public function member_listing(Request $request)');
        $end = strpos($source, 'public function ignored_user_list()', $start);
        self::assertNotFalse($start);
        self::assertNotFalse($end);
        return substr($source, $start, $end - $start);
    }

    public function test_member_listing_applies_empty_filter_results_without_broadening_results(): void
    {
        $method = $this->listingMethod();
        $expectedSubquery = <<<'SOURCE'
$users_query->whereIn('id', Member::query()
            ->select('user_id')
SOURCE;
        self::assertStringContainsString($expectedSubquery, $method);
        self::assertStringNotContainsString("->pluck('user_id')->toArray()", $method);
        self::assertStringNotContainsString('if (count($user_ids) > 0)', $method);
    }

    public function test_religion_and_state_filters_use_matching_user_ids_and_search_is_paginated(): void
    {
        $method = $this->listingMethod();
        $religionQuery = <<<'SOURCE'
where('religion_id', $religion_id)
SOURCE;
        $stateQuery = <<<'SOURCE'
where('state_id', $state_id)
SOURCE;
        self::assertStringContainsString($religionQuery, $method);
        self::assertStringContainsString($stateQuery, $method);
        self::assertStringContainsString('$users_query->paginate($perPage', $method);
        $paginationFlag = <<<'SOURCE'
'has_more' => $membersPage->hasMorePages()
SOURCE;
        self::assertStringContainsString($paginationFlag, $method);
        self::assertStringContainsString("'per_page' => ['sometimes', 'integer', 'min:1', 'max:50']", $method);
    }
}
