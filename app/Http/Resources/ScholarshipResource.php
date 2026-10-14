<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Scholarship */
class ScholarshipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'discount_percentage' => $this->discount_percentage !== null ? (float) $this->discount_percentage : null,
            'eligibility_notes' => $this->eligibility_notes,
            'students' => $this->whenLoaded('studentProfiles', fn () => $this->studentProfiles->map(fn ($profile) => [
                'id' => $profile->user_id,
                'name' => $profile->user?->name,
            ])),
        ];
    }
}
