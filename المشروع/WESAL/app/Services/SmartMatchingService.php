<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * مصدر واحد لحساب مطابقة تطبيق الموبايل من توقعات صاحب الحساب
 * وبيانات الملف الفعلية للمرشح. لا يعتمد على نتائج محفوظة قديمة.
 */
class SmartMatchingService
{
    /**
     * إعادة المرشحين المؤهلين مرتبين حسب نسبة التوافق من الأعلى إلى الأقل.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function getMatches(User $viewer): Collection
    {
        $viewer->loadMissing(['member', 'partner_expectations']);

        $expectations = $viewer->partner_expectations;
        if (!$expectations || !$this->hasPreferences($expectations->getAttributes())) {
            return collect();
        }

        $gender = (int) optional($viewer->member)->gender;
        if (!in_array($gender, [1, 2], true)) {
            return collect();
        }

        $oppositeGender = $gender === 1 ? 2 : 1;
        $viewerId = (int) $viewer->id;

        $candidates = User::query()
            ->with([
                'member',
                'addresses.country',
                'recidency',
                'physical_attributes',
                'spiritual_backgrounds',
                'lifestyles',
                'education',
                'career',
            ])
            ->where('user_type', 'member')
            ->where('id', '!=', $viewerId)
            ->where('blocked', 0)
            ->where('deactivated', 0)
            ->whereHas('member', function ($query) use ($oppositeGender) {
                $query->where('gender', $oppositeGender);
            })
            // تجاهل من تجاهلهم المستخدم.
            ->whereNotIn('id', function ($query) use ($viewerId) {
                $query->select('user_id')
                    ->from('ignored_users')
                    ->where('ignored_by', $viewerId);
            })
            // وتجاهل من قاموا بتجاهل المستخدم أيضاً.
            ->whereNotIn('id', function ($query) use ($viewerId) {
                $query->select('ignored_by')
                    ->from('ignored_users')
                    ->where('user_id', $viewerId);
            })
            ->get();

        return $candidates->map(function (User $candidate) use ($expectations) {
            $result = $this->scoreData(
                $expectations->getAttributes(),
                $this->candidateData($candidate)
            );

            $candidate->setAttribute('profile_match_percentage', $result['percentage']);
            $candidate->setAttribute('match_details', $result['details']);
            $candidate->setAttribute('match_criteria_count', $result['criteria_count']);

            return $candidate;
        })->sort(function (User $left, User $right) {
            $scoreOrder = (int) $right->profile_match_percentage <=> (int) $left->profile_match_percentage;
            if ($scoreOrder !== 0) {
                return $scoreOrder;
            }

            return strcmp(
                (string) ($right->created_at ?? ''),
                (string) ($left->created_at ?? '')
            );
        })->values();
    }

    /**
     * حساب توافق مرشح واحد، ويُستخدم أيضاً في شاشة الملف الشخصي.
     */
    public function scoreFor(User $viewer, User $candidate): ?array
    {
        $viewer->loadMissing(['member', 'partner_expectations']);
        $candidate->loadMissing([
            'member',
            'addresses',
            'recidency',
            'physical_attributes',
            'spiritual_backgrounds',
            'lifestyles',
            'education',
            'career',
        ]);

        if (!$viewer->member || !$candidate->member ||
            (int) $viewer->member->gender === (int) $candidate->member->gender ||
            !in_array((int) $viewer->member->gender, [1, 2], true) ||
            !in_array((int) $candidate->member->gender, [1, 2], true) ||
            $candidate->user_type !== 'member' ||
            (int) $candidate->blocked === 1 ||
            (int) $candidate->deactivated === 1) {
            return null;
        }

        $expectations = $viewer->partner_expectations;
        if (!$expectations || !$this->hasPreferences($expectations->getAttributes())) {
            return null;
        }

        return $this->scoreData(
            $expectations->getAttributes(),
            $this->candidateData($candidate)
        );
    }

    /**
     * دالة حساب نقية قابلة للاختبار دون قاعدة بيانات.
     *
     * @param array<string, mixed> $expectations
     * @param array<string, mixed> $candidate
     * @return array{percentage:int,criteria_count:int,details:array<int,array<string,mixed>>}
     */
    public function scoreData(array $expectations, array $candidate): array
    {
        $weightedScore = 0.0;
        $totalWeight = 0.0;
        $details = [];

        $add = function (string $key, float $weight, float $score) use (&$weightedScore, &$totalWeight, &$details) {
            $score = max(0.0, min(1.0, $score));
            $weightedScore += $weight * $score;
            $totalWeight += $weight;
            $details[] = [
                'key' => $key,
                'score' => (int) round($score * 100),
                'weight' => $weight,
            ];
        };

        $countryId = $expectations['preferred_country_id'] ?? null;
        if ($this->hasValue($countryId)) {
            $candidateCountry = $candidate['country_id'] ?? null;
            $score = !$this->hasValue($candidateCountry)
                ? 0.5
                : ((string) $candidateCountry === (string) $countryId ? 1.0 : 0.25);
            $add('country', 18, $score);
        }

        $stateId = $expectations['preferred_state_id'] ?? null;
        if ($this->hasValue($stateId)) {
            $candidateState = $candidate['state_id'] ?? null;
            $candidateCountry = $candidate['country_id'] ?? null;
            $sameCountry = $this->hasValue($countryId)
                && $this->hasValue($candidateCountry)
                && (string) $candidateCountry === (string) $countryId;

            if (!$this->hasValue($candidateState)) {
                $score = 0.5;
            } elseif ((string) $candidateState === (string) $stateId) {
                $score = 1.0;
            } else {
                $score = $sameCountry ? 0.4 : 0.1;
            }
            $add('state', 7, $score);
        }

        $minAge = $this->numericOrNull($expectations['min_age'] ?? null);
        $maxAge = $this->numericOrNull($expectations['max_age'] ?? null);
        if ($minAge !== null || $maxAge !== null) {
            $age = $this->numericOrNull($candidate['age'] ?? null);
            $score = $age === null ? 0.5 : $this->rangeScore($age, $minAge, $maxAge, 2, 5);
            $add('age', 12, $score);
        }

        $maritalStatus = $expectations['marital_status_id'] ?? null;
        if ($this->hasValue($maritalStatus)) {
            $candidateStatus = $candidate['marital_status_id'] ?? null;
            $score = !$this->hasValue($candidateStatus)
                ? 0.5
                : ((string) $candidateStatus === (string) $maritalStatus ? 1.0 : 0.0);
            $add('marital_status', 10, $score);
        }

        $religionId = $expectations['religion_id'] ?? null;
        if ($this->hasValue($religionId)) {
            $candidateReligion = $candidate['religion_id'] ?? null;
            $score = !$this->hasValue($candidateReligion)
                ? 0.5
                : ((string) $candidateReligion === (string) $religionId ? 1.0 : 0.0);
            $add('religion', 10, $score);
        }

        $minHeight = $this->normaliseHeight($expectations['min_height'] ?? null);
        $maxHeight = $this->normaliseHeight($expectations['max_height'] ?? null);
        if ($minHeight === null && $maxHeight === null) {
            $legacyHeight = $this->normaliseHeight($expectations['height'] ?? null);
            if ($legacyHeight !== null) {
                $minHeight = $legacyHeight - 10;
                $maxHeight = $legacyHeight + 10;
            }
        }
        if ($minHeight !== null || $maxHeight !== null) {
            $height = $this->normaliseHeight($candidate['height'] ?? null);
            $score = $height === null ? 0.5 : $this->rangeScore($height, $minHeight, $maxHeight, 5, 10);
            $add('height', 8, $score);
        }

        $minWeight = $this->numericOrNull($expectations['min_weight'] ?? null);
        $maxWeight = $this->numericOrNull($expectations['max_weight'] ?? null);
        if ($minWeight === null && $maxWeight === null) {
            $legacyWeight = $this->numericOrNull($expectations['weight'] ?? null);
            if ($legacyWeight !== null) {
                $minWeight = $legacyWeight - 15;
                $maxWeight = $legacyWeight + 15;
            }
        }
        if ($minWeight !== null || $maxWeight !== null) {
            $weight = $this->numericOrNull($candidate['weight'] ?? null);
            $score = $weight === null ? 0.5 : $this->rangeScore($weight, $minWeight, $maxWeight, 5, 10);
            $add('weight', 7, $score);
        }

        $education = $expectations['education'] ?? null;
        if ($this->hasMeaningfulPreference($education)) {
            $candidateDegrees = $candidate['education'] ?? [];
            $candidateDegrees = is_array($candidateDegrees) ? $candidateDegrees : [$candidateDegrees];
            $levels = array_filter(array_map([$this, 'educationLevel'], $candidateDegrees));
            if (empty($levels)) {
                $score = 0.5;
            } else {
                $preferredLevel = $this->educationLevel($education);
                $bestLevel = max($levels);
                $score = $preferredLevel === 0
                    ? ($this->textSimilarity($education, $candidateDegrees) ?? 0.5)
                    : ($bestLevel >= $preferredLevel
                        ? 1.0
                        : ($bestLevel === $preferredLevel - 1 ? 0.6 : 0.2));
            }
            $add('education', 8, $score);
        }

        $profession = $expectations['profession'] ?? null;
        if ($this->hasMeaningfulPreference($profession)) {
            $candidateProfessions = $candidate['professions'] ?? [];
            $similarity = $this->textSimilarity($profession, is_array($candidateProfessions) ? $candidateProfessions : [$candidateProfessions]);
            $add('profession', 5, $similarity ?? 0.5);
        }

        $languageId = $expectations['language_id'] ?? null;
        if ($this->hasValue($languageId)) {
            $candidateLanguages = $candidate['language_ids'] ?? [];
            $candidateLanguages = is_array($candidateLanguages) ? $candidateLanguages : [$candidateLanguages];
            $candidateLanguages = array_map('strval', $candidateLanguages);
            $score = empty($candidateLanguages)
                ? 0.5
                : (in_array((string) $languageId, $candidateLanguages, true) ? 1.0 : 0.0);
            $add('language', 7, $score);
        }

        $childrenPreference = $this->normaliseAcceptability($expectations['children_acceptable'] ?? null);
        if ($childrenPreference === 'no') {
            $children = $candidate['children'] ?? null;
            $hasChildren = $this->booleanOrCount($children);
            $add('children', 4, $hasChildren === null ? 0.5 : ($hasChildren ? 0.0 : 1.0));
        }

        $smokingPreference = $this->normaliseAcceptability($expectations['smoking_acceptable'] ?? null);
        if ($smokingPreference === 'no') {
            $smokes = $this->yesNoValue($candidate['smoke'] ?? null);
            $add('smoking', 4, $smokes === null ? 0.5 : ($smokes ? 0.0 : 1.0));
        }

        $drinkingPreference = $this->normaliseAcceptability($expectations['drinking_acceptable'] ?? null);
        if ($drinkingPreference === 'no') {
            $drinks = $this->yesNoValue($candidate['drink'] ?? null);
            $add('drinking', 4, $drinks === null ? 0.5 : ($drinks ? 0.0 : 1.0));
        }

        foreach ([
            'complexion' => ['expectation' => 'complexion', 'candidate' => 'complexion', 'weight' => 4],
            'eye_color' => ['expectation' => 'eye_color', 'candidate' => 'eye_color', 'weight' => 3],
            'hair_color' => ['expectation' => 'hair_color', 'candidate' => 'hair_color', 'weight' => 3],
        ] as $key => $mapping) {
            $expected = $expectations[$mapping['expectation']] ?? null;
            if (!$this->hasMeaningfulPreference($expected)) {
                continue;
            }

            $actual = $candidate[$mapping['candidate']] ?? null;
            $score = !$this->hasValue($actual)
                ? 0.5
                : ($this->normaliseText($expected) === $this->normaliseText($actual) ? 1.0 : 0.0);
            $add($key, (float) $mapping['weight'], $score);
        }

        if ($totalWeight <= 0) {
            return [
                'percentage' => 0,
                'criteria_count' => 0,
                'details' => [],
            ];
        }

        return [
            'percentage' => (int) round(($weightedScore / $totalWeight) * 100),
            'criteria_count' => count($details),
            'details' => $details,
        ];
    }

    private function candidateData(User $candidate): array
    {
        $member = $candidate->member;
        $physical = $candidate->physical_attributes;
        $spiritual = $candidate->spiritual_backgrounds;
        $lifestyle = $candidate->lifestyles;
        $residency = $candidate->recidency;

        $presentAddress = $candidate->addresses
            ? $candidate->addresses->firstWhere('type', 'present')
            : null;

        $languageIds = [];
        if ($member && $this->hasValue($member->mothere_tongue)) {
            $languageIds[] = (string) $member->mothere_tongue;
        }
        if ($member && $this->hasValue($member->known_languages)) {
            $known = $member->known_languages;
            if (is_string($known)) {
                $decoded = json_decode($known, true);
                $known = is_array($decoded) ? $decoded : preg_split('/[,،\s]+/u', $known);
            }
            if (is_array($known)) {
                foreach ($known as $language) {
                    if (is_array($language)) {
                        $language = $language['id'] ?? $language['value'] ?? null;
                    }
                    if ($this->hasValue($language)) {
                        $languageIds[] = (string) $language;
                    }
                }
            }
        }

        return [
            'age' => $this->candidateAge($member),
            'marital_status_id' => $member->marital_status_id ?? null,
            'children' => $member->children ?? null,
            'country_id' => $residency->recidency_country_id ?? $presentAddress->country_id ?? null,
            'state_id' => $residency->recidency_state_id ?? $presentAddress->state_id ?? null,
            'religion_id' => $spiritual->religion_id ?? null,
            'height' => $physical->height ?? null,
            'weight' => $physical->weight ?? null,
            'complexion' => $physical->complexion ?? null,
            'eye_color' => $physical->eye_color ?? null,
            'hair_color' => $physical->hair_color ?? null,
            'education' => $candidate->education ? $candidate->education->pluck('degree')->filter()->values()->all() : [],
            'professions' => $candidate->career ? $candidate->career->pluck('designation')->filter()->values()->all() : [],
            'language_ids' => array_values(array_unique($languageIds)),
            'smoke' => $lifestyle->smoke ?? null,
            'drink' => $lifestyle->drink ?? null,
        ];
    }

    private function candidateAge($member): ?int
    {
        if (!$member || empty($member->birthday)) {
            return null;
        }

        try {
            return (int) \Carbon\Carbon::parse($member->birthday)->age;
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function hasPreferences(array $expectations): bool
    {
        return ($this->scoreData($expectations, [])['criteria_count'] ?? 0) > 0;
    }

    private function hasValue($value): bool
    {
        if ($value === null || is_array($value) || is_object($value)) {
            return false;
        }

        $text = trim((string) $value);
        return $text !== '' && $text !== '0' && strtolower($text) !== 'null';
    }

    private function hasMeaningfulPreference($value): bool
    {
        if (!$this->hasValue($value)) {
            return false;
        }

        $normalised = $this->normaliseText($value);
        return !in_array($normalised, [
            'لا يهم',
            'لايهم',
            'does not matter',
            'doesnt matter',
            'any',
            'no preference',
        ], true);
    }

    private function normaliseAcceptability($value): string
    {
        $value = strtolower(trim((string) ($value ?? '')));
        if (in_array($value, ['', 'dose_not_matter', 'does_not_matter'], true)) {
            return 'does_not_matter';
        }
        return in_array($value, ['yes', 'no'], true) ? $value : 'does_not_matter';
    }

    private function numericOrNull($value): ?float
    {
        if (!$this->hasValue($value) || !is_numeric($value)) {
            return null;
        }
        $number = (float) $value;
        return $number > 0 ? $number : null;
    }

    private function normaliseHeight($value): ?float
    {
        $height = $this->numericOrNull($value);
        if ($height === null) {
            return null;
        }
        return $height <= 3 ? $height * 100 : $height;
    }

    private function rangeScore(float $value, ?float $minimum, ?float $maximum, float $near, float $far): float
    {
        if ($minimum !== null && $value < $minimum) {
            $distance = $minimum - $value;
        } elseif ($maximum !== null && $value > $maximum) {
            $distance = $value - $maximum;
        } else {
            return 1.0;
        }

        if ($distance <= $near) {
            return 0.7;
        }
        if ($distance <= $far) {
            return 0.3;
        }
        return 0.0;
    }

    private function educationLevel($value): int
    {
        $text = $this->normaliseText($value);
        $levels = [
            1 => ['اعدادي', 'preparatory'],
            2 => ['ثانوي', 'secondary'],
            3 => ['دبلوم', 'diploma'],
            4 => ['بكالوريوس', 'bachelor', 'licence', 'license'],
            5 => ['ماجستير', 'master'],
            6 => ['دكتوراه', 'doctorate', 'doctoral', 'phd'],
        ];

        foreach ($levels as $level => $terms) {
            foreach ($terms as $term) {
                if (str_contains($text, $term)) {
                    return $level;
                }
            }
        }
        return 0;
    }

    private function textSimilarity($expected, array $actualValues): ?float
    {
        $expectedText = $this->normaliseText($expected);
        if ($expectedText === '') {
            return null;
        }

        $best = null;
        $expectedTokens = array_values(array_filter(explode(' ', $expectedText)));
        foreach ($actualValues as $actual) {
            $actualText = $this->normaliseText($actual);
            if ($actualText === '') {
                continue;
            }
            if ($actualText === $expectedText) {
                return 1.0;
            }
            if (str_contains($actualText, $expectedText) || str_contains($expectedText, $actualText)) {
                $best = max($best ?? 0.0, 0.85);
                continue;
            }

            $actualTokens = array_values(array_filter(explode(' ', $actualText)));
            $intersection = count(array_intersect($expectedTokens, $actualTokens));
            $denominator = max(1, min(count($expectedTokens), count($actualTokens)));
            $similarity = $intersection / $denominator;
            $best = max($best ?? 0.0, $similarity);
        }

        return $best;
    }

    private function normaliseText($value): string
    {
        $text = trim((string) ($value ?? ''));
        if (function_exists('mb_strtolower')) {
            $text = mb_strtolower($text, 'UTF-8');
        } else {
            $text = strtolower($text);
        }

        $text = strtr($text, [
            'أ' => 'ا',
            'إ' => 'ا',
            'آ' => 'ا',
            'ى' => 'ي',
            'ة' => 'ه',
            'ـ' => '',
        ]);
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $text) ?? $text;
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    private function booleanOrCount($value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value > 0;
        }

        $normalised = $this->normaliseText($value);
        if (in_array($normalised, ['yes', 'true', 'نعم'], true)) {
            return true;
        }
        if (in_array($normalised, ['no', 'false', 'لا'], true)) {
            return false;
        }
        return null;
    }

    private function yesNoValue($value): ?bool
    {
        return $this->booleanOrCount($value);
    }
}
