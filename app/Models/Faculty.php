<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Faculty extends Model
{
    use HasFactory;

    protected $fillable = ['institution_id', 'name'];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function majors(): HasMany
    {
        return $this->hasMany(Major::class);
    }
}
