<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{

    protected $guarded = [];
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function pharmacist()
    {
        return $this->belongsTo(Pharmacist::class);
    }
}
