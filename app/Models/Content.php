<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Content extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'sub_contents';

    protected $fillable = [
        'article_id',
        'content',
        'image',
    ];

    public function Article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }
}
