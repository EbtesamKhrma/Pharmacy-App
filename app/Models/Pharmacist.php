<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Pharmacist extends Authenticatable
{
    use HasApiTokens;

    protected $guarded = [];

    protected $hidden = ['password','created_at','updated_at'];

    public function pharmacies()
    {
        return $this->hasMany(Pharmacy::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'pharmacist_id');
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class, 'pharmacist_id');
    }
}
