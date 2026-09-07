<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Photo extends Model
{
    use HasFactory;

    protected $table = 'images';

    protected $fillable = [
        'jock_id',
        'article_id',
        'show_id',
        'batch_id',
        'student_jock_id',
        'file',
        'name',
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

    public function Batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function StudentJock(): BelongsTo
    {
        return $this->belongsTo(StudentJock::class);
    }
}
