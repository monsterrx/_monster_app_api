<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Artist extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'country',
        'type',
        'image',
    ];

    public function Album(): HasMany
    {
        return $this->hasMany(Album::class);
    }

    public function Indie(): HasMany
    {
        return $this->hasMany(Indie::class);
    }
}
