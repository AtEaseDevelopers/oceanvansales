<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use App\Traits\BelongsToCompany;
use App\Models\Company;

class Invoice extends Model
{
    // use SoftDeletes;

    use HasFactory, BelongsToCompany;

    public $table = 'invoices';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public const STATUS_SYNCED_TO_XERO = 1;
    public const STATUS_VOIDED = 2;

    // AutoCount sync states (autocount_status column)
    public const AUTOCOUNT_NOT_SYNCED = 0;
    public const AUTOCOUNT_QUEUED     = 1;
    public const AUTOCOUNT_SYNCED     = 2;
    public const AUTOCOUNT_FAILED     = 3;

    // Marker written to `remark` when several DOs are combined into one invoice. It is what
    // identifies a "combined invoice" — the only kind that "Merge to Invoice" may target.
    public const COMBINED_REMARK_PREFIX = 'Combined from';

    /** Whether the given remark marks a combined invoice. */
    public static function isCombinedRemark($remark): bool
    {
        return strncmp((string) $remark, self::COMBINED_REMARK_PREFIX, strlen(self::COMBINED_REMARK_PREFIX)) === 0;
    }


    public $fillable = [
        'invoiceno',
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
        'autocount_status',
        'autocount_docno',
        'autocount_error',
        'autocount_synced_at',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'invoiceno' => 'string',
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
        'autocount_status' => 'integer',
        'autocount_synced_at' => 'datetime',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'invoiceno' => 'nullable|string|max:255|string|max:255',
        'date' => 'required',
        'customer_id' => 'required',
        'paymentterm' => 'required',
        'status' => 'required',
        'remark' => 'nullable|string|max:255|string|max:255',
        'created_at' => 'nullable|nullable',
        'updated_at' => 'nullable|nullable'
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

    public function invoicedetail()
    {
        return $this->hasMany(\App\Models\InvoiceDetail::class);
    }

    public function deliveryorders()
    {
        return $this->hasMany(\App\Models\DeliveryOrder::class, 'invoice_id');
    }

    public function invoicepayment()
    {
        return $this->hasMany(\App\Models\InvoicePayment::class);
    }

    public function einvoice()
    {
        return $this->hasOne(\App\Models\Einvoice::class, 'invoice_batch_id', 'id');
    }

    public function consolidatedEinvoices()
    {
        return $this->belongsToMany(\App\Models\ConsolidatedEinvoice::class, 'consolidated_einvoice_invoices', 'invoice_id', 'consolidated_einvoice_id');
    }

    /**
     * Generate the next invoice number for the current company and month.
     * Format: {PREFIX}{YY}{MM}/{SEQUENCE} e.g. OS2606/00001
     * Sequence is per-company per-month, auto-expands beyond 5 digits if needed.
     *
     * When $isDo is true, a "DO" prefix is added in front of the whole number
     * (e.g. DOOC2607/00001). DO-prefixed (customer.is_do) invoices run their own
     * independent sequence counter, separate from regular invoices — the lookup
     * pattern includes the DO prefix so it only matches prior invoices of the same kind.
     */
    public static function generateInvoiceNo(bool $isDo = false): string
    {
        $companyId = app()->bound('current_company_id') ? app('current_company_id') : null;
        $prefix = Company::INVOICE_PREFIXES[$companyId] ?? 'OX';
        $invoicePrefix = $isDo ? 'DO' . $prefix : $prefix;
        $yy = date('y');
        $mm = date('m');
        $pattern = $invoicePrefix . $yy . $mm . '/%';

        $lastInvoice = static::where('invoiceno', 'LIKE', $pattern)
            ->orderByRaw("CAST(SUBSTRING(invoiceno, LOCATE('/', invoiceno) + 1) AS UNSIGNED) DESC")
            ->first();

        $lastSeq = $lastInvoice
            ? intval(substr($lastInvoice->invoiceno, strpos($lastInvoice->invoiceno, '/') + 1))
            : 0;

        $nextSeq = $lastSeq + 1;
        $digits = max(5, strlen((string) $nextSeq));

        return $invoicePrefix . $yy . $mm . '/' . str_pad($nextSeq, $digits, '0', STR_PAD_LEFT);
    }

    /**
     * Generate the next OFFLINE invoice number for the current company and month.
     * Format: {PREFIX}{YY}{MM}/A{SEQUENCE} e.g. OC2608/A00081
     * Used by the driver app when it creates an invoice while offline, so it never
     * collides with the server-assigned online sequence from generateInvoiceNo() —
     * the "A" marker keeps the two sequences independent (and the numeric-cast used
     * to find the last online sequence naturally ignores "A..." rows since they
     * cast to 0, so offline numbers never interfere with online numbering either).
     */
    public static function generateOfflineInvoiceNo(bool $isDo = false): string
    {
        $companyId = app()->bound('current_company_id') ? app('current_company_id') : null;
        $prefix = Company::INVOICE_PREFIXES[$companyId] ?? 'OX';
        $invoicePrefix = $isDo ? 'DO' . $prefix : $prefix;
        $yy = date('y');
        $mm = date('m');
        $pattern = $invoicePrefix . $yy . $mm . '/A%';

        $lastInvoice = static::where('invoiceno', 'LIKE', $pattern)
            ->orderByRaw("CAST(SUBSTRING(invoiceno, LOCATE('/A', invoiceno) + 2) AS UNSIGNED) DESC")
            ->first();

        $lastSeq = $lastInvoice
            ? intval(substr($lastInvoice->invoiceno, strpos($lastInvoice->invoiceno, '/A') + 2))
            : 0;

        $nextSeq = $lastSeq + 1;
        $digits = max(5, strlen((string) $nextSeq));

        return $invoicePrefix . $yy . $mm . '/A' . str_pad($nextSeq, $digits, '0', STR_PAD_LEFT);
    }

    public function getDateAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }


}
