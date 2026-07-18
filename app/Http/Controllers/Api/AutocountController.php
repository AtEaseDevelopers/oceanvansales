<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Customer;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Endpoints consumed by the AutoCount desktop plugin.
 *
 * Flow: the web user selects invoices and queues them (autocount_status = QUEUED).
 * The plugin polls `queued`, creates a Sales Invoice for each in AutoCount, then
 * reports back via `synced` / `failed`. Nothing is fetched unless it was queued.
 *
 * Each plugin instance is connected to one AutoCount account book and identifies
 * itself with a `book` parameter (its configured COMPANY_CODE). We map that code to
 * a company (branch) and only hand back that branch's invoices, so an invoice is
 * never pushed into the wrong account book.
 */
class AutocountController extends Controller
{
    /**
     * GET /api/autocount/companies
     * List the companies (account books) the plugin can sync into. The desktop plugin
     * fetches this so the user can toggle the active company from inside AutoCount,
     * instead of hardcoding COMPANY_CODE in a .env file. Only companies with a code
     * are returned, since the code is what maps a branch to an AutoCount account book.
     */
    public function companies()
    {
        $companies = Company::whereNotNull('code')
            ->where('code', '!=', '')
            ->orderBy('code')
            ->get(['code', 'name']);

        $data = $companies->map(function (Company $company) {
            return [
                'code' => $company->code,
                'name' => $company->name,
            ];
        })->values();

        return response()->json($data);
    }

    /**
     * GET /api/autocount/invoices/queued?book={company_code}
     * Return the queued invoices for the account book (branch) the plugin is connected
     * to. When the book is missing or matches no branch we return nothing, so invoices
     * are never synced into an unknown/unintended account book.
     */
    public function queued(Request $request)
    {
        $book = trim((string) $request->query('book', ''));

        if ($book === '') {
            Log::warning('AutoCount queued: request without a book (account book) identifier; syncing nothing.');
            return response()->json([]);
        }

        $company = Company::where('code', $book)->first();

        if (!$company) {
            Log::warning("AutoCount queued: no branch matches account book code '{$book}'; syncing nothing.");
            return response()->json([]);
        }

        // Ignore the ambient company scope: filter strictly by the resolved branch.
        $invoices = Invoice::withoutGlobalScope('company')
            ->where('autocount_status', Invoice::AUTOCOUNT_QUEUED)
            ->where('company_id', $company->id)
            ->orderBy('id')
            ->get();

        $data = $invoices->map(function (Invoice $invoice) {
            $customer = Customer::find($invoice->customer_id);

            $details = InvoiceDetail::where('invoice_id', $invoice->id)->get()->map(function ($detail) {
                $product = Product::find($detail->product_id);

                // Push the product master alongside the line so the plugin can create the
                // stock item in AutoCount (when missing) and post a real item line — this
                // is what makes the item code appear on the AutoCount invoice detail.
                return [
                    'item_code'           => $product->code ?? null,
                    'item_name'           => $product->name ?? null,
                    'item_price'          => $product->price ?? null,
                    'classification_code' => $product->classification_code ?? null,
                    'description'         => $detail->remark ?: ($product->name ?? ''),
                    'quantity'            => $detail->quantity,
                    'unit_price'          => $detail->price,
                ];
            })->values();

            return [
                'id'          => $invoice->id,
                'invoiceno'   => $invoice->invoiceno,
                'date'        => Carbon::parse($invoice->getRawOriginal('date'))->format('Y-m-d'),
                'company_id'  => $invoice->company_id,
                // Payment-term code (1=Cash, 2=Credit, 3=Online BankIn, 4=E-wallet,
                // 5=Cheque); the plugin uses it to pick a credit term for new debtors.
                'paymentterm' => $invoice->paymentterm ?? ($customer->paymentterm ?? null),
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
