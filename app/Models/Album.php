<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Album extends Model
{
    use HasFactory;

    protected $fillable = [
        'artist_id',
        'genre_id',
        'name',
        'type',
        'year',
        'image',
    ];

    public function Artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function Genre(): BelongsTo
    {
        return $this->belongsTo(Genre::class);
    }

    public function Song(): HasMany
    {
        return $this->hasMany(Song::class);
    }
}
