<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Timeslot extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'show_id',
        'day',
        'start',
        'end',
        'location',
    ];

    public function Show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    public function Jock(): BelongsToMany
    {
        return $this->belongsToMany(Jock::class);
    }
}
