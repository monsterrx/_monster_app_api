<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feature extends Model
{
    protected $table = 'featured_indiegrounds';

    protected $fillable = [
        'independent_id',
        'header',
        'content',
        'month',
        'year',
        'location',
    ];

    public function Indie(): BelongsTo
    {
        return $this->belongsTo(Indie::class, 'indieground_id');
    }
}
