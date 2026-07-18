<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use App\Traits\BelongsToCompany;

class Trip extends Model
{
    // use SoftDeletes;

    use HasFactory, BelongsToCompany;

    public $table = 'trips';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';



    public $fillable = [
        'date',
        'driver_id',
        'kelindan_id',
        'lorry_id',
        'cash',
        'type',
        'stock_snapshot',
        'diesel',
        'diesel_images',
        'tol',
        'tol_images',
        'others',
        'others_images'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'date' => 'datetime:d-m-Y H:i:s',
        'driver_id' => 'integer',
        'kelindan_id' => 'integer',
        'lorry_id' => 'integer',
        'cash' => 'float',
        'type' => 'integer',
        'stock_snapshot' => 'array',
        'diesel' => 'float',
        'diesel_images' => 'array',
        'tol' => 'float',
        'tol_images' => 'array',
        'others' => 'float',
        'others_images' => 'array'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'date' => 'required',
        'driver_id' => 'required',
        'kelindan_id' => 'required',
        'lorry_id' => 'required',
        'type' => 'required',
        'created_at' => 'nullable|nullable',
        'updated_at' => 'nullable|nullable'
    ];

    public function driver()
    {
        return $this->belongsTo(\App\Models\Driver::class, 'driver_id', 'id');
    }

    public function kelindan()
    {
        return $this->belongsTo(\App\Models\Kelindan::class, 'kelindan_id', 'id');
    }

    public function lorry()
    {
        return $this->belongsTo(\App\Models\Lorry::class, 'lorry_id', 'id');
    }

    public function getDateAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y H:i:s');
    }


}
