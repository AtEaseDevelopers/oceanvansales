<?php

namespace App\Http\Controllers;

use App\DataTables\DeliveryOrderDataTable;
use App\Http\Requests\CreateDeliveryOrderRequest;
use App\Http\Requests\UpdateDeliveryOrderRequest;
use App\Repositories\DeliveryOrderRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Exception;

class DeliveryOrderController extends AppBaseController
{
    /** @var DeliveryOrderRepository $deliveryOrderRepository*/
    private $deliveryOrderRepository;

    public function __construct(DeliveryOrderRepository $deliveryOrderRepo)
    {
        $this->deliveryOrderRepository = $deliveryOrderRepo;
    }

    /**
     * Display a listing of the DeliveryOrder.
     *
     * @param DeliveryOrderDataTable $deliveryOrderDataTable
     *
     * @return Response
     */
    public function index(Request $request, DeliveryOrderDataTable $deliveryOrderDataTable)
    {
        return $deliveryOrderDataTable->render('delivery_orders.index');
    }

    /**
     * Show the form for creating a new DeliveryOrder.
     *
     * @return Response
     */
    public function create()
    {
        return view('delivery_orders.create');
    }

    /**
     * Store a newly created DeliveryOrder in storage.
     *
     * @param CreateDeliveryOrderRequest $request
     *
     * @return Response
     */
    public function store(CreateDeliveryOrderRequest $request)
    {
        $input = $request->all();

        $input['date'] = date_create($input['date']);
        $input['status'] = 1;
        if (empty($input['invoiceno'])) {
            $input['invoiceno'] = DeliveryOrder::generateDoNo();
        }

        if ($request->hasFile('attachment')) {
            $input['attachment'] = $request->file('attachment')->store('deliveryorders-attachments', 'public');
        }

        $deliveryOrder = $this->deliveryOrderRepository->create($input);

        Flash::success('Delivery Order saved successfully.');

        if ($input['method'] == 1) {
            return redirect(route('deliveryOrders.index'));
        } else {
            return redirect(route('deliveryOrders.show', encrypt($deliveryOrder->id)));
        }
    }

    /**
     * Display the specified DeliveryOrder.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $deliveryOrder = $this->deliveryOrderRepository->find($id);

        if (empty($deliveryOrder)) {
            Flash::error('Delivery Order not found');

            return redirect(route('deliveryOrders.index'));
        }

        $deliveryorderdetails = DeliveryOrderDetail::with('product')->where('deliveryorder_id', $id)->get()->toArray();

        return view('delivery_orders.show')->with('deliveryOrder', $deliveryOrder)->with('deliveryorderdetails', $deliveryorderdetails)->with('id', $id);
    }

    /**
     * Show the form for editing the specified DeliveryOrder.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $deliveryOrder = $this->deliveryOrderRepository->find($id);

        if (empty($deliveryOrder)) {
            Flash::error('Delivery Order not found');

            return redirect(route('deliveryOrders.index'));
        }

        return view('delivery_orders.edit')->with('deliveryOrder', $deliveryOrder);
    }

    /**
     * Update the specified DeliveryOrder in storage.
     *
     * @param int $id
     * @param UpdateDeliveryOrderRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateDeliveryOrderRequest $request)
    {
        $id = Crypt::decrypt($id);
        $deliveryOrder = $this->deliveryOrderRepository->find($id);

        if (empty($deliveryOrder)) {
            Flash::error('Delivery Order not found');

            return redirect(route('deliveryOrders.index'));
        }

        $input = $request->all();

        $input['date'] = date_create($input['date']);
        if (empty($input['invoiceno'])) {
            $input['invoiceno'] = DeliveryOrder::generateDoNo();
        }

        if ($request->hasFile('attachment')) {
            if ($deliveryOrder->attachment) {
                Storage::disk('public')->delete($deliveryOrder->attachment);
            }
            $input['attachment'] = $request->file('attachment')->store('deliveryorders-attachments', 'public');
        }

        $deliveryOrder = $this->deliveryOrderRepository->update($input, $id);

        Flash::success('Delivery Order updated successfully.');

        if ($input['method'] == 1) {
            return redirect(route('deliveryOrders.index'));
        } else {
            return redirect(route('deliveryOrders.show', encrypt($deliveryOrder->id)));
        }
    }

    /**
     * Remove the specified DeliveryOrder from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $deliveryOrder = $this->deliveryOrderRepository->find($id);

        if (empty($deliveryOrder)) {
            Flash::error('Delivery Order not found');

            return redirect(route('deliveryOrders.index'));
        }

        if (!empty($deliveryOrder->invoice_id)) {
            Flash::error('Delivery Order had already been converted to an invoice');

            return redirect(route('deliveryOrders.index'));
        }

        DeliveryOrderDetail::where('deliveryorder_id', $deliveryOrder->id)->delete();
        $this->deliveryOrderRepository->delete($id);

        Flash::success('Delivery Order deleted successfully.');

        return redirect(route('deliveryOrders.index'));
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];

        $count = 0;

        foreach ($ids as $id) {
            $deliveryOrder = $this->deliveryOrderRepository->find($id);

            if (empty($deliveryOrder) || !empty($deliveryOrder->invoice_id)) {
                continue;
            }

            DeliveryOrderDetail::where('deliveryorder_id', $deliveryOrder->id)->delete();

            $count = $count + DeliveryOrder::destroy($id);
        }

        return $count;
    }

    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];

        $count = DeliveryOrder::whereIn('id', $ids)->update(['status' => $status]);

        return $count;
    }

    public function getcustomer($id)
    {
        $customer = Customer::where('id', $id)->first();

        if (empty($customer)) {
            return response()->json(['status' => false, 'message' => 'Customer not found!']);
        }

        return response()->json(['status' => true, 'message' => 'Customer found!', 'data' => $customer]);
    }

    public function detail(Request $request, $id)
    {
        $id = Crypt::decrypt($id);
        $deliveryOrder = $this->deliveryOrderRepository->find($id);

        if (empty($deliveryOrder)) {
            Flash::error('Delivery Order not found');

            return redirect(route('deliveryOrders.index'));
        }

        return view('delivery_orders.detail')->with('id', $id);
    }

    public function adddetail($id, Request $request)
    {
        $id = Crypt::decrypt($id);

        $deliveryOrder = $this->deliveryOrderRepository->find($id);

        if (empty($deliveryOrder)) {
            Flash::error('Delivery Order not found');

            return redirect(route('deliveryOrders.index'));
        }

        $input = $request->all();

        $deliveryorderdetail = new DeliveryOrderDetail();
        $deliveryorderdetail->deliveryorder_id = $id;
        $deliveryorderdetail->product_id = $input['product_id'];
        $deliveryorderdetail->quantity = $input['quantity'];
        $deliveryorderdetail->price = $input['price'];
        $deliveryorderdetail->totalprice = $input['quantity'] * $input['price'];
        $deliveryorderdetail->remark = $input['remark'] ?? null;
        $deliveryorderdetail->save();

        Flash::success('Delivery Order Detail saved successfully.');

        return redirect(route('deliveryOrders.show', encrypt($id)));
    }

    public function deletedetail($id)
    {
        $id = Crypt::decrypt($id);

        $deliveryorderdetail = DeliveryOrderDetail::where('id', $id)->first();

        if (empty($deliveryorderdetail)) {
            Flash::error('Delivery Order Detail not found');

            return redirect()->back();
        }

        $deliveryorderdetailId = $deliveryorderdetail->deliveryorder_id;
        $deliveryorderdetail->delete();

        Flash::success('Delivery Order Detail deleted successfully.');

        return redirect(route('deliveryOrders.show', encrypt($deliveryorderdetailId)));
    }

    public function print()
    {
        return view('delivery_orders.print');
    }

    public function getDoViewPDF($id, $function)
    {
        $id = Crypt::decrypt($id);
        $deliveryOrder = DeliveryOrder::where('id', $id)
            ->with('customer')
            ->with('driver')
            ->with('deliveryorderdetail.product')
            ->first();

        if (empty($deliveryOrder)) {
            abort('404');
        }

        $min = 450;
        $each = 23;
        $height = (count($deliveryOrder['deliveryorderdetail']) * $each) + $min;

        $company = \App\Models\Company::find($deliveryOrder->company_id);

        try {
            $pdf = Pdf::loadView('delivery_orders.print', array(
                'deliveryOrder' => $deliveryOrder,
                'company' => $company,
            ));

            if ($function == 'download') {
                return $pdf->setPaper(array(0, 0, 300, $height), 'portrait')->setOptions(['isPhpEnabled' => true, 'isRemoteEnabled' => true])->download('download.pdf');
            } elseif ($function == 'view') {
                return $pdf->setPaper(array(0, 0, 300, $height), 'portrait')->setOptions(['isPhpEnabled' => true, 'isRemoteEnabled' => true])->stream('view.pdf');
            }
        } catch (Exception $e) {
            abort(404);
        }
    }

    /**
     * Convert selected Delivery Orders into Invoices, 1 DO -> 1 Invoice each.
     * Each converted DO's own customer is kept as the new Invoice's customer.
     * Already-converted DOs (invoice_id already set) are skipped.
     */
    public function convert(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json(['message' => 'Please select at least one delivery order.'], 422);
        }

        $converted = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            $deliveryOrder = DeliveryOrder::with('deliveryorderdetail')->find($id);

            if (empty($deliveryOrder) || !empty($deliveryOrder->invoice_id)) {
                $skipped++;
                continue;
            }

            $customer = Customer::find($deliveryOrder->customer_id);

            DB::beginTransaction();
            try {
                $invoice = new Invoice();
                $invoice->date = $deliveryOrder->getRawOriginal('date');
                // Converted invoices always get a normal invoice number — never the DO-prefixed
                // sequence — regardless of the source customer's is_do flag.
                $invoice->invoiceno = Invoice::generateInvoiceNo(false);
                $invoice->customer_id = $deliveryOrder->customer_id;
                $invoice->driver_id = $deliveryOrder->driver_id;
                $invoice->kelindan_id = $deliveryOrder->kelindan_id;
                $invoice->agent_id = $deliveryOrder->agent_id;
                $invoice->supervisor_id = $deliveryOrder->supervisor_id;
                $invoice->paymentterm = $deliveryOrder->paymentterm;
                $invoice->status = 1;
                $invoice->remark = $deliveryOrder->remark;
                $invoice->chequeno = $deliveryOrder->chequeno;
                $invoice->trip_id = $deliveryOrder->trip_id;
                $invoice->save();

                foreach ($deliveryOrder->deliveryorderdetail as $line) {
                    $detail = new InvoiceDetail();
                    $detail->invoice_id = $invoice->id;
                    $detail->product_id = $line->product_id;
                    $detail->deliveryorder_id = $deliveryOrder->id;
                    $detail->quantity = $line->quantity;
                    $detail->price = $line->price;
                    $detail->totalprice = $line->totalprice;
                    $detail->remark = $line->remark;
                    $detail->save();
                }

                $deliveryOrder->invoice_id = $invoice->id;
                $deliveryOrder->save();

                DB::commit();
                $converted++;
            } catch (\Throwable $e) {
                DB::rollBack();
                report($e);
                $skipped++;
            }
        }

        return response()->json([
            'message' => $converted . ' delivery order(s) converted to invoice.' . ($skipped ? ' ' . $skipped . ' skipped.' : ''),
            'count' => $converted,
        ]);
    }

    /**
     * Combine the selected Delivery Orders' items into a single Invoice.
     * The Invoice's customer is always fixed to "ANEKA INTERTRADE MARKETING SDN BHD",
     * regardless of each DO's own (outlet-specific) customer.
     */
    public function combineConvert(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json(['message' => 'Please select at least one delivery order.'], 422);
        }

        $deliveryOrders = DeliveryOrder::with('deliveryorderdetail')
            ->whereIn('id', $ids)
            ->whereNull('invoice_id')
            ->get();

        if ($deliveryOrders->isEmpty()) {
            return response()->json(['message' => 'Selected delivery order(s) are already converted or not found.'], 422);
        }

        $customer = Customer::where('company', 'ANEKA INTERTRADE MARKETING SDN BHD')->first();

        if (empty($customer)) {
            return response()->json(['message' => 'Fixed customer "ANEKA INTERTRADE MARKETING SDN BHD" not found. Please create it first.'], 422);
        }

        DB::beginTransaction();
        try {
            $first = $deliveryOrders->first();

            $invoice = new Invoice();
            // Use the date chosen in the dialog; fall back to the first DO's own date —
            // never the conversion (today's) date.
            $chosenDate = $request->input('date');
            $invoiceDate = !empty($chosenDate) ? date_create($chosenDate) : false;
            $invoice->date = $invoiceDate ?: $first->getRawOriginal('date');
            // Converted invoices always get a normal invoice number — never the DO-prefixed sequence.
            $invoice->invoiceno = Invoice::generateInvoiceNo(false);
            $invoice->customer_id = $customer->id;
            $invoice->driver_id = $first->driver_id;
            $invoice->kelindan_id = $first->kelindan_id;
            $invoice->agent_id = $customer->agent_id;
            $invoice->supervisor_id = $customer->supervisor_id;
            $invoice->paymentterm = $customer->paymentterm;
            $invoice->status = 1;
            $invoice->remark = 'Combined from ' . $deliveryOrders->count() . ' delivery order(s)';
            $invoice->trip_id = $first->trip_id;
            $invoice->save();

            foreach ($deliveryOrders as $deliveryOrder) {
                foreach ($deliveryOrder->deliveryorderdetail as $line) {
                    $detail = new InvoiceDetail();
                    $detail->invoice_id = $invoice->id;
                    $detail->product_id = $line->product_id;
                    $detail->deliveryorder_id = $deliveryOrder->id;
                    $detail->quantity = $line->quantity;
                    $detail->price = $line->price;
                    $detail->totalprice = $line->totalprice;
                    $detail->remark = $line->remark;
                    $detail->save();
                }

                $deliveryOrder->invoice_id = $invoice->id;
                $deliveryOrder->save();
            }

            DB::commit();

            return response()->json([
                'message' => $deliveryOrders->count() . ' delivery order(s) combined into invoice ' . $invoice->invoiceno . '.',
                'count' => $deliveryOrders->count(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(['message' => 'Something went wrong. Please contact administrator.'], 500);
        }
    }

    /**
     * Merge the selected Delivery Orders' lines into an EXISTING invoice chosen by the user
     * (see mergeInvoices() for the picker), then reset that invoice's AutoCount sync state to
     * NOT SYNCED — its contents changed, so any previous push is stale and it must be
     * re-synced before the desktop plugin can pick it up again.
     */
    public function mergeConvert(Request $request)
    {
        $ids = $request->input('ids', []);
        $invoiceId = $request->input('invoice_id');

        if (empty($ids)) {
            return response()->json(['message' => 'Please select at least one delivery order.'], 422);
        }

        if (empty($invoiceId)) {
            return response()->json(['message' => 'Please select an invoice to merge into.'], 422);
        }

        $invoice = Invoice::find($invoiceId);

        if (empty($invoice)) {
            return response()->json(['message' => 'Selected invoice not found.'], 422);
        }

        $deliveryOrders = DeliveryOrder::with('deliveryorderdetail')
            ->whereIn('id', $ids)
            ->whereNull('invoice_id')
            ->get();

        if ($deliveryOrders->isEmpty()) {
            return response()->json(['message' => 'Selected delivery order(s) are already converted or not found.'], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($deliveryOrders as $deliveryOrder) {
                foreach ($deliveryOrder->deliveryorderdetail as $line) {
                    $detail = new InvoiceDetail();
                    $detail->invoice_id = $invoice->id;
                    $detail->product_id = $line->product_id;
                    $detail->deliveryorder_id = $deliveryOrder->id;
                    $detail->quantity = $line->quantity;
                    $detail->price = $line->price;
                    $detail->totalprice = $line->totalprice;
                    $detail->remark = $line->remark;
                    $detail->save();
                }

                $deliveryOrder->invoice_id = $invoice->id;
                $deliveryOrder->save();
            }

            // Contents changed → any earlier AutoCount push is stale. Reset to NOT SYNCED and
            // clear the previous sync metadata so it re-syncs cleanly next time it is queued.
            $invoice->autocount_status = Invoice::AUTOCOUNT_NOT_SYNCED;
            $invoice->autocount_docno = null;
            $invoice->autocount_error = null;
            $invoice->autocount_synced_at = null;
            $invoice->save();

            DB::commit();

            return response()->json([
                'message' => $deliveryOrders->count() . ' delivery order(s) merged into invoice ' . $invoice->invoiceno . '.',
                'count' => $deliveryOrders->count(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(['message' => 'Something went wrong. Please contact administrator.'], 500);
        }
    }

    /**
     * Search existing invoices for the "Merge to Invoice" picker (select2 AJAX source).
     * Voided invoices are excluded — you cannot merge into a cancelled document.
     */
    public function mergeInvoices(Request $request)
    {
        $search = trim($request->input('q', ''));

        $invoices = Invoice::with('customer')
            ->where('status', '!=', Invoice::STATUS_VOIDED)
            ->when($search !== '', function ($query) use ($search) {
                $query->where('invoiceno', 'like', '%' . $search . '%');
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $results = $invoices->map(function ($invoice) {
            $company = optional($invoice->customer)->company;
            $date = $invoice->getRawOriginal('date');

            return [
                'id' => $invoice->id,
                'text' => $invoice->invoiceno
                    . ($company ? ' — ' . $company : '')
                    . ($date ? ' (' . $date . ')' : ''),
            ];
        });

        return response()->json(['results' => $results]);
    }
}
