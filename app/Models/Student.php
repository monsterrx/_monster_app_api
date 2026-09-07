<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'first_name',
        'last_name',
        'course',
        'year_level',
        'data',
        'scholar_type',
        'image',
    ];

    public function School(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function Batch(): BelongsToMany
    {
        return $this->belongsToMany(Batch::class);
    }

    public function Scholar(): HasMany
    {
        return $this->hasMany(Scholar::class);
    }
}
