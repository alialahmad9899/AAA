<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualPaymentMethod extends Model
{
    use HasFactory;

    public const SHAM_CASH_CODE = '0f50e77304209ae23d85b7971f1089b7';

    /** Allow only the configured Sham Cash offline method. */
    public function isShamCash(): bool
    {
        $code = strtolower(trim((string) $this->getAttribute('code')));
        if ($code === self::SHAM_CASH_CODE || $code === 'sham_cash' || $code === 'shamcash') {
            return true;
        }

        foreach ([$this->heading, $this->heading_en] as $label) {
            $normalized = preg_replace('/[\s_\-]+/u', '', mb_strtolower(trim((string) $label)));
            if ($normalized !== ''
                && (str_contains($normalized, 'shamcash')
                    || str_contains($normalized, 'شامكاش')
                    || str_contains($normalized, 'الشامكاش'))) {
                return true;
            }
        }

        return false;
    }
}
