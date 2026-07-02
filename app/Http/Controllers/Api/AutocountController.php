<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Endpoints consumed by the AutoCount desktop plugin.
 *
 * Flow: the web user selects invoices and queues them (autocount_status = QUEUED).
 * The plugin polls `queued`, creates a Sales Invoice for each in AutoCount, then
 * reports back via `synced` / `failed`. Nothing is fetched unless it was queued.
 */
class AutocountController extends Controller
{
    /**
     * GET /api/autocount/invoices/queued
     * Return every queued invoice with the data the plugin needs to build a Sales Invoice.
     */
    public function queued(Request $request)
    {
        $invoices = Invoice::where('autocount_status', Invoice::AUTOCOUNT_QUEUED)
            ->orderBy('id')
            ->get();

        $data = $invoices->map(function (Invoice $invoice) {
            $customer = Customer::find($invoice->customer_id);

            $details = InvoiceDetail::where('invoice_id', $invoice->id)->get()->map(function ($detail) {
                $product = Product::find($detail->product_id);

                return [
                    'item_code'   => $product->code ?? null,
                    'description' => $detail->remark ?: ($product->name ?? ''),
                    'quantity'    => $detail->quantity,
                    'unit_price'  => $detail->price,
                ];
            })->values();

            return [
                'id'         => $invoice->id,
                'invoiceno'  => $invoice->invoiceno,
                'date'       => Carbon::parse($invoice->getRawOriginal('date'))->format('Y-m-d'),
                'company_id' => $invoice->company_id,
                'customer'   => [
                    'code'     => $customer->code ?? null,
                    'company'  => $customer->company ?? null,
                    'phone'    => $customer->phone ?? null,
                    'address'  => $customer->address ?? null,
                    'postcode' => $customer->postcode ?? null,
                    'city'     => $customer->city ?? null,
                    'state'    => $customer->state ?? null,
                    'email'    => $customer->email ?? null,
                ],
                'details' => $details,
            ];
        })->values();

        return response()->json($data);
    }

    /**
     * POST /api/autocount/invoices/{invoice}/synced
     * The plugin created the AutoCount document successfully.
     */
    public function synced(Request $request, $invoice)
    {
        $record = Invoice::find($invoice);
        if (!$record) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        $record->update([
            'autocount_status'    => Invoice::AUTOCOUNT_SYNCED,
            'autocount_docno'     => $request->input('docno'),
            'autocount_error'     => null,
            'autocount_synced_at' => now(),
        ]);

        return response()->json(['message' => 'OK']);
    }

    /**
     * POST /api/autocount/invoices/{invoice}/failed
     * The plugin failed to create the AutoCount document.
     */
    public function failed(Request $request, $invoice)
    {
        $record = Invoice::find($invoice);
        if (!$record) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        $record->update([
            'autocount_status' => Invoice::AUTOCOUNT_FAILED,
            'autocount_error'  => $request->input('error'),
        ]);

        return response()->json(['message' => 'OK']);
    }
}
