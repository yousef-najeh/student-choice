<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->studentProfile;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'is_banned' => $this->is_banned,
            'banned_reason' => $this->banned_reason,
            'created_at' => $this->created_at,
            'profile' => $this->when($profile !== null, fn () => [
                'gpa' => $profile->gpa !== null ? (float) $profile->gpa : null,
                'tawjihi_branch' => $profile->relationLoaded('tawjihiBranch') ? $profile->tawjihiBranch?->name : null,
                'preferred_region' => $profile->relationLoaded('preferredRegion') ? $profile->preferredRegion?->name : null,
                'preferred_city' => $profile->relationLoaded('preferredCity') ? $profile->preferredCity?->name : null,
                'budget_max' => $profile->budget_max !== null ? (float) $profile->budget_max : null,
                'photo_url' => $profile->photo_path ? asset('storage/'.$profile->photo_path) : null,
                'description' => $profile->description,
                'preferred_fields' => $profile->relationLoaded('preferredFields')
                    ? $profile->preferredFields->pluck('name')
                    : null,
                'scholarships' => $profile->relationLoaded('scholarships')
                    ? $profile->scholarships->map(fn ($s) => [
                        'id' => $s->id,
                        'name' => $s->name,
                        'discount_percentage' => $s->discount_percentage !== null ? (float) $s->discount_percentage : null,
                    ])
                    : null,
            ]),
        ];
    }
}
