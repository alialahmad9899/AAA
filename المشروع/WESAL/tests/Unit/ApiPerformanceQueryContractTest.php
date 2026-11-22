<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ApiPerformanceQueryContractTest extends TestCase
{
    private function source(string $path): string
    {
        $content = file_get_contents(__DIR__ . '/../../' . $path);
        self::assertIsString($content);
        return $content;
    }

    public function test_member_listing_uses_subqueries_and_eager_loads_card_relations(): void
    {
        $source = $this->source('app/Http/Controllers/Api/MemberController.php');
        $start = strpos($source, 'function member_listing(');
        $end = strpos($source, 'function ignored_user_list(', $start);
        self::assertNotFalse($start);
        self::assertNotFalse($end);
        $method = substr($source, $start, $end - $start);

        self::assertStringNotContainsString("->pluck('user_id')->toArray()", $method);
        self::assertStringContainsString("->select('user_id')", $method);
        self::assertStringContainsString("'member.marital_status'", $method);
        self::assertStringContainsString("'addresses' => function (", $method);
        self::assertStringContainsString("'spiritual_backgrounds.religion'", $method);
        self::assertStringContainsString("'physical_attributes'", $method);
        self::assertStringContainsString('$users_query->paginate($perPage', $method);
    }

    public function test_interest_feeds_eager_load_relationships_and_avoid_per_row_queries(): void
    {
        $controller = $this->source('app/Http/Controllers/Api/InterestController.php');
        self::assertStringContainsString("'user.member.mothereTongue'", $controller);
        self::assertStringContainsString("'interestedBy.member.mothereTongue'", $controller);
        self::assertStringContainsString("whereHas('interestedBy'", $controller);

        $outgoing = $this->source('app/Http/Resources/MyInterestResource.php');
        $incoming = $this->source('app/Http/Resources/ExpressInterestResource.php');
        $model = $this->source('app/Models/ExpressInterest.php');

        self::assertStringContainsString('$user = $this->user;', $outgoing);
        self::assertStringContainsString('$interestedBy = $this->interestedBy;', $incoming);
        self::assertStringContainsString('function interestedBy()', $model);
        self::assertStringNotContainsString('User::find(', $outgoing);
        self::assertStringNotContainsString('User::find(', $incoming);
        self::assertStringNotContainsString('MemberUtility::member_', $outgoing);
        self::assertStringNotContainsString('MemberUtility::member_', $incoming);
    }

    public function test_chat_refresh_is_incremental_and_attachments_are_loaded_in_a_batch(): void
    {
        $chatResource = $this->source('app/Http/Resources/ChatResource.php');
        $messageResource = $this->source('app/Http/Resources/Chat/ChatViewResource.php');
        $controller = $this->source('app/Http/Controllers/Api/ChatController.php');

        self::assertStringContainsString("request->query('after_id'", $chatResource);
        self::assertStringContainsString("->where('id', '>', ", $chatResource);
        self::assertStringContainsString('ChatViewResource::preloadAttachments($chats)', $chatResource);
        self::assertStringContainsString('public static function preloadAttachments(iterable $messages)', $messageResource);
        self::assertStringContainsString('ChatThread::with([\'sender\', \'receiver\'])', $controller);
    }

    public function test_login_response_eager_loads_member_and_physical_data_once(): void
    {
        $source = $this->source('app/Http/Controllers/Api/AuthController.php');
        self::assertStringContainsString("loadMissing(['member.marital_status', 'physical_attributes'])", $source);
        $authResponseStart = strpos($source, 'function authResponse(');
        $authDataStart = strpos($source, 'function authData(');
        $authDataEnd = strpos($source, 'function checkedData(', $authDataStart);
        self::assertNotFalse($authResponseStart);
        self::assertNotFalse($authDataStart);
        self::assertNotFalse($authDataEnd);
        $authResponse = substr($source, $authResponseStart, strpos($source, 'function forgotPassword(', $authResponseStart) - $authResponseStart);
        $authData = substr($source, $authDataStart, $authDataEnd - $authDataStart);
        self::assertStringContainsString("loadMissing(['member.marital_status', 'physical_attributes'])", $authResponse);
        self::assertStringContainsString("loadMissing(['member.marital_status', 'physical_attributes'])", $authData);
        self::assertStringNotContainsString("MaritalStatus::where('id'", $authResponse);
        self::assertStringNotContainsString("MaritalStatus::where('id'", $authData);
    }
}
