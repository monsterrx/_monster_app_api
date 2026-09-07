<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Show extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'title',
        'front_description',
        'description',
        'slug_string',
        'icon',
        'header_image',
        'background_image',
        'is_special',
        'is_active',
        'location',
    ];

    public function Jock(): BelongsToMany
    {
        return $this->belongsToMany(Jock::class);
    }

    public function Timeslot(): HasMany
    {
        return $this->hasMany(Timeslot::class);
    }

    public function Image(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    public function Link(): HasMany
    {
        return $this->hasMany(Social::class);
    }

    public function Podcast(): HasMany
    {
        return $this->hasMany(Podcast::class);
    }

    public function podcastCount(): int
    {
        return $this->hasMany(Podcast::class)->count();
    }
}
