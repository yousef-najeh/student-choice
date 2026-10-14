<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Major */
class MajorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'institution' => $this->whenLoaded('institution', fn () => [
                'id' => $this->institution->id,
                'name' => $this->institution->name,
                'type' => $this->institution->institution_type,
            ]),
            'faculty' => $this->whenLoaded('faculty', fn () => $this->faculty?->name),
            'degree_level' => $this->whenLoaded('degreeLevel', fn () => $this->degreeLevel?->name),
            'field_of_study' => $this->whenLoaded('fieldOfStudy', fn () => $this->fieldOfStudy?->name),
            'campuses' => $this->whenLoaded('campuses', fn () => $this->campuses->map(fn ($campus) => [
                'id' => $campus->id,
                'name' => $campus->name,
                'city' => $campus->city?->name,
                'region' => $campus->city?->region?->name,
            ])),
            'tawjihi_branches' => $this->whenLoaded('tawjihiBranches', fn () => $this->tawjihiBranches->pluck('name')),
            'credit_hour_price' => $this->credit_hour_price !== null ? (float) $this->credit_hour_price : null,
            // Net of the logged-in student's own scholarship(s), per credit hour —
            // only present on /majors/match results, and only when a discount
            // actually applies. This (not total_cost) is what budget matching uses.
            'net_hour_price' => $this->when(isset($this->net_hour_price) && $this->net_hour_price !== null, fn () => (float) $this->net_hour_price),
            'credit_hours' => $this->credit_hours,
            'total_cost' => $this->totalCost(),
            'study_duration_text' => $this->study_duration_text,
            'admission_average' => $this->admission_average !== null ? (float) $this->admission_average : null,
            'min_gpa_to_apply' => $this->min_gpa_to_apply !== null ? (float) $this->min_gpa_to_apply : null,
            'admission_link' => $this->admission_link,
            'match' => $this->when(isset($this->match_score), fn () => [
                'score' => $this->match_score,
                'breakdown' => $this->match_breakdown,
            ]),
        ];
    }
}
