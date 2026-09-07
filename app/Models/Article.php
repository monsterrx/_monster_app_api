<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'unique_id',
        'employee_id',
        'category_id',
        'title',
        'heading',
        'published_at',
        'image',
        'location',
    ];

    public function Category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function Image(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    public function Social(): HasMany
    {
        return $this->hasMany(Social::class);
    }

    public function Relevant(): HasMany
    {
        return $this->hasMany(Relevant::class);
    }

    public function Content(): HasMany
    {
        return $this->hasMany(Content::class);
    }

    public function Employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
