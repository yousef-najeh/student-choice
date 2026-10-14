<?php

namespace App\Services;

use App\Models\Major;
use App\Models\StudentProfile;
use Illuminate\Support\Collection;

/**
 * Scores every major 0-100 for a given student, using a weighted blend of
 * preferences / GPA / tawjihi branch / budget / location instead of a hard
 * filter, so a student always gets a ranked list with a visible breakdown
 * of why each major scored the way it did. Weights live in config/matching.php.
 */
class MajorMatchingService
{
    public function scoredMajorsFor(StudentProfile $profile): Collection
    {
        $preferredFieldIds = $profile->relationLoaded('preferredFields')
            ? $profile->preferredFields->pluck('id')->all()
            : $profile->preferredFields()->pluck('field_of_study_id')->all();

        $majors = Major::with([
            'institution', 'faculty', 'degreeLevel', 'fieldOfStudy',
            'campuses.city.region', 'tawjihiBranches',
        ])->get();

        $weights = config('matching.weights');

        // The student's own scholarship(s) — not tied to any major — discount
        // whichever major they end up studying, so this is computed once here
        // rather than per-major.
        $studentDiscount = (float) (
            ($profile->relationLoaded('scholarships') ? $profile->scholarships : $profile->scholarships())
                ->max('discount_percentage') ?? 0
        );

        return $majors->map(function (Major $major) use ($profile, $preferredFieldIds, $weights, $studentDiscount) {
            $breakdown = [
                'preferences' => $this->preferenceScore($major, $preferredFieldIds),
                'gpa' => $this->gpaScore($major, $profile->gpa),
                'tawjihi' => $this->tawjihiScore($major, $profile->tawjihi_branch_id),
                'budget' => $this->budgetScore($major, $profile->budget_max, $studentDiscount),
                'location' => $this->locationScore($major, $profile->preferred_city_id, $profile->preferred_region_id),
            ];

            $total = 0.0;
            foreach ($breakdown as $key => $score) {
                $total += $score * ($weights[$key] ?? 0);
            }

            $major->match_score = round($total, 1);
            $major->match_breakdown = array_map(fn ($v) => round($v, 1), $breakdown);
            $major->net_hour_price = ($studentDiscount > 0 && $major->credit_hour_price !== null)
                ? round((float) $major->credit_hour_price * (1 - $studentDiscount / 100), 2)
                : null;

            return $major;
        })->sortByDesc('match_score')->values();
    }

    private function preferenceScore(Major $major, array $preferredFieldIds): float
    {
        if (empty($preferredFieldIds)) {
            return config('matching.neutral_score');
        }

        return in_array($major->field_of_study_id, $preferredFieldIds, true) ? 100.0 : 0.0;
    }

    private function gpaScore(Major $major, $gpa): float
    {
        if ($gpa === null || $major->admission_average === null) {
            return config('matching.neutral_score');
        }

        $gpa = (float) $gpa;
        $admissionAverage = (float) $major->admission_average;
        $minGpa = $major->min_gpa_to_apply !== null ? (float) $major->min_gpa_to_apply : $admissionAverage;

        if ($gpa >= $admissionAverage) {
            return 100.0;
        }

        if ($gpa >= $minGpa) {
            if ($admissionAverage <= $minGpa) {
                return 100.0;
            }

            $floor = config('matching.gpa_between_min_and_average_floor');

            return $floor + (100.0 - $floor) * (($gpa - $minGpa) / ($admissionAverage - $minGpa));
        }

        return 0.0;
    }

    private function tawjihiScore(Major $major, ?int $branchId): float
    {
        if (! $branchId) {
            return config('matching.neutral_score');
        }

        if ($major->tawjihiBranches->isEmpty()) {
            // No branch restriction recorded for this major = open to all branches.
            return 100.0;
        }

        return $major->tawjihiBranches->contains('id', $branchId) ? 100.0 : 0.0;
    }

    /**
     * Compared per credit hour, not the total program cost — majors vary wildly
     * in total hours (68 to 135+ in the real catalog), so a per-hour price is
     * the number that's actually comparable across them, and it's what
     * student_profiles.budget_max represents (see UpdateProfileRequest).
     */
    private function budgetScore(Major $major, $budgetMax, float $studentDiscount): float
    {
        if ($budgetMax === null) {
            return config('matching.neutral_score');
        }

        $hourPrice = $major->credit_hour_price;
        if ($hourPrice === null) {
            return config('matching.neutral_score');
        }

        $netHourPrice = (float) $hourPrice * (1 - $studentDiscount / 100);
        $budgetMax = (float) $budgetMax;

        if ($netHourPrice <= $budgetMax) {
            return 100.0;
        }

        $overRatio = ($netHourPrice - $budgetMax) / $budgetMax;

        return max(0.0, 100.0 - $overRatio * 100.0);
    }

    private function locationScore(Major $major, ?int $cityId, ?int $regionId): float
    {
        if (! $cityId && ! $regionId) {
            return config('matching.neutral_score');
        }

        $campusCityIds = $major->campuses->pluck('city_id');
        $campusRegionIds = $major->campuses->pluck('city.region_id');

        if ($cityId && $campusCityIds->contains($cityId)) {
            return 100.0;
        }

        if ($regionId && $campusRegionIds->contains($regionId)) {
            return config('matching.same_region_score');
        }

        return 0.0;
    }
}
