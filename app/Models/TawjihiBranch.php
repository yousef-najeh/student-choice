<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TawjihiBranch extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function majors(): BelongsToMany
    {
        return $this->belongsToMany(Major::class, 'major_tawjihi_branch');
    }

    public function studentProfiles(): HasMany
    {
        return $this->hasMany(StudentProfile::class);
    }
}
