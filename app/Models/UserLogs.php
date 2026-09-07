<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLogs extends Model
{
    protected $dates = [
        'deleted_at',
    ];

    protected $fillable = [
        'user_id',
        'action',
        'employee_id',
        'location',
    ];

    public function User(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function Employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
