<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use App\Traits\BelongsToCompany;

class Driver extends Model
{
    // use SoftDeletes;

    use HasFactory, BelongsToCompany;

    public $table = 'drivers';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    // Offline invoice/DO numbers are "A" + (offline_range * OFFLINE_RANGE_SIZE + seq), so each
    // driver prints from their own block: range 1 -> A10001.., range 2 -> A20001... The mobile
    // app only reads the digits after "/A", so the letter must stay "A". No range = the old
    // shared A00001-A09999 that every phone drew from.
    const OFFLINE_RANGE_SIZE = 10000;
    const OFFLINE_RANGE_MAX = 65535;


    protected $dates = ['deleted_at'];



    public $fillable = [
        'employeeid',
        'password',
        'name',
        'ic',
        'phone',
        'commissionrate',
        'bankdetails1',
        'bankdetails2',
        'firstvaccine',
        'secondvaccine',
        'temperature',
        'status',
        'remark',
        'session',
        'invoice_runningnumber',
        'offline_range',
        'trip_id',
        'lorry_id',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'employeeid' => 'string',
        'password' => 'string',
        'id' => 'integer',
        'name' => 'string',
        'ic' => 'string',
        'phone' => 'string',
        'commissionrate' => 'float',
        'bankdetails1' => 'string',
        'bankdetails2' => 'string',
        'firstvaccine' => 'date:d-m-Y',
        'secondvaccine' => 'date:d-m-Y',
        'temperature' => 'float',
        'status' => 'integer',
        'remark' => 'string',
        'session' => 'string',
        'trip_id' => 'integer',
        'lorry_id' => 'integer',
        'offline_range' => 'integer',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'employeeid' => 'required|string|max:20',
        'password' => 'required|string|max:65535',
        'name' => 'required|string|max:255',
        'ic' => 'nullable|string|max:20',
        'phone' => 'nullable|string|max:255',
        // 'commissionrate' => 'required|numeric|min:0|max:100',
        'bankdetails1' => 'nullable|string|max:255',
        'bankdetails2' => 'nullable|string|max:255',
        'firstvaccine' => 'nullable',
        'secondvaccine' => 'nullable',
        'temperature' => 'nullable|numeric',
        'status' => 'required',
        'remark' => 'nullable|string|max:255',
        'created_at' => 'nullable',
        'updated_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    public function lorry()
    {
        return $this->belongsTo(\App\Models\Lorry::class, 'lorry_id', 'id');
    }

    /**
     * First and last sequence number a range may use: null (legacy) -> [1, 9999],
     * 1 -> [10001, 19999], 2 -> [20001, 29999] ...
     */
    public static function offlineNumberBounds(?int $range): array
    {
        $start = ($range ?? 0) * self::OFFLINE_RANGE_SIZE;
        return [$start + 1, $start + self::OFFLINE_RANGE_SIZE - 1];
    }

    // What the driver's phone prints for a range, e.g. "A20001, A20002 ...", for admin screens.
    public static function offlineRangeExample(?int $range): string
    {
        if (!$range) {
            return '-';
        }
        [$first] = static::offlineNumberBounds($range);
        return 'A' . $first . ', A' . ($first + 1) . ' ...';
    }

    public static function offlineRangeMessages(): array
    {
        return [
            'offline_range.integer' => 'Offline number range must be a whole number.',
            'offline_range.min'     => 'Offline number range must be 1 or more.',
            'offline_range.max'     => 'Offline number range can be at most ' . self::OFFLINE_RANGE_MAX . '.',
            'offline_range.unique'  => 'This offline number range is already used by another driver.',
        ];
    }

    public static function nextFreeOfflineRange($companyId): int
    {
        $used = static::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereNotNull('offline_range')
            ->pluck('offline_range')
            ->all();

        $range = 1;
        while (in_array($range, $used)) {
            $range++;
        }
        return $range;
    }

    public function getFirstVaccineAttribute($value)
    {
        if($value != ''){
            return Carbon::parse($value)->format('d-m-Y');
        }else{
            return '';
        }
    }

    public function getSecondVaccineAttribute($value)
    {
        if($value != ''){
            return Carbon::parse($value)->format('d-m-Y');
        }else{
            return '';
        }
    }


}
