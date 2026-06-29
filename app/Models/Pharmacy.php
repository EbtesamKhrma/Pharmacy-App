<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pharmacy extends Model
{
    protected $guarded = [];
    protected $hidden = ['updated_at', 'created_at', 'status'];

    public function pharmacist()
    {
        return $this->belongsTo(Pharmacist::class, 'pharmacist_id');
    }

    public function medicines()
    {
        return $this->hasMany(Medicine::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class)->where('status', 'approved');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
