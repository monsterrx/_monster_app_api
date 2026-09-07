<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'semester',
        'number',
        'start_year',
        'end_year',
        'description',
        'image',
        'location',
    ];

    public function Student(): BelongsToMany
    {
        return $this->belongsToMany(Student::class);
    }

    public function Image(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    public function Sponsor(): BelongsToMany
    {
        return $this->belongsToMany(Sponsor::class);
    }
}
