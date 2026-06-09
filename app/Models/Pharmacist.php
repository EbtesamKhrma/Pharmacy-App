<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Pharmacist extends Authenticatable
{
    use HasApiTokens;

    protected $guarded = [];

    protected $hidden = ['password'];

    public function pharmacy()
    {
        return $this->hasOne(Pharmacy::class);
    }
}
