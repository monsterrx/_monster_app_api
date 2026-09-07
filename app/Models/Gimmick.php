<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gimmick extends Model
{
    use HasFactory;

    protected $table = 'gimikboards';

    protected $fillable = [
        'title',
        'start_date',
        'end_date',
        'description',
        'sub_description',
        'school_id',
        'image',
        'location',
        'is_published',
    ];

    public function School(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
