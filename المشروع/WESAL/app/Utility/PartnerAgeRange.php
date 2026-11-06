<?php

namespace App\Utility;

use Carbon\Carbon;

class PartnerAgeRange
{
    /**
     * Return inclusive date-of-birth boundaries for a current-age interval.
     */
    public static function birthdayBounds($minimumAge, $maximumAge, ?Carbon $today = null): array
    {
        $referenceDate = ($today ?: Carbon::today())->copy()->startOfDay();

        $minimum = is_numeric($minimumAge) && (int) $minimumAge >= 1
            ? (int) $minimumAge
            : null;
        $maximum = is_numeric($maximumAge) && (int) $maximumAge >= 1
            ? (int) $maximumAge
            : null;

        return [
            'oldest_birth_date' => $maximum !== null
                ? $referenceDate->copy()->subYears($maximum + 1)->addDay()->toDateString()
                : null,
            'youngest_birth_date' => $minimum !== null
                ? $referenceDate->copy()->subYears($minimum)->toDateString()
                : null,
        ];
    }
}
