<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Podcast extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'show_id',
        'episode',
        'date',
        'link',
        'image',
        'location',
    ];

    public function Show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }
}
