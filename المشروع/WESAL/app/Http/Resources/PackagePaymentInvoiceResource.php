<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PackagePaymentInvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $user = $this->user;
        $package = $this->package;

        return [
            'payment_id'      => (string) $this->id,
            'purchase_code'   => $this->payment_code ?: (string) $this->id,
            'purchase_date'   => $this->created_at ? $this->created_at->format('d-m-Y H:i:s') : '',
            'name'            => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
            'email'           => $user->email ?? '',
            'phone'           => $user->phone ?? '',
            'payment_method'  => $this->payment_method === 'manual_payment'
                ? ($this->custom_payment_name ?: 'manual_payment')
                : ucwords((string) $this->payment_method),
            // Preserve Pending/Rejected/Unpaid rather than reducing every non-paid state to Unpaid.
            'payment_status'  => (string) ($this->payment_status ?: 'Unpaid'),
            'package_id'      => (string) $this->package_id,
            'package_name'    => $package->name ?? '',
            'amount'          => single_price($this->amount),
            'total'           => single_price($this->amount),
        ];
    }
}
