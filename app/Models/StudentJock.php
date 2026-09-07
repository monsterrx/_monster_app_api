<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentJock extends Model
{
    use HasFactory;

    protected $table = 'student_jocks';

    protected $fillable = [
        'school_id',
        'first_name',
        'last_name',
        'nickname',
        'image',
        'description',
        'position',
    ];

    public function Batch(): BelongsToMany
    {
        return $this->belongsToMany(StudentJockBatch::class);
    }

    public function School(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function Link(): HasMany
    {
        return $this->hasMany(Social::class);
    }

    public function Image(): HasMany
    {
        return $this->hasMany(Photo::class);
    }
}
