<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StreamLink extends Model
{
    protected $fillable = [
        'name',
        'url',
        'location',
    ];
}
