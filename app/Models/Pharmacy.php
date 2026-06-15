<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pharmacy extends Model
{
    protected $guarded = [];

    protected $hidden = ['updated_at', 'created_at','status'];

    public function pharmacist(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Pharmacist::class);
    }
}
