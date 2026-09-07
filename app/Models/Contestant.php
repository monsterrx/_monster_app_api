<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contestant extends Model implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable, CanResetPassword, HasFactory;

    protected $fillable = [
        'phone_number',
        'first_name',
        'last_name',
        'birthday',
        'city',
        'email',
        'password',
    ];

    public function Contest(): BelongsToMany
    {
        return $this->belongsToMany(Contest::class, 'contestant_giveaway', 'giveaway_id', 'contestant_id');
    }
}
