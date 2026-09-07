<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Scholar extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'batch_id',
        'scholar_type',
    ];

    public function Student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function Batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}
