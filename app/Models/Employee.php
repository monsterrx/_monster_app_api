<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $hidden = ['employee_number', 'gender', 'birthday', 'contact_number', 'address'];

    protected $fillable = [
        'designation_id',
        'employee_number',
        'first_name',
        'last_name',
        'gender',
        'birthday',
        'contact_number',
        'address',
        'location',
    ];

    public function User(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function Designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function Jock(): HasMany
    {
        return $this->hasMany(Jock::class);
    }

    public function Article(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function Votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
}
