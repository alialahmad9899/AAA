<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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
        $candidates = $this->eligibleCandidatesQuery($viewer);
        if ($candidates === null) {
            return collect();
        }

        return $this->scoreCandidates($viewer, $candidates->get(), true);
    }

    /**
     * Score the full eligible pool in bounded chunks, then hydrate full models
     * and relations for only the requested globally-ranked page.
     *
     * @return array{members:Collection<int,User>,has_more:bool}
     */
    public function getMatchesPage(User $viewer, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(30, $perPage));
        $candidates = $this->eligibleCandidatesQuery($viewer);
        if ($candidates === null) {
            return ['members' => collect(), 'has_more' => false];
        }

        $rankedRows = [];
        $candidates->chunkById(200, function (Collection $chunk) use ($viewer, &$rankedRows) {
            $scoredChunk = $this->scoreCandidates($viewer, $chunk, true);
            foreach ($scoredChunk as $candidate) {
                $rankedRows[] = [
                    'id' => (int) $candidate->id,
                    'percentage' => $candidate->profile_match_percentage,
                    'details' => $candidate->match_details,
                    'criteria_count' => (int) $candidate->match_criteria_count,
                    'evaluated_criteria_count' => (int) $candidate->match_evaluated_criteria_count,
                    'status' => (string) $candidate->match_status,
                    'created_at' => (string) ($candidate->created_at ?? ''),
                ];
            }
        });

        $rankedRows = $this->sortRankedRows($rankedRows);
        $offset = ($page - 1) * $perPage;
        $pageRows = array_slice($rankedRows, $offset, $perPage);
        $hasMore = count($rankedRows) > ($offset + count($pageRows));
        if ($pageRows === []) {
            return ['members' => collect(), 'has_more' => $hasMore];
        }

        $ids = array_column($pageRows, 'id');
        $usersById = User::query()
            ->with($this->candidateRelations())
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $members = collect();
        foreach ($pageRows as $row) {
            /** @var User|null $candidate */
            $candidate = $usersById->get($row['id']);
            if (!$candidate) {
                continue;
            }

            $candidate->setAttribute('profile_match_percentage', $row['percentage']);
            $candidate->setAttribute('match_details', $row['details']);
            $candidate->setAttribute('match_criteria_count', $row['criteria_count']);
            $candidate->setAttribute('match_evaluated_criteria_count', $row['evaluated_criteria_count']);
            $candidate->setAttribute('match_status', $row['status']);
            $members->push($candidate);
        }

        return ['members' => $members, 'has_more' => $hasMore];
    }

    /**
     * Order compact score rows with the same global tie-breaking rules used by
     * scoreCandidates(). Exposed so the ranking contract can be unit-tested.
     */
    public function sortRankedRows(array $rows): array
    {
        usort($rows, static function (array $left, array $right): int {
            $leftScore = $left['percentage'];
            $rightScore = $right['percentage'];
            if ($leftScore === null && $rightScore !== null) return 1;
            if ($leftScore !== null && $rightScore === null) return -1;

            if ($leftScore !== null && $rightScore !== null) {
                $scoreOrder = (int) $rightScore <=> (int) $leftScore;
                if ($scoreOrder !== 0) return $scoreOrder;
            }

            $dateOrder = strcmp($right['created_at'], $left['created_at']);
            return $dateOrder !== 0 ? $dateOrder : ($right['id'] <=> $left['id']);
        });

        return array_values($rows);
    }

    /**
     * Eager-loaded relationships needed to calculate compatibility and build a card.
     *
     * @return array<int,string>
     */
    private function candidateRelations(): array
    {
        return [
            'member.marital_status',
            'member.mothereTongue',
            'addresses.country',
            'addresses.state',
            'addresses.city',
            'recidency',
            'physical_attributes',
            'spiritual_backgrounds.religion',
            'spiritual_backgrounds.caste',
            'spiritual_backgrounds.sub_caste',
            'spiritual_backgrounds.family_value',
            'lifestyles',
            'education',
            'career',
        ];
    }

    /**
     * Shared eligibility query for paginated and legacy full-list requests.
     */
    private function eligibleCandidatesQuery(User $viewer): ?Builder
    {
        $viewer->loadMissing(['member', 'partner_expectations']);
        $gender = (int) optional($viewer->member)->gender;
        if (!in_array($gender, [1, 2], true)) {
            return null;
        }

        $oppositeGender = $gender === 1 ? 2 : 1;
        $viewerId = (int) $viewer->id;
        $query = User::query()
            ->with($this->candidateRelations())
            ->where('user_type', 'member')
            ->where('id', '!=', $viewerId)
            ->where('blocked', 0)
            ->where('deactivated', 0)
            ->whereHas('member', function ($memberQuery) use ($oppositeGender) {
                $memberQuery->where('gender', $oppositeGender);
            })
            ->whereNotIn('id', function ($ignoredByViewer) use ($viewerId) {
                $ignoredByViewer->select('user_id')
                    ->from('ignored_users')
                    ->where('ignored_by', $viewerId);
            })
            ->whereNotIn('id', function ($ignoredViewer) use ($viewerId) {
                $ignoredViewer->select('ignored_by')
                    ->from('ignored_users')
                    ->where('user_id', $viewerId);
            });

        $this->applyPartnerAgeRangeFilter($query, $viewer);
        return $query;
    }

    /**
     * Normalise canonical and legacy partner-age fields into one safe range.
     * Missing sides use the supported adult-age boundary; invalid/reversed
     * persisted values are ignored rather than producing an empty home feed.
     *
     * @return array{min_age:int,max_age:int}|null
     */
    public function normalisePartnerAgeRange(array $expectations): ?array
    {
        $readAge = static function (array $keys) use ($expectations): ?int {
            foreach ($keys as $key) {
                $raw = $expectations[$key] ?? null;
                if (!is_numeric($raw)) {
                    continue;
                }
                $numeric = (float) $raw;
                if (!is_finite($numeric) || floor($numeric) !== $numeric) {
                    continue;
                }
                $age = (int) $numeric;
                if ($age >= 18 && $age <= 100) {
                    return $age;
                }
            }
            return null;
        };

        $minimum = $readAge(['min_age', 'partner_age']);
        $maximum = $readAge(['max_age', 'partner_max_age']);
        if ($minimum === null && $maximum === null) {
            return null;
        }

        $minimum ??= 18;
        $maximum ??= 100;
        if ($minimum > $maximum) {
            return null;
        }

        return ['min_age' => $minimum, 'max_age' => $maximum];
    }

    /**
     * Apply the member's saved age range before pagination so out-of-range
     * profiles cannot leak into later pages of the home feed.
     */
    public function applyPartnerAgeRangeFilter(Builder $query, User $viewer): Builder
    {
        $viewer->loadMissing(['partner_expectations']);
        $expectations = $viewer->partner_expectations;
        $ageRange = $this->normalisePartnerAgeRange(
            $expectations ? $expectations->getAttributes() : []
        );
        if ($ageRange === null) {
            return $query;
        }

        $today = now()->startOfDay();
        $youngestAllowedBirthDate = $today->copy()
            ->subYears($ageRange['min_age'])
            ->toDateString();
        $oldestAllowedBirthDateExclusive = $today->copy()
            ->subYears($ageRange['max_age'] + 1)
            ->toDateString();

        $query->whereHas('member', static function (Builder $memberQuery) use (
            $youngestAllowedBirthDate,
            $oldestAllowedBirthDateExclusive
        ) {
            $memberQuery
                ->whereNotNull('birthday')
                ->where('birthday', '<=', $youngestAllowedBirthDate)
                ->where('birthday', '>', $oldestAllowedBirthDateExclusive);
        });

        return $query;
    }

    /**
     * إرفاق نتيجة الحساب بكل مرشح ظاهر، مع إبقاء ترتيب القائمة عند إيقاف
     * مفتاح المطابقة، أو ترتيبها تنازلياً عند تشغيله.
     */
    public function scoreCandidates(User $viewer, Collection $candidates, bool $sortByMatch = false): Collection
    {
        $viewer->loadMissing(['member', 'partner_expectations']);

        // Eloquent Collection loads missing relations in batches, avoiding
        // per-candidate queries while each matching chunk is scored.
        if (method_exists($candidates, 'loadMissing')) {
            $candidates->loadMissing($this->candidateRelations());
        }

        $expectations = $viewer->partner_expectations;
        $preferenceData = $expectations ? $expectations->getAttributes() : [];
        $hasPreferences = $this->hasPreferences($preferenceData);

        $scored = $candidates->map(function (User $candidate) use ($preferenceData, $hasPreferences) {
            $result = $hasPreferences
                ? $this->scoreData($preferenceData, $this->candidateData($candidate))
                : $this->emptyScore('no_preferences');

            $candidate->setAttribute('profile_match_percentage', $result['percentage']);
            $candidate->setAttribute('match_details', $result['details']);
            $candidate->setAttribute('match_criteria_count', $result['criteria_count']);
            $candidate->setAttribute('match_evaluated_criteria_count', $result['evaluated_criteria_count']);
            $candidate->setAttribute('match_status', $result['status']);

            return $candidate;
        });

        if (!$sortByMatch) {
            return $scored->values();
        }

        return $scored->sort(function (User $left, User $right) {
            $leftScore = $left->profile_match_percentage;
            $rightScore = $right->profile_match_percentage;

            // Profiles with an actual score come first. Uncalculated scores
            // remain visible at the bottom and are never represented as zero.
            if ($leftScore === null && $rightScore !== null) return 1;
            if ($leftScore !== null && $rightScore === null) return -1;

            if ($leftScore !== null && $rightScore !== null) {
                $scoreOrder = (int) $rightScore <=> (int) $leftScore;
                if ($scoreOrder !== 0) return $scoreOrder;
            }

            $dateOrder = strcmp(
                (string) ($right->created_at ?? ''),
                (string) ($left->created_at ?? '')
            );
            return $dateOrder !== 0 ? $dateOrder : ((int) $right->id <=> (int) $left->id);
        })->values();
    }

    /**
     * حساب توافق مرشح واحد، ويُستخدم أيضاً في شاشة الملف الشخصي.
     */
    public function scoreFor(User $viewer, User $candidate): ?array
    {
        $viewer->loadMissing(['member', 'partner_expectations']);
        $candidate->loadMissing([
            'member.marital_status',
            'member.mothereTongue',
            'addresses.country',
            'addresses.state',
            'addresses.city',
            'recidency',
            'physical_attributes',
            'spiritual_backgrounds.religion',
            'spiritual_backgrounds.caste',
            'spiritual_backgrounds.sub_caste',
            'spiritual_backgrounds.family_value',
            'lifestyles',
            'education',
            'career',
        ]);

        if ((int) $viewer->id === (int) $candidate->id ||
            !$viewer->member || !$candidate->member ||
            (int) $viewer->member->gender === (int) $candidate->member->gender ||
            !in_array((int) $viewer->member->gender, [1, 2], true) ||
            !in_array((int) $candidate->member->gender, [1, 2], true) ||
            $candidate->user_type !== 'member' ||
            (int) $candidate->blocked === 1 ||
            (int) $candidate->deactivated === 1) {
            return $this->emptyScore('not_eligible');
        }

        $isIgnoredByViewer = \App\Models\IgnoredUser::where('user_id', $candidate->id)
            ->where('ignored_by', $viewer->id)
            ->exists();
        $hasIgnoredViewer = \App\Models\IgnoredUser::where('user_id', $viewer->id)
            ->where('ignored_by', $candidate->id)
            ->exists();

        if ($isIgnoredByViewer || $hasIgnoredViewer) {
            return $this->emptyScore('not_eligible');
        }

        $expectations = $viewer->partner_expectations;
        if (!$expectations || !$this->hasPreferences($expectations->getAttributes())) {
            return $this->emptyScore('no_preferences');
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
     * @return array{percentage:?int,criteria_count:int,evaluated_criteria_count:int,status:string,details:array<int,array<string,mixed>>}
     */
    public function scoreData(array $expectations, array $candidate): array
    {
        $weightedScore = 0.0;
        $totalWeight = 0.0;
        $details = [];
        $evaluatedCriteriaCount = 0;

        $add = function (string $key, float $weight, ?float $score) use (&$weightedScore, &$totalWeight, &$details, &$evaluatedCriteriaCount) {
            if ($score !== null) {
                $score = max(0.0, min(1.0, $score));
                $weightedScore += $weight * $score;
                $totalWeight += $weight;
                $evaluatedCriteriaCount++;
            }

            $details[] = [
                'key' => $key,
                'score' => $score === null ? null : (int) round($score * 100),
                'weight' => $weight,
                'evaluated' => $score !== null,
            ];
        };

        $countryId = $expectations['preferred_country_id'] ?? null;
        if ($this->hasValue($countryId)) {
            $candidateCountry = $candidate['country_id'] ?? null;
            $score = !$this->hasValue($candidateCountry)
                ? null
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
                $score = null;
            } elseif ((string) $candidateState === (string) $stateId) {
                $score = 1.0;
            } else {
                $score = $sameCountry ? 0.4 : 0.1;
            }
            $add('state', 7, $score);
        }

        $ageRange = $this->normalisePartnerAgeRange($expectations);
        if ($ageRange !== null) {
            $age = $this->numericOrNull($candidate['age'] ?? null);
            $score = $age === null ? null : $this->rangeScore(
                $age,
                (float) $ageRange['min_age'],
                (float) $ageRange['max_age'],
                2,
                5
            );
            $add('age', 12, $score);
        }

        $maritalStatus = $expectations['marital_status_id'] ?? null;
        if ($this->hasValue($maritalStatus)) {
            $candidateStatus = $candidate['marital_status_id'] ?? null;
            $score = !$this->hasValue($candidateStatus)
                ? null
                : ((string) $candidateStatus === (string) $maritalStatus ? 1.0 : 0.0);
            $add('marital_status', 10, $score);
        }

        $religionId = $expectations['religion_id'] ?? null;
        if ($this->hasValue($religionId)) {
            $candidateReligion = $candidate['religion_id'] ?? null;
            $score = !$this->hasValue($candidateReligion)
                ? null
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
            $score = $height === null ? null : $this->rangeScore($height, $minHeight, $maxHeight, 5, 10);
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
            $score = $weight === null ? null : $this->rangeScore($weight, $minWeight, $maxWeight, 5, 10);
            $add('weight', 7, $score);
        }

        $education = $expectations['education'] ?? null;
        if ($this->hasMeaningfulPreference($education)) {
            $candidateDegrees = $candidate['education'] ?? [];
            $candidateDegrees = is_array($candidateDegrees) ? $candidateDegrees : [$candidateDegrees];
            $levels = array_filter(array_map([$this, 'educationLevel'], $candidateDegrees));
            if (empty($candidateDegrees)) {
                $score = null;
            } else {
                $preferredLevel = $this->educationLevel($education);
                if ($preferredLevel === 0) {
                    $score = $this->textSimilarity($education, $candidateDegrees);
                } elseif (!empty($levels)) {
                    $bestLevel = max($levels);
                    $score = $bestLevel >= $preferredLevel
                        ? 1.0
                        : ($bestLevel === $preferredLevel - 1 ? 0.6 : 0.2);
                } else {
                    // Custom/unmapped candidate degree is unknown for a ranked-level
                    // preference; do not invent a neutral 50% result.
                    $score = null;
                }
            }
            $add('education', 8, $score);
        }

        $profession = $expectations['profession'] ?? null;
        if ($this->hasMeaningfulPreference($profession)) {
            $candidateProfessions = $candidate['professions'] ?? [];
            $similarity = $this->textSimilarity($profession, is_array($candidateProfessions) ? $candidateProfessions : [$candidateProfessions]);
            $add('profession', 5, $similarity);
        }

        $languageId = $expectations['language_id'] ?? null;
        if ($this->hasValue($languageId)) {
            $candidateLanguages = $candidate['language_ids'] ?? [];
            $candidateLanguages = is_array($candidateLanguages) ? $candidateLanguages : [$candidateLanguages];
            $candidateLanguages = array_map('strval', $candidateLanguages);
            $score = empty($candidateLanguages)
                ? null
                : (in_array((string) $languageId, $candidateLanguages, true) ? 1.0 : 0.0);
            $add('language', 7, $score);
        }

        $childrenPreference = $this->normaliseAcceptability($expectations['children_acceptable'] ?? null);
        if ($childrenPreference === 'no') {
            $children = $candidate['children'] ?? null;
            $hasChildren = $this->booleanOrCount($children);
            $add('children', 4, $hasChildren === null ? null : ($hasChildren ? 0.0 : 1.0));
        }

        $smokingPreference = $this->normaliseAcceptability($expectations['smoking_acceptable'] ?? null);
        if ($smokingPreference === 'no') {
            $smokes = $this->yesNoValue($candidate['smoke'] ?? null);
            $add('smoking', 4, $smokes === null ? null : ($smokes ? 0.0 : 1.0));
        }

        $drinkingPreference = $this->normaliseAcceptability($expectations['drinking_acceptable'] ?? null);
        if ($drinkingPreference === 'no') {
            $drinks = $this->yesNoValue($candidate['drink'] ?? null);
            $add('drinking', 4, $drinks === null ? null : ($drinks ? 0.0 : 1.0));
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
                ? null
                : ($this->normaliseComparableText($expected) === $this->normaliseComparableText($actual) ? 1.0 : 0.0);
            $add($key, (float) $mapping['weight'], $score);
        }

        $casteId = $expectations['caste_id'] ?? null;
        if ($this->hasValue($casteId)) {
            $candidateCasteId = $candidate['caste_id'] ?? null;
            $add('caste', 4, !$this->hasValue($candidateCasteId)
                ? null
                : ((string) $candidateCasteId === (string) $casteId ? 1.0 : 0.0));
        }

        $subCasteId = $expectations['sub_caste_id'] ?? null;
        if ($this->hasValue($subCasteId)) {
            $candidateSubCasteId = $candidate['sub_caste_id'] ?? null;
            $add('sub_caste', 2, !$this->hasValue($candidateSubCasteId)
                ? null
                : ((string) $candidateSubCasteId === (string) $subCasteId ? 1.0 : 0.0));
        }

        $familyValueId = $expectations['family_value_id'] ?? null;
        if ($this->hasValue($familyValueId)) {
            $candidateFamilyValueId = $candidate['family_value_id'] ?? null;
            $add('family_value', 3, !$this->hasValue($candidateFamilyValueId)
                ? null
                : ((string) $candidateFamilyValueId === (string) $familyValueId ? 1.0 : 0.0));
        }

        $personalValue = $expectations['personal_value'] ?? null;
        if ($this->hasMeaningfulPreference($personalValue)) {
            $actualPersonalValue = $candidate['personal_value'] ?? null;
            $add('personal_value', 3, !$this->hasValue($actualPersonalValue)
                ? null
                : $this->textSimilarity($personalValue, [$actualPersonalValue]));
        }

        $bodyType = $expectations['body_type'] ?? null;
        if ($this->hasMeaningfulPreference($bodyType)) {
            $actualBodyType = $candidate['body_type'] ?? null;
            $add('body_type', 4, !$this->hasValue($actualBodyType)
                ? null
                : $this->textSimilarity($bodyType, [$actualBodyType]));
        }

        $criteriaCount = count($details);
        $status = $criteriaCount === 0
            ? 'no_preferences'
            : ($evaluatedCriteriaCount === 0 ? 'insufficient_data' : 'calculated');

        return [
            'percentage' => $totalWeight > 0
                ? (int) round(($weightedScore / $totalWeight) * 100)
                : null,
            'criteria_count' => $criteriaCount,
            'evaluated_criteria_count' => $evaluatedCriteriaCount,
            'status' => $status,
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
            'caste_id' => $spiritual->caste_id ?? null,
            'sub_caste_id' => $spiritual->sub_caste_id ?? null,
            'family_value_id' => $spiritual->family_value_id ?? null,
            'personal_value' => $spiritual->personal_value ?? null,
            'body_type' => $physical->body_type ?? null,
            'height' => $physical->height ?? null,
            'weight' => $physical->weight ?? null,
            'complexion' => $physical->complexion ?? null,
            'eye_color' => $physical->eye_color ?? null,
            'hair_color' => $physical->hair_color ?? null,
            'education' => $candidate->education ? $candidate->education->pluck('degree')->filter()->values()->all() : [],
            'professions' => $this->candidateProfessions($candidate),
            'language_ids' => array_values(array_unique($languageIds)),
            'smoke' => $lifestyle->smoke ?? null,
            'drink' => $lifestyle->drink ?? null,
        ];
    }

    private function candidateProfessions(User $candidate): array
    {
        $careers = $candidate->career ?? collect();
        $currentCareers = $careers->where('present', 1);
        $selectedCareers = $currentCareers->isNotEmpty() ? $currentCareers : $careers;

        return $selectedCareers
            ->pluck('designation')
            ->filter()
            ->values()
            ->all();
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

    private function emptyScore(string $status): array
    {
        return [
            'percentage' => null,
            'criteria_count' => 0,
            'evaluated_criteria_count' => 0,
            'status' => $status,
            'details' => [],
        ];
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
            'doesn t matter',
            'dont care',
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
        $expectedText = $this->normaliseComparableText($expected);
        if ($expectedText === '') {
            return null;
        }

        $best = null;
        $expectedTokens = array_values(array_filter(explode(' ', $expectedText)));
        foreach ($actualValues as $actual) {
            $actualText = $this->normaliseComparableText($actual);
            if ($actualText === '') {
                continue;
            }
            if ($actualText === $expectedText) {
                return 1.0;
            }
            $actualTokens = array_values(array_unique(array_filter(explode(' ', $actualText))));
            $intersection = count(array_intersect($expectedTokens, $actualTokens));
            $union = count(array_unique(array_merge($expectedTokens, $actualTokens)));
            $similarity = $union > 0 ? $intersection / $union : 0.0;
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

    /**
     * Normalize known Arabic/English options to one canonical comparison value.
     * Free-form values remain unchanged; only documented option labels are mapped.
     */
    private function normaliseComparableText($value): string
    {
        $text = $this->normaliseText($value);
        $aliases = [
            // Complexion.
            'ابيض' => 'fair white',
            'fair white' => 'fair white',
            'حنطي فاتح' => 'light tan',
            'light tan' => 'light tan',
            'حنطي' => 'wheat tan',
            'wheat tan' => 'wheat tan',
            'اسمر' => 'dark tan',
            'dark tan' => 'dark tan',
            'اسمر داكن' => 'dark',
            'dark' => 'dark',
            // Eye colours.
            'بني' => 'brown',
            'brown' => 'brown',
            'اسود' => 'black',
            'black' => 'black',
            'ازرق' => 'blue',
            'blue' => 'blue',
            'اخضر' => 'green',
            'green' => 'green',
            'عسلي' => 'hazel',
            'hazel' => 'hazel',
            'رمادي' => 'grey',
            'grey' => 'grey',
            'gray' => 'grey',
            // Hair colours.
            'بني داكن' => 'dark brown',
            'dark brown' => 'dark brown',
            'بني فاتح' => 'light brown',
            'light brown' => 'light brown',
            'اشقر' => 'blonde',
            'blonde' => 'blonde',
            'احمر' => 'red',
            'red' => 'red',
            'رمادي شيب' => 'grey white',
            'grey white' => 'grey white',
            // Body types.
            'نحيف رشيق' => 'slim',
            'نحيف' => 'slim',
            'رشيق' => 'slim',
            'slim' => 'slim',
            'متوسط متناسق' => 'average',
            'متوسط' => 'average',
            'average' => 'average',
            'ممتلئ ضخم' => 'heavy',
            'ممتلئ' => 'heavy',
            'heavy' => 'heavy',
            'رياضي متناسق' => 'athletic',
            'رياضي' => 'athletic',
            'athletic' => 'athletic',
            // Personal values.
            'ملتزم جدا' => 'very religious',
            'very religious' => 'very religious',
            'ملتزم' => 'religious',
            'religious' => 'religious',
            'متوسط معتدل' => 'moderate',
            'معتدل' => 'moderate',
            'moderate' => 'moderate',
            'غير ملتزم' => 'not practicing',
            'not practicing' => 'not practicing',
            // Common occupation labels currently offered in the app.
            'مهندس' => 'engineer',
            'engineer' => 'engineer',
            'مهندس برمجيات' => 'software engineer',
            'software engineer' => 'software engineer',
            'طبيب طبيبه' => 'doctor',
            'طبيب' => 'doctor',
            'طبيبه' => 'doctor',
            'doctor' => 'doctor',
            'معلم معلمه' => 'teacher',
            'معلم' => 'teacher',
            'معلمه' => 'teacher',
            'teacher' => 'teacher',
            'مستقل' => 'freelancer',
            'freelancer' => 'freelancer',
            'freelancer independent' => 'freelancer',
        ];

        return $aliases[$text] ?? $text;
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
