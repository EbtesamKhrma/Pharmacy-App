<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{

    protected $guarded = [];
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    public function medicines()
    {
        return $this->hasMany(Medicine::class);
    }


}
