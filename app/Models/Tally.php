<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tally extends Model
{
    use HasFactory;

    protected $table = 'tallies';

    protected $fillable = [
        'chart_id',
        'result',
        'last_result',
        'dated',
    ];

    public function Chart(): BelongsTo
    {
        return $this->belongsTo(Chart::class);
    }
}
