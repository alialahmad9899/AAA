<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AppAnnouncement extends Model
{
    protected $fillable = [
        'announcement_type',
        'title_ar',
        'message_ar',
        'title_en',
        'message_en',
        'target_type',
        'age_min',
        'age_max',
        'button_enabled',
        'button_text_ar',
        'button_text_en',
        'button_url',
        'is_active',
    ];

    protected $casts = [
        'age_min' => 'integer',
        'age_max' => 'integer',
        'button_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Resolve audience targeting against the member record stored on the server.
     * Wesal uses gender ID 1 for male and 2 for female.
     */
    public function matchesMember(?Member $member): bool
    {
        $target = (string) ($this->target_type ?? 'all');

        if ($target === 'all') {
            return true;
        }

        if (!$member) {
            return false;
        }

        if ($target === 'male') {
            return (int) $member->gender === 1;
        }

        if ($target === 'female') {
            return (int) $member->gender === 2;
        }

        if ($target !== 'age' || empty($member->birthday)
            || $this->age_min === null || $this->age_max === null) {
            return false;
        }

        try {
            $age = Carbon::parse($member->birthday)->age;
        } catch (\Throwable $exception) {
            return false;
        }

        return $age >= (int) $this->age_min && $age <= (int) $this->age_max;
    }
}
