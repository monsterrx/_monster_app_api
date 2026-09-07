<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'description',
        'icon',
        'dark_mode_icon',
    ];

    public function Article(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
