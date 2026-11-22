<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MobileApiPerformanceContractTest extends TestCase
{
    private function source(string $relativePath): string
    {
        $path = dirname(__DIR__, 2) . '/' . $relativePath;
        $content = file_get_contents($path);
        $this->assertIsString($content);
        return $content;
    }

    public function test_home_member_endpoint_supports_opt_in_pagination(): void
    {
        $source = $this->source('app/Http/Controllers/Api/MemberController.php');
        $this->assertStringContainsString("has('per_page')", $source);
        $this->assertStringContainsString('->offset(($page - 1) * $perPage)', $source);
        $this->assertStringContainsString('->limit($perPage + 1)', $source);
        $this->assertStringContainsString('scoreCandidates(', $source);
        $this->assertStringContainsString("'has_more' => $hasMore", $source);
    }

    public function test_paged_smart_matching_scores_in_chunks_and_hydrates_only_the_requested_page(): void
    {
        $controller = $this->source('app/Http/Controllers/Api/MemberController.php');
        $service = $this->source('app/Services/SmartMatchingService.php');

        $this->assertStringContainsString(
            '$matchingService->getMatchesPage($user, $page, $perPage)',
            $controller
        );
        $this->assertStringContainsString('->chunkById(200', $service);
        $this->assertStringContainsString('$this->sortRankedRows($rankedRows)', $service);
        $this->assertStringContainsString('array_slice($rankedRows, $offset, $perPage)', $service);
        $this->assertStringContainsString("->whereIn('id', $ids)", $service);
    }

    public function test_compact_mobile_summary_returns_identity_and_access_flags(): void
    {
        $controller = $this->source('app/Http/Controllers/Api/ProfileController.php');
        $routes = $this->source('routes/api.php');
        $this->assertStringContainsString('public function app_summary()', $controller);
        $isFemaleContract = <<<'SOURCE'
'is_female' => $gender === 2
SOURCE;
        $activePackageContract = <<<'SOURCE'
'is_subscribed_active' => $activePackage !== null
SOURCE;
        $this->assertStringContainsString($isFemaleContract, $controller);
        $this->assertStringContainsString($activePackageContract, $controller);
        $this->assertStringContainsString("Route::get('/app-summary', 'ProfileController@app_summary')", $routes);
    }

    public function test_chat_resource_uses_eager_loaded_member_and_package(): void
    {
        $controller = $this->source('app/Http/Controllers/Api/ChatController.php');
        $resource = $this->source('app/Http/Resources/ChatThreadResource.php');
        $this->assertStringContainsString(
            "->with(['sender.member.package', 'receiver.member.package'])",
            $controller
        );
        $this->assertStringContainsString('$member = $targetUser->member;', $resource);
        $this->assertStringContainsString('$memberPackage = $member ? $member->package : null;', $resource);
        $this->assertStringNotContainsString('Member::where(', $resource);
        $this->assertStringNotContainsString('Package::find(', $resource);
    }

    public function test_full_mobile_profile_eager_loads_and_reuses_address_rows(): void
    {
        $controller = $this->source('app/Http/Controllers/Api/ProfileController.php');
        $this->assertStringContainsString("'addresses.country'", $controller);
        $this->assertStringContainsString("firstWhere('type', 'present')", $controller);
        $this->assertStringContainsString("firstWhere('type', 'permanent')", $controller);
        $this->assertStringNotContainsString(
            "Address::where('user_id', $userId)->where('type', 'present')->first()",
            $controller
        );
    }
}
