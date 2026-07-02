<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompany;

class ProductPrice extends Model
{
    use HasFactory, BelongsToCompany;

    public $table = 'product_prices';

    public $fillable = [
        'product_id',
        'company_id',
        'price',
        'status',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'company_id' => 'integer',
        'price'      => 'float',
        'status'     => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
