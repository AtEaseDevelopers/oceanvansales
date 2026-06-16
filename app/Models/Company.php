<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * Class Company
 * @package App\Models
 * @version December 13, 2023, 7:26 pm +08
 *
 * @property string $code
 * @property string $name
 * @property string $ssm
 * @property string $address1
 * @property string $address2
 * @property string $address3
 * @property string $address4
 * @property integer $group_id
 */
class Company extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'companies';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    // Invoice number prefix per company. Add new companies here.
    const INVOICE_PREFIXES = [
        1 => 'OC',
        2 => 'OS',
    ];
    

    protected $dates = ['deleted_at'];



    public $fillable = [
        'code',
        'name',
        'ssm',
        'tin',
        'phone1',
        'phone2',
        'phone3',
        'address1',
        'address2',
        'address3',
        'address4',
        'group_id'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id'       => 'integer',
        'code'     => 'string',
        'name'     => 'string',
        'ssm'      => 'string',
        'tin'      => 'string',
        'phone1'   => 'string',
        'phone2'   => 'string',
        'phone3'   => 'string',
        'address1' => 'string',
        'address2' => 'string',
        'address3' => 'string',
        'address4' => 'string',
        'group_id' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'code'     => 'required|string|max:255',
        'name'     => 'required|string|max:255',
        'ssm'      => 'required|string|max:255',
        'tin'      => 'nullable|string|max:50',
        'phone1'   => 'nullable|string|max:20',
        'phone2'   => 'nullable|string|max:20',
        'phone3'   => 'nullable|string|max:20',
        'address1' => 'nullable|string|max:255',
        'address2' => 'nullable|string|max:255',
        'address3' => 'nullable|string|max:255',
        'address4' => 'nullable|string|max:255',
    'group_id' => 'required|unique:companies,group_id',
    ];

    public function group()
    {
        return $this->belongsTo(\App\Models\Code::class, 'group_id', 'value')->where('code','customer_group');
    }

    
}
