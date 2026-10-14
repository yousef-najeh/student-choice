<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * One physical branch of an institution, located in one city. An institution can
 * have several campuses (different cities); a major can be offered at more than
 * one campus (see the major_campus pivot via Major::campuses()).
 */
class Campus extends Model
{
    use HasFactory;

    protected $fillable = ['institution_id', 'city_id', 'name'];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function majors(): BelongsToMany
    {
        return $this->belongsToMany(Major::class, 'major_campus');
    }
}
