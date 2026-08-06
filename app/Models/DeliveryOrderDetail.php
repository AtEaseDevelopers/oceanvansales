<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToCompany;

class DeliveryOrderDetail extends Model
{
    // use SoftDeletes;

    use HasFactory, BelongsToCompany;

    public $table = 'deliveryorder_details';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'deliveryorder_id',
        'product_id',
        'quantity',
        'price',
        'totalprice',
        'remark',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'deliveryorder_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
        'price' => 'float',
        'totalprice' => 'float',
        'remark' => 'string',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'deliveryorder_id' => 'required',
        'product_id' => 'required',
        'quantity' => 'required|integer',
        'price' => 'required|numeric',
        'remark' => 'nullable|string|max:255',
        'created_at' => 'nullable',
        'updated_at' => 'nullable',
    ];

    public function deliveryorder()
    {
        return $this->belongsTo(\App\Models\DeliveryOrder::class, 'deliveryorder_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id', 'id');
    }
}
