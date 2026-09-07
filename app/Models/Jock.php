<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jock extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'employee_id',
        'slug_string',
        'name',
        'moniker',
        'description',
        'profile_image',
        'background_image',
        'main_image',
        'is_active',
    ];

    public function Employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function Fact(): HasMany
    {
        return $this->hasMany(Fact::class, 'jock_id');
    }

    public function Show(): BelongsToMany
    {
        return $this->belongsToMany(Show::class);
    }

    public function Image(): HasMany
    {
        return $this->hasMany(Photo::class, 'jock_id');
    }

    public function Link(): HasMany
    {
        return $this->hasMany(Social::class, 'jock_id');
    }

    public function Award(): HasMany
    {
        return $this->hasMany(Award::class, 'jock_id');
    }

    public function Timeslot(): BelongsToMany
    {
        return $this->belongsToMany(Timeslot::class);
    }
}
