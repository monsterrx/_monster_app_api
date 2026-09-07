<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Song extends Model
{
    use HasFactory;

    protected $fillable = [
        'album_id',
        'name',
        'track_link',
        'type',
        'is_charted',
    ];

    public function Chart(): HasMany
    {
        return $this->hasMany(Chart::class);
    }

    public function Album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    public function User(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
