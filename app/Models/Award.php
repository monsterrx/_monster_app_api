<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Award extends Model
{
    use HasFactory;

    protected $fillable = [
        'jock_id',
        'show_id',
        'name',
        'title',
        'description',
        'year',
        'special',
        'location',
    ];

    public function Jock(): BelongsTo
    {
        return $this->belongsTo(Jock::class);
    }

    public function Show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }
}
