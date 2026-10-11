<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;

class IdentityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                             => $this->whenHas('id'),
            'user_id'                        => $this->whenHas('user_id'),
            'name'                           => $this->whenHas('name'),
            'dob'                            => $this->whenHas('dob'),
            'school_id'                      => $this->whenHas('school_id'),
            'school_name'                    => $this->whenHas('school_name'),
            'transcript'                     => identityFileUrl($this->resource, 'transcript') ?? url(mediaUrl('placeholder.png')),
            'parent_name'                    => $this->whenHas('parent_name'),
            'parent_phone'                   => $this->whenHas('parent_phone'),
            'parent_email'                   => $this->whenHas('parent_email'),
            'personal_photo'                 => $this->profile_image,
            'status'                         => $this->whenHas('status'),
            'attachments'                    => identityFileUrl($this->resource, 'attachments') ?? url(mediaUrl('placeholder.png')),
            'parent_verified_at'             => $this->whenHas('parent_verified_at'),   
            'address'                        => new AddressResource($this->whenLoaded('address')),

        ];
    }
}
