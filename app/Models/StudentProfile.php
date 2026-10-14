<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class StudentProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'gpa',
        'tawjihi_branch_id',
        'preferred_region_id',
        'preferred_city_id',
        'budget_max',
        'photo_path',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'gpa' => 'decimal:2',
            'budget_max' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tawjihiBranch(): BelongsTo
    {
        return $this->belongsTo(TawjihiBranch::class);
    }

    public function preferredRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'preferred_region_id');
    }

    public function preferredCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'preferred_city_id');
    }

    public function preferredFields(): BelongsToMany
    {
        return $this->belongsToMany(FieldOfStudy::class, 'student_preferred_fields', 'student_profile_id', 'field_of_study_id');
    }

    public function scholarships(): BelongsToMany
    {
        return $this->belongsToMany(Scholarship::class, 'student_scholarships');
    }
}
