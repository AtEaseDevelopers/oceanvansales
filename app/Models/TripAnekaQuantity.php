<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripAnekaQuantity extends Model
{
    use HasFactory;

    public $table = 'trip_aneka_quantities';

    public $fillable = [
        'trip_id',
        'product_id',
        'company_id',
        'quantity'
    ];

    protected $casts = [
        'id' => 'integer',
        'trip_id' => 'integer',
        'product_id' => 'integer',
        'company_id' => 'integer',
        'quantity' => 'integer'
    ];

    public function trip()
    {
        return $this->belongsTo(\App\Models\Trip::class, 'trip_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id', 'id');
    }
}
