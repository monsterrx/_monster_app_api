<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Relevant extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'relateds';

    protected $fillable = [
        'article_id',
        'related_article_id',
    ];

    public function Article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function RelatedArticle(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'related_article_id');
    }
}
