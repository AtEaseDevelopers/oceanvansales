<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait CalculatesCustomerCredit
{
    /**
     * Calculate outstanding credit for a customer up to a given date.
     * credit = total invoiced - total approved payments
     */
    protected function getCustomerCreditByDate(int $customerId, string $date): float
    {
        $companyId = app()->bound('current_company_id') ? app('current_company_id') : null;

        $totalInvoiced = DB::table('invoice_details')
            ->join('invoices', 'invoice_details.invoice_id', '=', 'invoices.id')
            ->where('invoices.customer_id', $customerId)
            ->where('invoices.company_id', $companyId)
            ->where('invoices.status', 1)
            ->where('invoices.date', '<=', $date)
            ->sum('invoice_details.totalprice') ?: 0;

        $totalPaid = DB::table('invoice_payments')
            ->where('customer_id', $customerId)
            ->where('company_id', $companyId)
            ->where('status', 1)
            ->where('approve_at', '<=', $date)
            ->sum('amount') ?: 0;

        return round($totalInvoiced - $totalPaid, 2);
    }
}
