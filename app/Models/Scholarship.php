<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Scholarship extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'discount_percentage',
        'eligibility_notes',
    ];

    protected function casts(): array
    {
        return [
            'discount_percentage' => 'decimal:2',
        ];
    }

    /**
     * A scholarship is held by students, not tied to any major — see
     * student_scholarships migration and docs/technical-flows.md.
     */
    public function studentProfiles(): BelongsToMany
    {
        return $this->belongsToMany(StudentProfile::class, 'student_scholarships');
    }
}
