<?php

namespace Tests\\Unit;

use PHPUnit\\Framework\\TestCase;

class AccountDeletionPrivacyContractTest extends TestCase
{
    private function source(string $relativePath): string
    {
        $path = dirname(__DIR__, 2) . '/' . $relativePath;
        $content = file_get_contents($path);
        $this->assertIsString($content, "Unable to read {$relativePath}");
        return $content;
    }

    private function accountDeleteMethod(): string
    {
        $source = $this->source('app/Http/Controllers/MemberController.php');
        $start = strpos($source, 'public function account_delete(Request $request)');
        $this->assertNotFalse($start, 'Website account deletion method must exist.');
        $end = strpos($source, "\\n    public function ", $start + 20);
        $this->assertNotFalse($end, 'Expected another controller method after account deletion.');

        return substr($source, $start, $end - $start);
    }

    public function test_website_account_deletion_is_atomic_and_redirects_after_success(): void
    {
        $method = $this->accountDeleteMethod();

        self::assertStringContainsString('DB::transaction(function () use ($user)', $method);
        self::assertStringContainsString('$user->delete();', $method);
        self::assertStringContainsString('auth()->guard()->logout();', $method);
        self::assertStringContainsString('$request->session()->invalidate();', $method);
        self::assertStringContainsString('$request->session()->regenerateToken();', $method);
        self::assertStringContainsString("return redirect()->route('home');", $method);

        $afterLogout = substr($method, strpos($method, 'auth()->guard()->logout();'));
        self::assertStringNotContainsString('Something Went Wrong!', $afterLogout);
        self::assertStringNotContainsString('return back();', $afterLogout);
    }

    public function test_account_deletion_removes_profile_and_user_specific_activity(): void
    {
        $method = $this->accountDeleteMethod();

        foreach ([
            '$user->member()->delete();',
            "Address::where('user_id', $user->id)->delete();",
            "Education::where('user_id', $user->id)->delete();",
            "Career::where('user_id', $user->id)->delete();",
            "PhysicalAttribute::where('user_id', $user->id)->delete();",
            "Hobby::where('user_id', $user->id)->delete();",
            "Attitude::where('user_id', $user->id)->delete();",
            "Recidency::where('user_id', $user->id)->delete();",
            "Lifestyle::where('user_id', $user->id)->delete();",
            "Astrology::where('user_id', $user->id)->delete();",
            "Family::where('user_id', $user->id)->delete();",
            "PartnerExpectation::where('user_id', $user->id)->delete();",
            "SpiritualBackground::where('user_id', $user->id)->delete();",
            "Shortlist::where('user_id', $user->id)",
            "IgnoredUser::where('user_id', $user->id)",
            "GalleryImage::where('user_id', $user->id)->delete();",
            "ExpressInterest::where('user_id', $user->id)",
            "ProfileMatch::where('user_id', $user->id)",
            "ChatThread::where(function ($query) use ($user)",
            "Upload::where('user_id', $user->id)->delete();",
        ] as $expected) {
            self::assertStringContainsString($expected, $method);
        }
    }

    public function test_account_deletion_preserves_statutory_payment_and_report_records(): void
    {
        $method = $this->accountDeleteMethod();
        $privacy = $this->source('resources/views/frontend/legal/privacy_policy.blade.php');

        self::assertStringNotContainsString('PackagePayment::where', $method);
        self::assertStringNotContainsString('ReportedUser::where', $method);
        self::assertStringContainsString('PackagePayment rows available for statutory accounting retention', $method);
        self::assertStringContainsString('12 شهراً بعد إغلاق البلاغ', $privacy);
        self::assertStringContainsString('8 سنوات وفق § 147 AO و§ 257 HGB', $privacy);
    }
}
