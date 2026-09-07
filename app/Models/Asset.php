<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model
{
    use HasFactory;

    protected $table = 'mobile_app_assets';

    protected $fillable = [
        'title_id',
        'logo',
        'chart_icon',
        'article_icon',
        'podcast_icon',
        'article_page_icon',
        'youtube_page_icon',
        'is_dark_mode',
        'location',
    ];

    public function Title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }
}
