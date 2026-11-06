<?php

namespace Tests\Unit;

use App\Http\Requests\PartnerExpectationRequest;
use App\Http\Resources\PublicProfile\PartnerExpectationResource;
use App\Models\PartnerExpectation;
use App\Utility\PartnerAgeRange;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PartnerExpectationAgeValidationTest extends TestCase
{
    private function ageValidator(array $payload)
    {
        $request = PartnerExpectationRequest::create(
            '/member/partner-expectation/update',
            'POST',
            $payload
        );
        $validator = Validator::make($payload, $request->rules());
        $request->withValidator($validator);

        return $validator;
    }

    public function test_accepts_a_valid_adult_age_range()
    {
        $validator = $this->ageValidator(['min_age' => 18, 'max_age' => 100]);
        $this->assertTrue($validator->passes());
    }

    public function test_rejects_age_below_eighteen()
    {
        $validator = $this->ageValidator(['min_age' => 17, 'max_age' => 40]);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('min_age'));
    }

    public function test_rejects_age_above_one_hundred()
    {
        $validator = $this->ageValidator(['min_age' => 25, 'max_age' => 101]);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('max_age'));
    }

    public function test_rejects_minimum_age_greater_than_maximum_age()
    {
        $validator = $this->ageValidator(['min_age' => 45, 'max_age' => 30]);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('max_age'));
    }

    public function test_allows_partial_age_range_updates()
    {
        $validator = $this->ageValidator(['max_age' => 50]);
        $this->assertTrue($validator->passes());
    }

    public function test_resource_prefers_canonical_age_columns()
    {
        $expectation = new PartnerExpectation();
        $expectation->setRawAttributes([
            'min_age' => 24,
            'max_age' => 41,
            'partner_age' => 20,
            'partner_max_age' => 60,
        ], true);

        $resource = (new PartnerExpectationResource($expectation))->toArray(request());
        $this->assertSame(24, $resource['min_age']);
        $this->assertSame(41, $resource['max_age']);
    }

    public function test_resource_falls_back_to_legacy_age_columns()
    {
        $expectation = new PartnerExpectation();
        $expectation->setRawAttributes([
            'partner_age' => 23,
            'partner_max_age' => 39,
        ], true);

        $resource = (new PartnerExpectationResource($expectation))->toArray(request());
        $this->assertSame(23, $resource['min_age']);
        $this->assertSame(39, $resource['max_age']);
    }

    public function test_birthday_bounds_match_inclusive_current_age_edges()
    {
        $bounds = PartnerAgeRange::birthdayBounds(25, 40, Carbon::parse('2026-11-06'));
        $this->assertSame('1985-11-07', $bounds['oldest_birth_date']);
        $this->assertSame('2001-11-06', $bounds['youngest_birth_date']);
    }

    public function test_birthday_bounds_support_one_sided_age_limits()
    {
        $today = Carbon::parse('2026-11-06');
        $minimumOnly = PartnerAgeRange::birthdayBounds(25, null, $today);
        $maximumOnly = PartnerAgeRange::birthdayBounds(null, 40, $today);

        $this->assertNull($minimumOnly['oldest_birth_date']);
        $this->assertSame('2001-11-06', $minimumOnly['youngest_birth_date']);
        $this->assertSame('1985-11-07', $maximumOnly['oldest_birth_date']);
        $this->assertNull($maximumOnly['youngest_birth_date']);
    }
}
