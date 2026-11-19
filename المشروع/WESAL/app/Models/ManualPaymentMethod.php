<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualPaymentMethod extends Model
{
    use HasFactory;

    /**
     * The admin manual-payment form persists the provider name in "heading".
     * Do not depend on heading_en: it is not part of the admin form contract.
     */
    public function isShamCash(): bool
    {
        $heading = mb_strtolower(trim((string) $this->heading), 'UTF-8');
        $compactHeading = preg_replace('/[\s\-_]+/u', '', $heading) ?: $heading;

        return str_contains($heading, 'شام كاش')
            || str_contains($compactHeading, 'شامكاش')
            || str_contains($compactHeading, 'shamcash');
    }
}
