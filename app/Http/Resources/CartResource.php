<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [    
            "id"                => $this['id'] ?? null,
            "user_id"           => $this['user_id'] ?? null,
            'booking_id'        => $this['cartable_id'] ?? null,
            "subject_name"      => $this['name'] ?? null,
            "price"             => !empty($this['price']) ? formatAmount($this['price']) : null,
            "session_time"      => $this['options']['session_time'] ?? null,
            "subject_group"     => $this['options']['subject_group'] ?? null,
            "currency_symbol"   => $this['options']['currency_symbol'] ?? null,
            "image"             => $this['options']['image'] ? url(Storage::url($this['options']['image'])) : url(Storage::url('placeholder.png')),
        ];
    }
}
