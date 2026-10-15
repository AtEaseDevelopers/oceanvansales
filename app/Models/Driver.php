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

    // The old offline invoice series every phone shared, so two drivers could print
    // the same number. Each driver now gets their own series; "A" stays reserved.
    const LEGACY_OFFLINE_SERIES = 'A';


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
        'offline_series',
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
        'offline_series' => 'string',
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
     * Offline series codes in the order they are handed out: B..Z, then BA..ZZ.
     */
    public static function offlineSeriesCodes(): array
    {
        $letters = array_slice(range('A', 'Z'), 1);
        $codes = $letters;
        foreach ($letters as $first) {
            foreach (range('A', 'Z') as $second) {
                $codes[] = $first . $second;
            }
        }
        return $codes;
    }

    public static function offlineSeriesMessages(): array
    {
        return [
            'offline_series.regex'  => 'Offline invoice series must be 1 to 3 letters (A-Z).',
            'offline_series.not_in' => 'Offline invoice series "' . self::LEGACY_OFFLINE_SERIES . '" is reserved for the old shared series, please use another letter.',
            'offline_series.unique' => 'This offline invoice series is already used by another driver.',
        ];
    }

    public static function nextFreeOfflineSeries($companyId): ?string
    {
        $used = static::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereNotNull('offline_series')
            ->pluck('offline_series')
            ->all();

        foreach (static::offlineSeriesCodes() as $code) {
            if (!in_array($code, $used, true)) {
                return $code;
            }
        }
        return null;
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
