<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vote extends Model
{
    use HasFactory;

    protected $fillable = [
        'chart_id',
        'employee_id',
        'action',
        'dated',
    ];

    public function Chart(): BelongsTo
    {
        return $this->belongsTo(Chart::class);
    }

    public function Employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
