<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'seal',
        'location',
    ];

    public function Gimikboard(): HasMany
    {
        return $this->hasMany(Gimmick::class);
    }

    public function Student(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
