<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Indie extends Model
{
    use HasFactory;

    protected $table = 'indiegrounds';

    protected $fillable = [
        'artist_id',
        'introduction',
        'image',
        'location',
    ];

    public function Artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function Feature(): HasMany
    {
        return $this->hasMany(Feature::class, 'independent_id');
    }
}
