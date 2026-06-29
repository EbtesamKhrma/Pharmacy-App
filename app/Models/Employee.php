<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Employee extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'pharmacy_id',
        'name',
        'phone',
        'email',
        'password',
        'cv',
        'experience_proof',
        'salary',
        'role',
        'status',
        'first_login',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password'    => 'hashed',
        'first_login' => 'boolean',
        'salary'      => 'decimal:2',
    ];

    // علاقة مع الصيدلية
    public function pharmacy()
    {
        return $this->belongsTo(Pharmacy::class);
    }

    // علاقة مع المبيعات
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
