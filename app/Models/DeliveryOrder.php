<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use App\Traits\BelongsToCompany;
use App\Models\Company;

class DeliveryOrder extends Model
{
    // use SoftDeletes;

    use HasFactory, BelongsToCompany;

    public $table = 'deliveryorders';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    // Status values (mirrors the "Completed/Cancelled" map in DeliveryOrderDataTable).
    const STATUS_COMPLETED = 1;
    const STATUS_CANCELLED = 2;

    public $fillable = [
        'invoiceno',
        'uuid',
        'date',
        'customer_id',
        'driver_id',
        'kelindan_id',
        'agent_id',
        'supervisor_id',
        'paymentterm',
        'status',
        'remark',
        'chequeno',
        'attachment',
        'trip_id',
        'invoice_id',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'invoiceno' => 'string',
        'uuid' => 'string',
        'date' => 'date:d-m-Y',
        'customer_id' => 'integer',
        'driver_id' => 'integer',
        'kelindan_id' => 'integer',
        'agent_id' => 'integer',
        'supervisor_id' => 'integer',
        'paymentterm' => 'string',
        'status' => 'integer',
        'remark' => 'string',
        'attachment' => 'string',
        'trip_id' => 'integer',
        'invoice_id' => 'integer',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'invoiceno' => 'nullable|string|max:255',
        'date' => 'required',
        'customer_id' => 'required',
        'paymentterm' => 'required',
        'status' => 'required',
        'remark' => 'nullable|string|max:255',
        'created_at' => 'nullable',
        'updated_at' => 'nullable',
    ];

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class, 'customer_id', 'id');
    }

    public function driver()
    {
        return $this->belongsTo(\App\Models\Driver::class, 'driver_id', 'id');
    }

    public function kelindan()
    {
        return $this->belongsTo(\App\Models\Kelindan::class, 'kelindan_id', 'id');
    }

    public function agent()
    {
        return $this->belongsTo(\App\Models\Agent::class, 'agent_id', 'id');
    }

    public function supervisor()
    {
        return $this->belongsTo(\App\Models\Supervisor::class, 'supervisor_id', 'id');
    }

    public function deliveryorderdetail()
    {
        return $this->hasMany(\App\Models\DeliveryOrderDetail::class, 'deliveryorder_id');
    }

    public function invoice()
    {
        return $this->belongsTo(\App\Models\Invoice::class, 'invoice_id', 'id');
    }

    /**
     * Generate the next delivery order number for the current company and month.
     * Format: DO{PREFIX}{YY}{MM}/{SEQUENCE} e.g. DOOC2608/00001
     * Mirrors Invoice::generateInvoiceNo()'s DO-prefixed sequence, but runs against
     * this table's own independent counter.
     */
    public static function generateDoNo(): string
    {
        $companyId = app()->bound('current_company_id') ? app('current_company_id') : null;
        $prefix = Company::INVOICE_PREFIXES[$companyId] ?? 'OX';
        $doPrefix = 'DO' . $prefix;
        $yy = date('y');
        $mm = date('m');
        $pattern = $doPrefix . $yy . $mm . '/%';

        $lastDo = static::where('invoiceno', 'LIKE', $pattern)
            ->orderByRaw("CAST(SUBSTRING(invoiceno, LOCATE('/', invoiceno) + 1) AS UNSIGNED) DESC")
            ->first();

        $lastSeq = $lastDo
            ? intval(substr($lastDo->invoiceno, strpos($lastDo->invoiceno, '/') + 1))
            : 0;

        $nextSeq = $lastSeq + 1;
        $digits = max(5, strlen((string) $nextSeq));

        return $doPrefix . $yy . $mm . '/' . str_pad($nextSeq, $digits, '0', STR_PAD_LEFT);
    }

    /**
     * Generate the next OFFLINE delivery order number for the current company and month.
     * Format: DO{PREFIX}{YY}{MM}/A{SEQUENCE} e.g. DOOC2608/A00001
     * Mirrors Invoice::generateOfflineInvoiceNo() — used when the driver app creates
     * a DO while offline, kept on its own "A" sequence so it never collides with the
     * server-assigned online DO numbers from generateDoNo().
     */
    public static function generateOfflineDoNo(): string
    {
        $companyId = app()->bound('current_company_id') ? app('current_company_id') : null;
        $prefix = Company::INVOICE_PREFIXES[$companyId] ?? 'OX';
        $doPrefix = 'DO' . $prefix;
        $yy = date('y');
        $mm = date('m');
        $pattern = $doPrefix . $yy . $mm . '/A%';

        $lastDo = static::where('invoiceno', 'LIKE', $pattern)
            ->orderByRaw("CAST(SUBSTRING(invoiceno, LOCATE('/A', invoiceno) + 2) AS UNSIGNED) DESC")
            ->first();

        $lastSeq = $lastDo
            ? intval(substr($lastDo->invoiceno, strpos($lastDo->invoiceno, '/A') + 2))
            : 0;

        $nextSeq = $lastSeq + 1;
        $digits = max(5, strlen((string) $nextSeq));

        return $doPrefix . $yy . $mm . '/A' . str_pad($nextSeq, $digits, '0', STR_PAD_LEFT);
    }

    public function getDateAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }
}
