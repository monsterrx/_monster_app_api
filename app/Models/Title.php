<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Title extends Model
{
    use HasFactory;

    protected $table = 'mobile_app_titles';

    protected $fillable = [
        'chart_title',
        'chart_sub_title',
        'article_title',
        'article_sub_title',
        'podcast_title',
        'podcast_sub_title',
        'articles_main_page_title',
        'podcast_main_page_title',
        'youtube_main_page_title',
        'location',
    ];

    public function Asset(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
