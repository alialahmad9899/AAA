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

    public function test_global_rank_sort_keeps_real_scores_ahead_of_unknown_scores(): void
    {
        $rows = [
            ['id' => 9, 'percentage' => null, 'details' => [], 'criteria_count' => 1, 'evaluated_criteria_count' => 0, 'status' => 'insufficient_data', 'created_at' => '2026-11-22 12:00:00'],
            ['id' => 4, 'percentage' => 80, 'details' => [], 'criteria_count' => 1, 'evaluated_criteria_count' => 1, 'status' => 'calculated', 'created_at' => '2026-11-22 13:00:00'],
            ['id' => 2, 'percentage' => 100, 'details' => [], 'criteria_count' => 1, 'evaluated_criteria_count' => 1, 'status' => 'calculated', 'created_at' => '2026-11-22 10:00:00'],
            ['id' => 7, 'percentage' => 100, 'details' => [], 'criteria_count' => 1, 'evaluated_criteria_count' => 1, 'status' => 'calculated', 'created_at' => '2026-11-22 14:00:00'],
            ['id' => 6, 'percentage' => 100, 'details' => [], 'criteria_count' => 1, 'evaluated_criteria_count' => 1, 'status' => 'calculated', 'created_at' => '2026-11-22 14:00:00'],
            ['id' => 8, 'percentage' => null, 'details' => [], 'criteria_count' => 1, 'evaluated_criteria_count' => 0, 'status' => 'insufficient_data', 'created_at' => '2026-11-22 15:00:00'],
        ];

        $sorted = $this->service->sortRankedRows($rows);

        $this->assertSame([7, 6, 2, 4, 8, 9], array_column($sorted, 'id'));
    }

    public function test_only_selected_and_available_preferences_contribute_to_the_score(): void
    {
        $result = $this->service->scoreData([
            'preferred_country_id' => 12,
            'religion_id' => 4,
            'marital_status_id' => 2,
            'education' => 'لا يهم',
            'smoking_acceptable' => 'does_not_matter',
            'drinking_acceptable' => 'dose_not_matter',
        ], [
            'country_id' => 12,
            'religion_id' => 4,
            'marital_status_id' => 2,
        ]);

        $this->assertSame(100, $result['percentage']);
        $this->assertSame(3, $result['criteria_count']);
        $this->assertSame(3, $result['evaluated_criteria_count']);
        $this->assertSame('calculated', $result['status']);
        $this->assertSame('country', $result['details'][0]['key']);
    }

    public function test_same_country_ranks_above_a_different_country(): void
    {
        $expectations = [
            'preferred_country_id' => 12,
            'religion_id' => 4,
            'marital_status_id' => 2,
        ];

        $sameCountry = $this->service->scoreData($expectations, [
            'country_id' => 12, 'religion_id' => 4, 'marital_status_id' => 2,
        ]);
        $otherCountry = $this->service->scoreData($expectations, [
            'country_id' => 44, 'religion_id' => 4, 'marital_status_id' => 2,
        ]);

        $this->assertSame(100, $sameCountry['percentage']);
        $this->assertSame(64, $otherCountry['percentage']);
        $this->assertGreaterThan($otherCountry['percentage'], $sameCountry['percentage']);
    }

    public function test_height_and_weight_ranges_accept_values_with_normalised_height_units(): void
    {
        $result = $this->service->scoreData([
            'min_height' => 1.60,
            'max_height' => 1.82,
            'min_weight' => 50,
            'max_weight' => 85,
            'preferred_country_id' => 12,
        ], [
            'height' => 1.75,
            'weight' => 72,
            'country_id' => 12,
        ]);

        $this->assertSame(100, $result['percentage']);
        $this->assertSame(3, $result['criteria_count']);
        $this->assertSame(3, $result['evaluated_criteria_count']);
    }

    public function test_age_range_scores_inside_range_above_outside_range(): void
    {
        $expectations = [
            'min_age' => 25,
            'max_age' => 35,
            'preferred_country_id' => 12,
            'religion_id' => 4,
        ];

        $inside = $this->service->scoreData($expectations, [
            'age' => 30, 'country_id' => 12, 'religion_id' => 4,
        ]);
        $nearOutside = $this->service->scoreData($expectations, [
            'age' => 38, 'country_id' => 12, 'religion_id' => 4,
        ]);

        $this->assertSame(100, $inside['percentage']);
        $this->assertSame(79, $nearOutside['percentage']);
        $this->assertGreaterThan($nearOutside['percentage'], $inside['percentage']);
    }

    public function test_legacy_partner_age_columns_are_scored(): void
    {
        $result = $this->service->scoreData([
            'partner_age' => 25,
            'partner_max_age' => 35,
            'preferred_country_id' => 12,
            'religion_id' => 4,
        ], [
            'age' => 30,
            'country_id' => 12,
            'religion_id' => 4,
        ]);

        $this->assertSame(100, $result['percentage']);
        $this->assertSame(3, $result['criteria_count']);
    }

    public function test_candidate_outside_a_rejected_children_preference_loses_that_criterion(): void
    {
        $result = $this->service->scoreData([
            'children_acceptable' => 'no',
            'preferred_country_id' => 12,
            'religion_id' => 4,
            'marital_status_id' => 2,
        ], [
            'children' => 2,
            'country_id' => 12,
            'religion_id' => 4,
            'marital_status_id' => 2,
        ]);

        $this->assertSame(90, $result['percentage']);
        $this->assertSame(4, $result['criteria_count']);
        $this->assertSame(0, $result['details'][3]['score']);
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

    public function test_missing_candidate_fields_are_excluded_from_the_percentage_denominator(): void
    {
        $result = $this->service->scoreData([
            'preferred_country_id' => 12, // weight 18
            'religion_id' => 4,           // weight 10
            'marital_status_id' => 2,     // weight 10
            'eye_color' => 'brown',       // candidate data is missing
        ], [
            'country_id' => 12,
            'religion_id' => 4,
            'marital_status_id' => 2,
            'eye_color' => null,
        ]);

        // The missing eye-colour weight is omitted, not counted as a mismatch.
        $this->assertSame(100, $result['percentage']);
        $this->assertSame(4, $result['criteria_count']);
        $this->assertSame(3, $result['evaluated_criteria_count']);
        $this->assertSame('calculated', $result['status']);
        $this->assertSame(100, $result['details'][0]['score']);
        $this->assertNull($result['details'][3]['score']);
        $this->assertFalse($result['details'][3]['evaluated']);
    }

    public function test_sparse_candidate_with_one_available_field_is_not_given_a_fake_percentage(): void
    {
        $result = $this->service->scoreData([
            'preferred_country_id' => 12,
            'religion_id' => 4,
            'min_age' => 25,
            'max_age' => 35,
            'profession' => 'مهندس',
        ], [
            'country_id' => 12,
            'religion_id' => null,
            'age' => null,
            'professions' => [],
        ]);

        $this->assertNull($result['percentage']);
        $this->assertSame(4, $result['criteria_count']);
        $this->assertSame(1, $result['evaluated_criteria_count']);
        $this->assertSame('insufficient_data', $result['status']);
    }

    public function test_incomplete_candidate_is_scored_only_on_fields_that_are_available(): void
    {
        $result = $this->service->scoreData([
            'preferred_country_id' => 12,
            'religion_id' => 4,
            'marital_status_id' => 2,
            'min_age' => 25,
            'max_age' => 35,
            'profession' => 'مهندس',
        ], [
            'country_id' => 12,
            'religion_id' => 4,
            'marital_status_id' => 2,
            'age' => null,
            'professions' => [],
        ]);

        // Three available fields carry 38 points; missing age and profession
        // are excluded, so the available data itself scores 100%.
        $this->assertSame(100, $result['percentage']);
        $this->assertSame(5, $result['criteria_count']);
        $this->assertSame(3, $result['evaluated_criteria_count']);
        $this->assertSame('calculated', $result['status']);
    }

    public function test_placeholder_candidate_text_is_treated_as_missing_data(): void
    {
        $result = $this->service->scoreData([
            'profession' => 'مهندس',
        ], [
            'professions' => ['غير محدد'],
        ]);

        $this->assertNull($result['percentage']);
        $this->assertSame(1, $result['criteria_count']);
        $this->assertSame(0, $result['evaluated_criteria_count']);
        $this->assertSame('insufficient_data', $result['status']);
    }

    public function test_placeholder_preference_does_not_activate_smart_matching(): void
    {
        $result = $this->service->scoreData([
            'profession' => 'غير محدد',
            'complexion' => 'Not specified',
        ], []);

        $this->assertNull($result['percentage']);
        $this->assertSame(0, $result['criteria_count']);
        $this->assertSame(0, $result['evaluated_criteria_count']);
        $this->assertSame('no_preferences', $result['status']);
    }

    public function test_a_single_matching_preference_does_not_produce_a_trustworthy_percentage(): void
    {
        $result = $this->service->scoreData([
            'preferred_country_id' => 12,
        ], [
            'country_id' => 12,
        ]);

        $this->assertNull($result['percentage']);
        $this->assertSame(1, $result['criteria_count']);
        $this->assertSame(1, $result['evaluated_criteria_count']);
        $this->assertSame('insufficient_data', $result['status']);
    }

    public function test_a_real_zero_match_is_preserved_when_data_is_present(): void
    {
        $result = $this->service->scoreData([
            'religion_id' => 4,
            'marital_status_id' => 1,
            'caste_id' => 2,
            'family_value_id' => 3,
        ], [
            'religion_id' => 5,
            'marital_status_id' => 2,
            'caste_id' => 4,
            'family_value_id' => 6,
        ]);

        $this->assertSame(0, $result['percentage']);
        $this->assertSame(4, $result['criteria_count']);
        $this->assertSame(4, $result['evaluated_criteria_count']);
        $this->assertSame('calculated', $result['status']);
    }

    public function test_arabic_profession_text_is_normalised_for_comparison(): void
    {
        $result = $this->service->scoreData([
            'profession' => 'مهندس برمجيات',
            'preferred_country_id' => 12,
            'religion_id' => 4,
        ], [
            'professions' => ['مُهَنْدِس برمجيات'],
            'country_id' => 12,
            'religion_id' => 4,
        ]);

        $this->assertSame(100, $result['percentage']);
        $this->assertSame(3, $result['criteria_count']);
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
            'preferred_country_id' => 12,
        ], [
            'caste_id' => 2,
            'sub_caste_id' => 8,
            'family_value_id' => 3,
            'personal_value' => 'ملتزم',
            'body_type' => 'رياضي',
            'country_id' => 12,
        ]);

        $this->assertSame(100, $result['percentage']);
        $this->assertSame(6, $result['criteria_count']);
        $this->assertSame(6, $result['evaluated_criteria_count']);
    }

    public function test_partial_profession_overlap_is_not_reported_as_a_perfect_match(): void
    {
        $result = $this->service->scoreData([
            'profession' => 'مهندس برمجيات',
            'preferred_country_id' => 12,
            'religion_id' => 4,
        ], [
            'professions' => ['مهندس'],
            'country_id' => 44,
            'religion_id' => 5,
        ]);

        // The text similarity itself is 50%; the other two criteria are also evaluated.
        $this->assertSame(50, $result['details'][0]['score']);
        $this->assertSame('calculated', $result['status']);
    }


    public function test_arabic_and_english_hair_colour_labels_resolve_to_the_same_value(): void
    {
        $result = $this->service->scoreData([
            'hair_color' => 'أشقر',
            'preferred_country_id' => 12,
            'religion_id' => 4,
        ], [
            'hair_color' => 'Blonde',
            'country_id' => 12,
            'religion_id' => 4,
        ]);

        $this->assertSame(100, $result['percentage']);
        $this->assertSame(3, $result['evaluated_criteria_count']);
    }

    public function test_arabic_and_english_profession_labels_resolve_to_the_same_value(): void
    {
        $result = $this->service->scoreData([
            'profession' => 'Engineer',
            'preferred_country_id' => 12,
            'religion_id' => 4,
        ], [
            'professions' => ['مهندس'],
            'country_id' => 12,
            'religion_id' => 4,
        ]);

        $this->assertSame(100, $result['percentage']);
    }

    public function test_arabic_personal_value_and_body_type_match_english_preferences(): void
    {
        $result = $this->service->scoreData([
            'personal_value' => 'Very Religious',
            'body_type' => 'Athletic',
            'preferred_country_id' => 12,
            'religion_id' => 4,
        ], [
            'personal_value' => 'ملتزم جداً',
            'body_type' => 'رياضي / متناسق',
            'country_id' => 12,
            'religion_id' => 4,
        ]);

        $this->assertSame(100, $result['percentage']);
        $this->assertSame(4, $result['evaluated_criteria_count']);
    }

    public function test_partner_age_range_normalises_canonical_legacy_and_one_sided_values(): void
    {
        $this->assertSame(
            ['min_age' => 22, 'max_age' => 45],
            $this->service->normalisePartnerAgeRange(['min_age' => '22', 'max_age' => '45'])
        );
        $this->assertSame(
            ['min_age' => 26, 'max_age' => 100],
            $this->service->normalisePartnerAgeRange(['partner_age' => 26])
        );
        $this->assertNull(
            $this->service->normalisePartnerAgeRange(['min_age' => 46, 'max_age' => 45])
        );
        $this->assertNull($this->service->normalisePartnerAgeRange([]));
    }

    public function test_multiple_real_preferences_produce_a_weighted_intermediate_percentage(): void
    {
        $result = $this->service->scoreData([
            'min_age' => 22,
            'max_age' => 45,
            'preferred_country_id' => 12,
        ], [
            'age' => 48,
            'country_id' => 44,
        ]);

        // Both data points are available, so they alone form the denominator.
        // Age contributes 3.6 points and country contributes 4.5 of 30.
        $this->assertSame(27, $result['percentage']);
        $this->assertSame(2, $result['criteria_count']);
        $this->assertSame(2, $result['evaluated_criteria_count']);
        $this->assertSame('calculated', $result['status']);
    }

}
