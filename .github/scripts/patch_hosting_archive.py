#!/usr/bin/env python3
"""Patch the Laravel application embedded in the hosting backup archive.

The patch is deliberately fail-fast: every expected source block must be found
exactly once before the archive is rewritten. The modified PHP files are also
exported under اصلاحات 2 so they can be uploaded individually to the Laravel root.
"""
from __future__ import annotations

import json
import os
import tempfile
import zipfile
from pathlib import Path

ARCHIVE = Path("ملفات الاتسضافة.zip")
OUTPUT_DIR = Path("اصلاحات 2/Laravel")
PATCHED: dict[str, str] = {}


def require_replace(source: str, old: str, new: str, label: str) -> str:
    count = source.count(old)
    if count != 1:
        raise RuntimeError(f"{label}: expected one exact source block, found {count}")
    return source.replace(old, new, 1)


def replace_method(source: str, start: str, end: str, new_method: str, label: str) -> str:
    start_at = source.find(start)
    if start_at < 0:
        raise RuntimeError(f"{label}: start marker not found: {start}")
    end_at = source.find(end, start_at + len(start))
    if end_at < 0:
        raise RuntimeError(f"{label}: end marker not found: {end}")
    return source[:start_at] + new_method.rstrip() + "\n\n    " + source[end_at:]


def patch_profile_controller(source: str) -> str:
    source = require_replace(
        source,
        "use App\\Models\\User;\n",
        "use App\\Models\\Upload;\nuse App\\Models\\User;\n",
        "ProfileController Upload import",
    )

    source = replace_method(
        source,
        "    public function basic_info_update(ProfileRequest $request)",
        "    public function present_address()",
        r"""    public function basic_info_update(ProfileRequest $request)
    {
        $storedPhotoPath = null;

        try {
            DB::transaction(function () use ($request, &$storedPhotoPath) {
                $user = User::lockForUpdate()->findOrFail(auth()->id());
                $member = Member::where('user_id', $user->id)->lockForUpdate()->first();

                if (!$member) {
                    throw new \RuntimeException('Member profile record is missing.');
                }

                $input = $request->all();

                foreach (['first_name', 'last_name', 'phone'] as $field) {
                    if (array_key_exists($field, $input)) {
                        $user->{$field} = $input[$field];
                    }
                }

                if ($request->hasFile('photo')) {
                    $uploadId = upload_api_file($request->file('photo'));
                    $upload = $uploadId ? Upload::find($uploadId) : null;

                    if (!$upload || empty($upload->file_name)) {
                        throw new \RuntimeException('Unable to persist the uploaded profile photo.');
                    }

                    $storedPhotoPath = $upload->file_name;
                    $user->photo = $upload->id;
                    $user->avatar_original = $upload->id;
                    $user->avatar = $upload->file_name;

                    $approvalRequired = Setting::where('type', 'profile_picture_approval_by_admin')
                        ->value('value');
                    $user->photo_approved = $approvalRequired ? 0 : 1;
                }

                if (array_key_exists('gender', $input)) {
                    $member->gender = $input['gender'];
                }
                if (array_key_exists('on_behalf', $input)) {
                    $member->on_behalves_id = $input['on_behalf'];
                } elseif (array_key_exists('on_behalves_id', $input)) {
                    $member->on_behalves_id = $input['on_behalves_id'];
                }
                if (array_key_exists('date_of_birth', $input) && !empty($input['date_of_birth'])) {
                    $member->birthday = \Carbon\Carbon::parse($input['date_of_birth'])->format('Y-m-d');
                }
                if (array_key_exists('marital_status', $input)) {
                    $member->marital_status_id = $input['marital_status'];
                } elseif (array_key_exists('marital_status_id', $input)) {
                    $member->marital_status_id = $input['marital_status_id'];
                } elseif (array_key_exists('maritial_status_id', $input)) {
                    $member->marital_status_id = $input['maritial_status_id'];
                }
                if (array_key_exists('children', $input)) {
                    $member->children = $input['children'];
                }
                if (array_key_exists('introduction', $input)) {
                    $member->introduction = $input['introduction'];
                }

                $user->save();
                $member->save();
            });

            return $this->success_message('Member basic info has been updated successfully.');
        } catch (\Throwable $e) {
            if ($storedPhotoPath) {
                try {
                    \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))
                        ->delete($storedPhotoPath);
                } catch (\Throwable $cleanupError) {
                    \Log::warning('Profile photo cleanup failed: ' . $cleanupError->getMessage());
                }
            }

            \Log::error('Profile basic-info update failed: ' . $e->getMessage());

            return response()->json([
                'result' => false,
                'message' => translate('Unable to update profile information. Please try again.'),
            ], 500);
        }
    }""",
        "ProfileController basic_info_update",
    )

    source = replace_method(
        source,
        "    public function physical_attributes_update(Request $request)",
        "    public function member_language()",
        r"""    public function physical_attributes_update(Request $request)
    {
        $input = $request->all();
        $this->validate($request, [
            'height'      => ['sometimes', 'nullable', 'numeric', 'between:0.4,2.5'],
            'weight'      => ['sometimes', 'nullable', 'numeric', 'between:1,999.99'],
            'eye_color'   => ['sometimes', 'nullable', 'string', 'max:50'],
            'eye_colour'  => ['sometimes', 'nullable', 'string', 'max:50'],
            'hair_color'  => ['sometimes', 'nullable', 'string', 'max:50'],
            'hair_colour' => ['sometimes', 'nullable', 'string', 'max:50'],
            'complexion'  => ['sometimes', 'nullable', 'string', 'max:50'],
            'blood_group' => ['sometimes', 'nullable', 'string', 'max:10'],
            'body_type'   => ['sometimes', 'nullable', 'string', 'max:50'],
            'body_art'    => ['sometimes', 'nullable', 'string', 'max:50'],
            'disability'  => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $values = [];
        foreach (['height', 'weight', 'complexion', 'blood_group', 'body_type', 'body_art', 'disability'] as $field) {
            if (array_key_exists($field, $input)) {
                $values[$field] = $input[$field];
            }
        }
        if (array_key_exists('eye_color', $input) || array_key_exists('eye_colour', $input)) {
            $values['eye_color'] = $input['eye_color'] ?? $input['eye_colour'];
        }
        if (array_key_exists('hair_color', $input) || array_key_exists('hair_colour', $input)) {
            $values['hair_color'] = $input['hair_color'] ?? $input['hair_colour'];
        }

        if (empty($values)) {
            return response()->json([
                'result' => false,
                'message' => translate('No physical attributes were provided for update.'),
            ], 422);
        }

        $attribute = PhysicalAttribute::firstOrNew(['user_id' => auth()->id()]);
        foreach ($values as $field => $value) {
            $attribute->{$field} = $value;
        }
        $attribute->save();

        return $this->success_message('Physical Attribute Info has been updated successfully');
    }""",
        "ProfileController physical_attributes_update",
    )

    source = replace_method(
        source,
        "    public function residency_info_update(Request $request)",
        "    public function spiritual_background()",
        r"""    public function residency_info_update(Request $request)
    {
        $input = $request->all();
        $this->validate($request, [
            'birth_country_id'      => ['sometimes', 'nullable', 'integer', 'exists:countries,id'],
            'recidency_country_id'  => ['sometimes', 'nullable', 'integer', 'exists:countries,id'],
            'residency_country_id'  => ['sometimes', 'nullable', 'integer', 'exists:countries,id'],
            'growup_country_id'     => ['sometimes', 'nullable', 'integer', 'exists:countries,id'],
            'recidency_state_id'    => ['sometimes', 'nullable', 'integer', 'exists:states,id'],
            'residency_state_id'    => ['sometimes', 'nullable', 'integer', 'exists:states,id'],
            'state_id'              => ['sometimes', 'nullable', 'integer', 'exists:states,id'],
            'recidency_city_id'     => ['sometimes', 'nullable', 'integer', 'exists:cities,id'],
            'residency_city_id'     => ['sometimes', 'nullable', 'integer', 'exists:cities,id'],
            'city_id'               => ['sometimes', 'nullable', 'integer', 'exists:cities,id'],
            'immigration_status'    => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $residency = Recidency::firstOrNew(['user_id' => auth()->id()]);
        $oldCountryId = $residency->recidency_country_id;

        foreach (['birth_country_id', 'growup_country_id', 'immigration_status'] as $field) {
            if (array_key_exists($field, $input)) {
                $residency->{$field} = $input[$field];
            }
        }

        $countryWasProvided = array_key_exists('recidency_country_id', $input)
            || array_key_exists('residency_country_id', $input);
        $countryId = array_key_exists('recidency_country_id', $input)
            ? $input['recidency_country_id']
            : (array_key_exists('residency_country_id', $input)
                ? $input['residency_country_id']
                : $oldCountryId);

        if ($countryWasProvided) {
            $residency->recidency_country_id = $countryId;
        }

        $stateWasProvided = array_key_exists('recidency_state_id', $input)
            || array_key_exists('residency_state_id', $input)
            || array_key_exists('state_id', $input);
        $stateId = $input['recidency_state_id']
            ?? $input['residency_state_id']
            ?? $input['state_id']
            ?? null;

        $countryChanged = $countryWasProvided && (string) $oldCountryId !== (string) $countryId;
        if ($stateWasProvided) {
            $residency->recidency_state_id = $stateId ?: null;
        } elseif ($countryChanged) {
            // A governorate selected under the old country must never survive a country change.
            $residency->recidency_state_id = null;
        }
        $effectiveStateId = $residency->recidency_state_id;

        $cityWasProvided = array_key_exists('recidency_city_id', $input)
            || array_key_exists('residency_city_id', $input)
            || array_key_exists('city_id', $input);
        $cityId = $input['recidency_city_id']
            ?? $input['residency_city_id']
            ?? $input['city_id']
            ?? null;

        if ($cityWasProvided) {
            $residency->recidency_city_id = $cityId ?: null;
        } elseif ($countryChanged || ($stateWasProvided && (string) $effectiveStateId !== (string) $residency->getOriginal('recidency_state_id'))) {
            $residency->recidency_city_id = null;
        }
        $effectiveCityId = $residency->recidency_city_id;

        if ($effectiveStateId && $countryId &&
            !State::where('id', $effectiveStateId)->where('country_id', $countryId)->exists()) {
            return response()->json([
                'result' => false,
                'message' => translate('The selected governorate does not belong to the selected country.'),
            ], 422);
        }
        if ($effectiveCityId && $effectiveStateId &&
            !City::where('id', $effectiveCityId)->where('state_id', $effectiveStateId)->exists()) {
            return response()->json([
                'result' => false,
                'message' => translate('The selected city does not belong to the selected governorate.'),
            ], 422);
        }

        DB::transaction(function () use ($residency, $countryWasProvided, $countryId, $stateWasProvided, $cityWasProvided, $effectiveStateId, $effectiveCityId) {
            $residency->save();

            // Keep the present address synchronized only with an actual residence country.
            // Birth country is not a substitute for present residence.
            if ($countryWasProvided || $stateWasProvided || $cityWasProvided) {
                $effectiveCountryId = $countryId ?: $residency->recidency_country_id;
                if ($effectiveCountryId) {
                    $address = Address::firstOrNew([
                        'user_id' => auth()->id(),
                        'type' => 'present',
                    ]);
                    $address->country_id = $effectiveCountryId;
                    $address->state_id = $effectiveStateId ?: null;
                    $address->city_id = $effectiveCityId ?: null;
                    // Preserve the user's existing postal code; never invent one.
                    $address->save();
                }
            }
        });

        return $this->success_message('Residency Info has been updated successfully');
    }""",
        "ProfileController residency_info_update",
    )

    source = replace_method(
        source,
        "    public function spiritual_background_update(Request $request)",
        "    public function life_style()",
        r"""    public function spiritual_background_update(Request $request)
    {
        $input = $request->all();
        $this->validate($request, [
            'member_religion_id' => ['sometimes', 'nullable', 'integer', 'exists:religions,id'],
            'religion_id'        => ['sometimes', 'nullable', 'integer', 'exists:religions,id'],
            'member_caste_id'    => ['sometimes', 'nullable', 'integer', 'exists:castes,id'],
            'caste_id'           => ['sometimes', 'nullable', 'integer', 'exists:castes,id'],
            'member_sub_caste_id' => ['sometimes', 'nullable', 'integer', 'exists:sub_castes,id'],
            'sub_caste_id'        => ['sometimes', 'nullable', 'integer', 'exists:sub_castes,id'],
            'family_value_id'    => ['sometimes', 'nullable', 'integer', 'exists:family_values,id'],
            'marital_status'     => ['sometimes', 'nullable', 'integer', 'exists:marital_statuses,id'],
            'marital_status_id'  => ['sometimes', 'nullable', 'integer', 'exists:marital_statuses,id'],
            'member_marital_status_id' => ['sometimes', 'nullable', 'integer', 'exists:marital_statuses,id'],
            'ethnicity'          => ['sometimes', 'nullable', 'string', 'max:255'],
            'personal_value'     => ['sometimes', 'nullable', 'string', 'max:255'],
            'community_value'   => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $spiritualMap = [
            'religion_id' => ['member_religion_id', 'religion_id'],
            'caste_id' => ['member_caste_id', 'caste_id'],
            'sub_caste_id' => ['member_sub_caste_id', 'sub_caste_id'],
            'ethnicity' => ['ethnicity'],
            'personal_value' => ['personal_value'],
            'family_value_id' => ['family_value_id'],
            'community_value' => ['community_value'],
        ];

        $hasSpiritualInput = false;
        foreach ($spiritualMap as $keys) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $input)) {
                    $hasSpiritualInput = true;
                    break 2;
                }
            }
        }

        $maritalKeys = ['marital_status', 'member_marital_status_id', 'marital_status_id'];
        $hasMaritalInput = false;
        foreach ($maritalKeys as $key) {
            if (array_key_exists($key, $input)) {
                $hasMaritalInput = true;
                break;
            }
        }

        if (!$hasSpiritualInput && !$hasMaritalInput) {
            return response()->json([
                'result' => false,
                'message' => translate('No spiritual or marital information was provided.'),
            ], 422);
        }

        DB::transaction(function () use ($input, $spiritualMap, $hasSpiritualInput, $maritalKeys, $hasMaritalInput) {
            if ($hasSpiritualInput) {
                $spiritual = SpiritualBackground::firstOrNew(['user_id' => auth()->id()]);
                foreach ($spiritualMap as $column => $keys) {
                    foreach ($keys as $key) {
                        if (array_key_exists($key, $input)) {
                            $spiritual->{$column} = $input[$key];
                            break;
                        }
                    }
                }
                $spiritual->save();
            }

            if ($hasMaritalInput) {
                $member = Member::where('user_id', auth()->id())->first();
                if ($member) {
                    foreach ($maritalKeys as $key) {
                        if (array_key_exists($key, $input)) {
                            $member->marital_status_id = $input[$key];
                            break;
                        }
                    }
                    $member->save();
                }
            }
        });

        return $this->success_message('Spiritual Background info has been updated successfully');
    }""",
        "ProfileController spiritual_background_update",
    )

    source = replace_method(
        source,
        "    public function partner_expectation_update(PartnerExpectationRequest $request)",
        "    public function password_update(Request $request)",
        r"""    public function partner_expectation_update(PartnerExpectationRequest $request)
    {
        $input = $request->all();
        $this->validate($request, [
            'partner_country_id' => ['sometimes', 'nullable', 'integer', 'exists:countries,id'],
            'partner_state_id' => ['sometimes', 'nullable', 'integer', 'exists:states,id'],
        ]);

        $partner = PartnerExpectation::where('user_id', auth()->id())->first();
        $oldPreferredCountry = $partner ? $partner->preferred_country_id : null;

        $fields = [
            'general' => ['general'],
            'height' => ['partner_height'],
            'weight' => ['partner_weight'],
            'marital_status_id' => ['partner_marital_status'],
            'children_acceptable' => ['partner_children_acceptable'],
            'residence_country_id' => ['residence_country_id'],
            'religion_id' => ['partner_religion_id'],
            'caste_id' => ['partner_caste_id'],
            'sub_caste_id' => ['partner_sub_caste_id'],
            'education' => ['partner_education', 'pertner_education'],
            'profession' => ['partner_profession'],
            'smoking_acceptable' => ['smoking_acceptable'],
            'drinking_acceptable' => ['drinking_acceptable'],
            'diet' => ['partner_diet'],
            'body_type' => ['partner_body_type'],
            'personal_value' => ['partner_personal_value'],
            'manglik' => ['partner_manglik'],
            'language_id' => ['language_id'],
            'family_value_id' => ['family_value_id'],
            'preferred_country_id' => ['partner_country_id'],
            'complexion' => ['partner_complexion', 'pertner_complexion', 'complexion'],
        ];

        $hasUpdates = false;
        foreach ($fields as $keys) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $input)) {
                    $hasUpdates = true;
                    break 2;
                }
            }
        }
        $stateWasProvided = array_key_exists('partner_state_id', $input);

        if (!$hasUpdates && !$stateWasProvided) {
            return response()->json([
                'result' => false,
                'message' => translate('No partner preferences were provided for update.'),
            ], 422);
        }

        $user = User::with('member')->findOrFail(auth()->id());

        DB::transaction(function () use ($input, $fields, $stateWasProvided, $oldPreferredCountry) {
            $partner = PartnerExpectation::firstOrNew(['user_id' => auth()->id()]);

            foreach ($fields as $column => $keys) {
                foreach ($keys as $key) {
                    if (array_key_exists($key, $input)) {
                        $value = $input[$key];

                        if ($column === 'height' && $value !== null && is_numeric($value)) {
                            $value = (float) $value;
                            if ($value > 10) $value = $value / 100;
                        }
                        if ($column === 'children_acceptable' ||
                            $column === 'smoking_acceptable' ||
                            $column === 'drinking_acceptable') {
                            if ($value === 'dose_not_matter') $value = 'does_not_matter';
                        }

                        $partner->{$column} = $value;
                        break;
                    }
                }
            }

            if (array_key_exists('partner_country_id', $input)) {
                $newCountry = $input['partner_country_id'];
                if ((string) $newCountry !== (string) $oldPreferredCountry && !$stateWasProvided) {
                    // A governorate cannot remain linked to a different preferred country.
                    $partner->preferred_state_id = null;
                }
            }
            if ($stateWasProvided) {
                $partner->preferred_state_id = $input['partner_state_id'] ?: null;
            }

            if (!empty($partner->preferred_state_id) && !empty($partner->preferred_country_id) &&
                !State::where('id', $partner->preferred_state_id)
                    ->where('country_id', $partner->preferred_country_id)
                    ->exists()) {
                throw new \InvalidArgumentException('Preferred governorate does not belong to selected country.');
            }

            $partner->save();
        });

        if ($user->member && $user->member->auto_profile_match == 1) {
            try {
                $profileMatchController = new ProfileMatchController();
                $profileMatchController->match_profiles($user->id);
            } catch (\Throwable $e) {
                \Log::warning('Partner preference matching refresh failed: ' . $e->getMessage());
            }
        }

        return $this->success_message('Partner Expectations Info has been updated successfully');
    }""",
        "ProfileController partner_expectation_update",
    )

    return source


def patch_auth_controller(source: str) -> str:
    source = require_replace(
        source,
        "$storedPhotoPath = $photoFile->store('uploads/all', 'public');",
        "$storedPhotoPath = $photoFile->store('uploads/all');",
        "AuthController photo storage disk",
    )
    source = require_replace(
        source,
        "Storage::disk('public')->delete($storedPhotoPath);",
        "Storage::disk(config('filesystems.default'))->delete($storedPhotoPath);",
        "AuthController photo cleanup disk",
    )
    return source


def patch_profile_request(source: str) -> str:
    return replace_method(
        source,
        "    public function rules()",
        "    /**\n     * Get the validation messages",
        r"""    public function rules()
    {
        return [
            'first_name'    => ['sometimes', 'required', 'string', 'max:255'],
            'last_name'     => ['sometimes', 'required', 'string', 'max:255'],
            'gender'        => ['sometimes', 'nullable', 'in:1,2'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before:today'],
            'on_behalf'     => ['sometimes', 'nullable', 'integer', 'exists:on_behalves,id'],
            'phone'         => ['sometimes', 'nullable', 'string', 'max:32'],
            'children'      => ['sometimes', 'nullable', 'integer', 'min:0'],
            'marital_status' => ['sometimes', 'nullable', 'integer', 'exists:marital_statuses,id'],
            'introduction'  => ['sometimes', 'nullable', 'string', 'max:5000'],
            'photo'         => ['sometimes', 'nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:10240'],
        ];
    }

    """,
        "ProfileRequest partial profile validation",
    )


def patch_career_request(source: str) -> str:
    return replace_method(
        source,
        "    public function rules()",
        "    /**\n     * Get the validation messages",
        r"""    public function rules()
    {
        $isUpdate = $this->route('career') !== null;
        $designationRule = $isUpdate ? 'sometimes|required' : 'required';
        $startRule = $isUpdate ? 'sometimes|required' : 'required';

        return [
            'designation' => [$designationRule, 'string', 'max:255'],
            'company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'start' => [$startRule, 'numeric', 'between:1900,' . date('Y')],
            'end' => ['sometimes', 'nullable', 'numeric', 'between:1900,' . date('Y')],
        ];
    }

    """,
        "CareerRequest create/update validation",
    )


def patch_partner_request(source: str) -> str:
    return replace_method(
        source,
        "    public function rules()",
        "}",
        r"""    public function rules()
    {
        return [
            'general' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'partner_height' => ['sometimes', 'nullable', 'numeric', 'between:0.4,2.5'],
            'partner_weight' => ['sometimes', 'nullable', 'numeric', 'between:1,999.99'],
            'partner_marital_status' => ['sometimes', 'nullable', 'integer', 'exists:marital_statuses,id'],
            'partner_children_acceptable' => ['sometimes', 'nullable', 'in:yes,no,dose_not_matter,does_not_matter'],
            'residence_country_id' => ['sometimes', 'nullable', 'integer', 'exists:countries,id'],
            'partner_religion_id' => ['sometimes', 'nullable', 'integer', 'exists:religions,id'],
            'partner_caste_id' => ['sometimes', 'nullable', 'integer', 'exists:castes,id'],
            'partner_sub_caste_id' => ['sometimes', 'nullable', 'integer', 'exists:sub_castes,id'],
            'smoking_acceptable' => ['sometimes', 'nullable', 'in:yes,no,dose_not_matter,does_not_matter'],
            'drinking_acceptable' => ['sometimes', 'nullable', 'in:yes,no,dose_not_matter,does_not_matter'],
            'partner_diet' => ['sometimes', 'nullable', 'string', 'max:50'],
            'partner_manglik' => ['sometimes', 'nullable', 'string', 'max:50'],
            'language_id' => ['sometimes', 'nullable', 'integer', 'exists:member_languages,id'],
            'family_value_id' => ['sometimes', 'nullable', 'integer', 'exists:family_values,id'],
            'partner_country_id' => ['sometimes', 'nullable', 'integer', 'exists:countries,id'],
            'partner_state_id' => ['sometimes', 'nullable', 'integer', 'exists:states,id'],
            'partner_complexion' => ['sometimes', 'nullable', 'string', 'max:50'],
            'pertner_complexion' => ['sometimes', 'nullable', 'string', 'max:50'],
            'complexion' => ['sometimes', 'nullable', 'string', 'max:50'],
            'partner_education' => ['sometimes', 'nullable', 'string', 'max:255'],
            'pertner_education' => ['sometimes', 'nullable', 'string', 'max:255'],
            'partner_profession' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
""",
        "PartnerExpectationRequest partial validation",
    )


def patch_education_controller(source: str) -> str:
    return replace_method(
        source,
        "    public function update(Request $request, $id)",
        "    /**\n     * Remove the specified resource",
        r"""    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'degree' => ['sometimes', 'required', 'string', 'max:255'],
            'institution' => ['sometimes', 'required', 'string', 'max:255'],
            'education_start' => ['sometimes', 'required', 'numeric', 'between:1900,' . date('Y')],
            'education_end' => ['sometimes', 'nullable', 'numeric', 'between:1900,' . date('Y')],
        ]);

        $education = Education::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$education) {
            return $this->failure_message('You are not authorized or data not found');
        }

        $input = $request->all();
        if (array_key_exists('degree', $input)) $education->degree = $input['degree'];
        if (array_key_exists('institution', $input)) $education->institution = $input['institution'];
        if (array_key_exists('education_start', $input)) $education->start = $input['education_start'];
        if (array_key_exists('education_end', $input)) $education->end = $input['education_end'];
        if (array_key_exists('end', $input)) $education->end = $input['end'];

        if ($education->isDirty()) {
            $education->save();
        }

        return $this->success_message('Education Info has been updated successfully');
    }

    """,
        "EducationController partial update",
    )


def patch_career_controller(source: str) -> str:
    source = require_replace(
        source,
        """        Career::create($request->validated() + [
            'user_id' => auth()->id()
        ]);""",
        """        $careerData = $request->validated();
        // Company is optional; store an empty value rather than inventing a company name.
        if (!array_key_exists('company', $careerData)) {
            $careerData['company'] = '';
        }
        Career::create($careerData + [
            'user_id' => auth()->id()
        ]);""",
        "CareerController store optional company",
    )
    return source


def patch_basic_resource(source: str) -> str:
    source = require_replace(
        source,
        "            'gender' => $genderText,\n",
        "            'gender' => $genderText,\n            'gender_id' => $memberModel->gender ?? null,\n",
        "BasicInformation raw gender id",
    )
    source = require_replace(
        source,
        "            'onbehalf' => new OnBehalfResource(OnBehalf::find($memberModel->on_behalves_id ?? 0)),\n",
        "            'onbehalf' => new OnBehalfResource(OnBehalf::find($memberModel->on_behalves_id ?? 0)),\n            'on_behalf_id' => $memberModel->on_behalves_id ?? null,\n",
        "BasicInformation raw behalf id",
    )
    source = require_replace(
        source,
        "            'maritial_status' => $maritalStatusName,\n            'marital_status' => $maritalStatusName,\n",
        "            'maritial_status' => $maritalStatusName,\n            'marital_status' => $maritalStatusName,\n            'marital_status_id' => $memberModel->marital_status_id ?? null,\n",
        "BasicInformation raw marital id",
    )
    source = require_replace(
        source,
        "            'photo' => show_profile_picture($this) ? uploaded_asset($this->photo) : static_asset('assets/img/avatar-place.png'),\n",
        "            'photo' => show_profile_picture($this) ? uploaded_asset($this->photo) : static_asset('assets/img/avatar-place.png'),\n            'photo_id' => $this->photo,\n",
        "BasicInformation raw photo id",
    )
    return source


def patch_spiritual_resource(source: str) -> str:
    source = require_replace(source, "'religion_id'    => $religionName,", "'religion_id'    => $this->religion_id,\n            'raw_religion_id' => $this->religion_id,", "Spiritual resource numeric religion")
    source = require_replace(source, "'caste_id'       => $casteName,", "'caste_id'       => $this->caste_id,", "Spiritual resource numeric caste")
    source = require_replace(source, "'sub_caste_id'   => $subCasteName,", "'sub_caste_id'   => $this->sub_caste_id,", "Spiritual resource numeric subcaste")
    source = require_replace(source, "'family_value_id'=> $familyValueName,", "'family_value_id'=> $this->family_value_id,", "Spiritual resource numeric family value")
    return source


def patch_partner_resource(source: str) -> str:
    source = require_replace(
        source,
        "            'raw_marital_status_id' => $this->marital_status_id,\n",
        "            'raw_marital_status_id' => $this->marital_status_id,\n            'marital_status_id' => $this->marital_status_id,\n",
        "Partner resource raw marital id",
    )
    source = require_replace(
        source,
        "            'language'              => $language ? $language->name : '',\n",
        "            'language'              => $language ? $language->name : '',\n            'language_id'           => $this->language_id,\n",
        "Partner resource raw language id",
    )
    return source


PATCHERS = {
    "app/Http/Controllers/Api/AuthController.php": patch_auth_controller,
    "app/Http/Controllers/Api/ProfileController.php": patch_profile_controller,
    "app/Http/Requests/ProfileRequest.php": patch_profile_request,
    "app/Http/Requests/CareerRequest.php": patch_career_request,
    "app/Http/Requests/PartnerExpectationRequest.php": patch_partner_request,
    "app/Http/Controllers/Api/EducationController.php": patch_education_controller,
    "app/Http/Controllers/Api/CareerController.php": patch_career_controller,
    "app/Http/Resources/PublicProfile/BasicInformation.php": patch_basic_resource,
    "app/Http/Resources/PublicProfile/SpiritualSocialBackground.php": patch_spiritual_resource,
    "app/Http/Resources/PublicProfile/PartnerExpectationResource.php": patch_partner_resource,
}


def main() -> None:
    if not ARCHIVE.is_file():
        raise SystemExit(f"Hosting archive not found: {ARCHIVE}")

    with zipfile.ZipFile(ARCHIVE, "r") as source_zip:
        names = source_zip.namelist()
        patched_entries: dict[str, bytes] = {}
        for suffix, patcher in PATCHERS.items():
            matches = [name for name in names if name.endswith(suffix)]
            if len(matches) != 1:
                raise RuntimeError(f"{suffix}: expected exactly one archive entry, found {len(matches)}")
            entry = matches[0]
            original = source_zip.read(entry).decode("utf-8")
            updated = patcher(original)
            if updated == original:
                raise RuntimeError(f"{suffix}: patch made no changes")
            patched_entries[entry] = updated.encode("utf-8")
            PATCHED[suffix] = updated

        temp_fd, temp_name = tempfile.mkstemp(prefix="hosting-patched-", suffix=".zip", dir=str(ARCHIVE.parent))
        os.close(temp_fd)
        try:
            with zipfile.ZipFile(temp_name, "w", compression=zipfile.ZIP_DEFLATED) as target_zip:
                for info in source_zip.infolist():
                    data = patched_entries.get(info.filename)
                    if data is None:
                        data = source_zip.read(info.filename)
                    target_zip.writestr(info, data)
            os.replace(temp_name, ARCHIVE)
        finally:
            if os.path.exists(temp_name):
                os.unlink(temp_name)

    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    for suffix, text in PATCHED.items():
        destination = OUTPUT_DIR / suffix
        destination.parent.mkdir(parents=True, exist_ok=True)
        destination.write_text(text, encoding="utf-8")

    manifest = {
        "archive": ARCHIVE.name,
        "patched_files": sorted(PATCHERS.keys()),
    }
    Path("اصلاحات 2/hosting-patch-manifest.json").write_text(
        json.dumps(manifest, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )

    with zipfile.ZipFile(ARCHIVE, "r") as check:
        bad_entry = check.testzip()
        if bad_entry:
            raise RuntimeError(f"Archive verification failed at: {bad_entry}")

    print("Patched and verified Laravel source:")
    for suffix in sorted(PATCHERS):
        print(f" - {suffix}")


if __name__ == "__main__":
    main()
