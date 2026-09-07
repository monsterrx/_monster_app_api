<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Social extends Model
{
    use HasFactory;

    protected $table = 'links';

    protected $fillable = [
        'jock_id',
        'show_id',
        'article_id',
        'student_jock_id',
        'website',
        'url',
    ];

    public function Show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    public function Jock(): BelongsTo
    {
        return $this->belongsTo(Jock::class);
    }

    public function Article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function StudentJock(): BelongsTo
    {
        return $this->belongsTo(StudentJock::class);
    }
}
