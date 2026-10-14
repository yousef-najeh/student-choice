<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Major extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'faculty_id',
        'degree_level_id',
        'field_of_study_id',
        'name',
        'credit_hour_price',
        'credit_hours',
        'study_duration_text',
        'admission_average',
        'min_gpa_to_apply',
        'admission_link',
    ];

    protected function casts(): array
    {
        return [
            'credit_hour_price' => 'decimal:2',
            'admission_average' => 'decimal:2',
            'min_gpa_to_apply' => 'decimal:2',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function degreeLevel(): BelongsTo
    {
        return $this->belongsTo(DegreeLevel::class);
    }

    public function fieldOfStudy(): BelongsTo
    {
        return $this->belongsTo(FieldOfStudy::class);
    }

    public function campuses(): BelongsToMany
    {
        return $this->belongsToMany(Campus::class, 'major_campus');
    }

    public function tawjihiBranches(): BelongsToMany
    {
        return $this->belongsToMany(TawjihiBranch::class, 'major_tawjihi_branch');
    }

    /**
     * Total tuition cost for the whole major (credit hour price x credit hours).
     */
    public function totalCost(): ?float
    {
        if ($this->credit_hour_price === null || $this->credit_hours === null) {
            return null;
        }

        return (float) $this->credit_hour_price * $this->credit_hours;
    }
}
