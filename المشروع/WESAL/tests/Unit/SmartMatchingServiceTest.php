<?php

namespace Tests\Unit;

use App\Services\SmartMatchingService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/Services/SmartMatchingService.php';

class SmartMatchingServiceTest extends TestCase
{
    private SmartMatchingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SmartMatchingService();
    }

    public function test_only_selected_preferences_contribute_to_the_score(): void
    {
        $result = $this->service->scoreData([
            'preferred_country_id' => 12,
            'education' => 'لا يهم',
            'smoking_acceptable' => 'does_not_matter',
            'drinking_acceptable' => 'dose_not_matter',
        ], [
            'country_id' => 12,
        ]);

        $this->assertSame(100, $result['percentage']);
        $this->assertSame(1, $result['criteria_count']);
        $this->assertSame(1, $result['evaluated_criteria_count']);
        $this->assertSame('calculated', $result['status']);
        $this->assertSame('country', $result['details'][0]['key']);
    }

    public function test_same_country_ranks_above_a_different_country(): void
    {
        $expectations = ['preferred_country_id' => 12];

        $sameCountry = $this->service->scoreData($expectations, ['country_id' => 12]);
        $otherCountry = $this->service->scoreData($expectations, ['country_id' => 44]);

        $this->assertSame(100, $sameCountry['percentage']);
        $this->assertSame(25, $otherCountry['percentage']);
        $this->assertGreaterThan($otherCountry['percentage'], $sameCountry['percentage']);
    }

    public function test_height_and_weight_ranges_accept_values_with_normalised_height_units(): void
    {
        $result = $this->service->scoreData([
            'min_height' => 1.60,
            'max_height' => 1.82,
            'min_weight' => 50,
            'max_weight' => 85,
        ], [
            'height' => 1.75,
            'weight' => 72,
        ]);

        $this->assertSame(100, $result['percentage']);
        $this->assertSame(2, $result['criteria_count']);
        $this->assertSame(2, $result['evaluated_criteria_count']);
    }

    public function test_age_range_scores_inside_range_above_outside_range(): void
    {
        $expectations = [
            'min_age' => 25,
            'max_age' => 35,
        ];

        $inside = $this->service->scoreData($expectations, ['age' => 30]);
        $nearOutside = $this->service->scoreData($expectations, ['age' => 38]);

        $this->assertSame(100, $inside['percentage']);
        $this->assertSame(30, $nearOutside['percentage']);
        $this->assertGreaterThan($nearOutside['percentage'], $inside['percentage']);
    }

    public function test_legacy_partner_age_columns_are_scored(): void
    {
        $result = $this->service->scoreData([
            'partner_age' => 25,
            'partner_max_age' => 35,
        ], [
            'age' => 30,
        ]);

        $this->assertSame(100, $result['percentage']);
        $this->assertSame(1, $result['criteria_count']);
    }

    public function test_candidate_outside_a_rejected_children_preference_loses_that_criterion(): void
    {
        $result = $this->service->scoreData([
            'children_acceptable' => 'no',
        ], [
            'children' => 2,
        ]);

        $this->assertSame(0, $result['percentage']);
        $this->assertSame(1, $result['criteria_count']);
    }

    public function test_missing_candidate_data_is_not_counted_as_a_match_or_a_zero(): void
    {
        $result = $this->service->scoreData([
            'religion_id' => 4,
        ], []);

        $this->assertNull($result['percentage']);
        $this->assertSame(1, $result['criteria_count']);
        $this->assertSame(0, $result['evaluated_criteria_count']);
        $this->assertSame('insufficient_data', $result['status']);
        $this->assertFalse($result['details'][0]['evaluated']);
    }

    public function test_missing_criteria_are_excluded_from_the_percentage_denominator(): void
    {
        $result = $this->service->scoreData([
            'preferred_country_id' => 12,
            'religion_id' => 4,
        ], [
            'country_id' => 12,
            'religion_id' => null,
        ]);

        $this->assertSame(100, $result['percentage']);
        $this->assertSame(2, $result['criteria_count']);
        $this->assertSame(1, $result['evaluated_criteria_count']);
        $this->assertSame('calculated', $result['status']);
    }

    public function test_a_real_zero_match_is_preserved_when_data_is_present(): void
    {
        $result = $this->service->scoreData([
            'religion_id' => 4,
        ], [
            'religion_id' => 5,
        ]);

        $this->assertSame(0, $result['percentage']);
        $this->assertSame(1, $result['criteria_count']);
        $this->assertSame(1, $result['evaluated_criteria_count']);
        $this->assertSame('calculated', $result['status']);
    }

    public function test_arabic_profession_text_is_normalised_for_comparison(): void
    {
        $result = $this->service->scoreData([
            'profession' => 'مهندس برمجيات',
        ], [
            'professions' => ['مُهَنْدِس برمجيات'],
        ]);

        $this->assertSame(100, $result['percentage']);
        $this->assertSame(1, $result['criteria_count']);
    }

    public function test_does_not_matter_values_do_not_create_a_fake_match_score(): void
    {
        $result = $this->service->scoreData([
            'education' => 'Doesn\'t matter',
            'complexion' => 'لا يهم',
            'eye_color' => 'لا يهم',
            'hair_color' => 'لا يهم',
            'children_acceptable' => 'does_not_matter',
            'smoking_acceptable' => 'does_not_matter',
            'drinking_acceptable' => 'does_not_matter',
        ], []);

        $this->assertNull($result['percentage']);
        $this->assertSame(0, $result['criteria_count']);
        $this->assertSame(0, $result['evaluated_criteria_count']);
        $this->assertSame('no_preferences', $result['status']);
        $this->assertSame([], $result['details']);
    }

    public function test_supported_legacy_preferences_are_scored_when_candidate_fields_exist(): void
    {
        $result = $this->service->scoreData([
            'caste_id' => 2,
            'sub_caste_id' => 8,
            'family_value_id' => 3,
            'personal_value' => 'ملتزم',
            'body_type' => 'رياضي',
        ], [
            'caste_id' => 2,
            'sub_caste_id' => 8,
            'family_value_id' => 3,
            'personal_value' => 'ملتزم',
            'body_type' => 'رياضي',
        ]);

        $this->assertSame(100, $result['percentage']);
        $this->assertSame(5, $result['criteria_count']);
        $this->assertSame(5, $result['evaluated_criteria_count']);
    }

    public function test_partial_profession_overlap_is_not_reported_as_a_perfect_match(): void
    {
        $result = $this->service->scoreData([
            'profession' => 'مهندس برمجيات',
        ], [
            'professions' => ['مهندس'],
        ]);

        $this->assertSame(50, $result['percentage']);
    }


    public function test_arabic_and_english_hair_colour_labels_resolve_to_the_same_value(): void
    {
        $result = $this->service->scoreData([
            'hair_color' => 'أشقر',
        ], [
            'hair_color' => 'Blonde',
        ]);

        $this->assertSame(100, $result['percentage']);
        $this->assertSame(1, $result['evaluated_criteria_count']);
    }

    public function test_arabic_and_english_profession_labels_resolve_to_the_same_value(): void
    {
        $result = $this->service->scoreData([
            'profession' => 'Engineer',
        ], [
            'professions' => ['مهندس'],
        ]);

        $this->assertSame(100, $result['percentage']);
    }

    public function test_arabic_personal_value_and_body_type_match_english_preferences(): void
    {
        $result = $this->service->scoreData([
            'personal_value' => 'Very Religious',
            'body_type' => 'Athletic',
        ], [
            'personal_value' => 'ملتزم جداً',
            'body_type' => 'رياضي / متناسق',
        ]);

        $this->assertSame(100, $result['percentage']);
        $this->assertSame(2, $result['evaluated_criteria_count']);
    }

}
