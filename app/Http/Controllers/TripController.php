<?php

namespace App\Http\Controllers;

use App\DataTables\TripDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateTripRequest;
use App\Http\Requests\UpdateTripRequest;
use App\Repositories\TripRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Trip;
use App\Models\Invoice;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\InventoryBalance;
use App\Models\InventoryTransaction;
use App\Models\TripAnekaQuantity;
use Barryvdh\DomPDF\Facade\Pdf;

class TripController extends AppBaseController
{
    /** @var TripRepository $tripRepository*/
    private $tripRepository;

    public function __construct(TripRepository $tripRepo)
    {
        $this->tripRepository = $tripRepo;
    }

    /**
     * Display a listing of the Trip.
     *
     * @param TripDataTable $tripDataTable
     *
     * @return Response
     */
    public function index(TripDataTable $tripDataTable)
    {
        return $tripDataTable->render('trips.index');
    }

    /**
     * Show the form for creating a new Trip.
     *
     * @return Response
     */
    public function create()
    {
        return view('trips.create');
    }

    /**
     * Store a newly created Trip in storage.
     *
     * @param CreateTripRequest $request
     *
     * @return Response
     */
    public function store(CreateTripRequest $request)
    {
        $input = $request->all();

        $trip = $this->tripRepository->create($input);

        Flash::success('Trip saved successfully.');

        return redirect(route('trips.index'));
    }

    /**
     * Display the specified Trip.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $trip = $this->tripRepository->find($id);

        if (empty($trip)) {
            Flash::error(__('trips.trip_not_found'));

            return redirect(route('trip.index'));
        }

        $data = [
            'date' => Carbon::parse($trip->date)->toDateString()
        ];
        
        $sales = DB::Select('select sum(a.totalprice) as sales from(select i.id,sum(id.totalprice) as totalprice from invoices i left join invoice_details id on id.invoice_id = i.id where i.status = 1 and DATE(i.date) = "'.$data['date'].'" and i.driver_id = '.$trip->driver_id  .' group by i.id) a')[0]->sales;
            $cash = DB::Select('select coalesce(sum(coalesce(amount,0)),0) as cash from invoice_payments where type = \'cash\' and status = 1 and driver_id = '.$trip->driver_id  .' and approve_at >= "'.$data['date'].'" and approve_at < "'.date('Y-m-d', strtotime("+1 day", strtotime($data['date']))).'";')[0]->cash;
            $bank_in = DB::Select('select coalesce(sum(coalesce(bank_in,0)),0) as bank_in from trips where type = 2 and driver_id = '.$trip->driver_id  .' and created_at >= "'.$data['date'].'" and created_at < "'.date('Y-m-d', strtotime("+1 day", strtotime($data['date']))).'";')[0]->bank_in;
            $cash_left = DB::Select('select coalesce(sum(coalesce(cash,0)),0) as cash from trips where type = 2 and driver_id = '.$trip->driver_id  .' and created_at >= "'.$data['date'].'" and created_at < "'.date('Y-m-d', strtotime("+1 day", strtotime($data['date']))).'";')[0]->cash;
            // $credit = DB::select('select sum(a.totalprice) as credit from ( select i.id,sum(id.totalprice) as totalprice from invoices i left join invoice_details id on id.invoice_id = i.id left join invoice_payments ip on ip.invoice_id = i.id where i.status = 1 and i.date = "'.$data['date'].'" and i.driver_id = '.$driver->id.' and ip.id is null group by i.id ) a')[0]->credit;
            $credit = DB::select('select sum(a.totalprice) as credit from ( select i.id, sum(id.totalprice) as totalprice from invoices i left join invoice_details id on id.invoice_id = i.id where i.status = 1 and DATE(i.date) = "'.$data['date'].'" and i.driver_id = '.$trip->driver_id  .' and i.paymentterm = 2 group by i.id ) a')[0]->credit;
            $bank = DB::select('select sum(a.totalprice) as bank from ( select i.id, sum(id.totalprice) as totalprice from invoices i left join invoice_details id on id.invoice_id = i.id where i.status = 1 and DATE(i.date) = "'.$data['date'].'" and i.driver_id = '.$trip->driver_id  .' and i.paymentterm = 3 group by i.id ) a')[0]->bank;
            $tng = DB::select('select sum(a.totalprice) as tng from ( select i.id, sum(id.totalprice) as totalprice from invoices i left join invoice_details id on id.invoice_id = i.id where i.status = 1 and DATE(i.date) = "'.$data['date'].'" and i.driver_id = '.$trip->driver_id  .' and i.paymentterm = 4 group by i.id ) a')[0]->tng;
            $productsold = DB::Select('select sum(id.quantity) as productsold from invoices i left join invoice_details id on id.invoice_id = i.id where i.status = 1 and id.totalprice > 0 and DATE(i.date) = "'.$data['date'].'" and i.driver_id = '.$trip->driver_id  )[0]->productsold;
            $solddetail = DB::select('select p.name, sum(id.quantity) as quantity, sum(id.totalprice) as price from invoices i left join invoice_details id on id.invoice_id = i.id  left join products p on p.id = id.product_id where i.status = 1 and id.totalprice > 0 and DATE(i.date) = "'.$data['date'].'" and i.driver_id = '.$trip->driver_id  .' group by id.product_id, p.id, p.name');
            $productfoc = DB::Select('select sum(id.quantity) as productsold from invoices i left join invoice_details id on id.invoice_id = i.id where i.status = 1 and id.totalprice = 0 and DATE(i.date) = "'.$data['date'].'" and i.driver_id = '.$trip->driver_id  )[0]->productsold;
            $focdetail = DB::select('select p.name, sum(id.quantity) as quantity, sum(id.totalprice) as price from invoices i left join invoice_details id on id.invoice_id = i.id left join products p on p.id = id.product_id where i.status = 1 and id.totalprice = 0  and DATE(i.date) = "'.$data['date'].'" and i.driver_id = '.$trip->driver_id  .' group by id.product_id, p.id, p.name');
            $tripList = DB::select('select t.id, d.name as driver_name, k.name as kelindan_name, l.lorryno from trips t left join drivers d on d.id = t.driver_id left join kelindans k on k.id = t.kelindan_id left join lorrys l on l.id = t.lorry_id where t.driver_id = '.$trip->driver_id  .' and t.type = 1 and t.date >= "'.$data['date'].'" and t.date < "'.$data['date'].' 23:59:59"');
            
            $transaction = DB::table('inventory_transactions as i_t')
            ->join('products as p', 'p.id', '=', 'i_t.product_id')
            ->join('drivers as d', function($join) use ($trip) {
                $join->where('d.id', '=', $trip->id)
                    ->where(DB::raw("SUBSTRING_INDEX(i_t.user, ' ', 1)"), '=', DB::raw('d.employeeid'))
                    ->where(DB::raw("REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(i_t.user, '(', -1), ')', 1), ')', '')"), '=', DB::raw('d.name'));
            })
            ->where('i_t.type', 5)
            ->where('i_t.created_at', '>=', $data['date'] . ' 00:00:00')
            ->where('i_t.created_at', '<', $data['date'] . ' 23:59:59')
            ->select('p.name', 'i_t.quantity')
            ->get();

            // $trip = Trip::where('driver_id', $driver->id)
            // ->where('date','>=',$data['date'].' 00:00:00')
            // ->where('date','<',$data['date'].' 23:59:59')
            // ->where('type',1) 
            // ->with('driver')
            // ->with('kelindan')
            // ->with('lorry')
            // ->get()
            // ->toArray();
            $result = [
                'sales' => round($sales,2),
                'cash' => round($cash,2),
                'cash_left' =>  ceil($cash_left),
                'bank_in' => round($bank_in,2),
                'wastage' => $transaction,
                'credit' => round($credit,2),
                'onlinebank' =>round($bank,2),
                'tng' =>round($tng,2),
                'productsold' => [
                    'total_quantity' =>round($productsold,2),
                    'details' =>$solddetail
                ],
                'productfoc' => [
                    'total_quantity' =>round($productfoc,2),
                    'details' =>$focdetail
                ],
                'trip' => $tripList
            ];
        return view('trips.show')->with('trip', (object)$result);
    }

    public function report($id)
    {
        $id = Crypt::decrypt($id);

        // The clicked row is the end trip (type=2)
        $endTrip = Trip::with(['driver', 'kelindan', 'lorry'])->findOrFail($id);

        $tripData  = $this->buildStockMovementData($endTrip);
        $startTrip = $tripData['startTrip'];
        $invoices  = $tripData['invoices'];
        $startTime = $tripData['startTime'];
        $endTime   = $tripData['endTime'];
        $stockMovements = $tripData['stockMovements'];

        // Payment term labels
        $paymentLabels = \App\Models\Customer::PAYMENT_TERMS;

        // Aggregate payment breakdown
        $breakdown = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($invoices as $invoice) {
            $term = (int) $invoice->paymentterm;
            if (array_key_exists($term, $breakdown)) {
                $breakdown[$term] += $invoice->invoicedetail->sum('totalprice');
            }
        }
        $grandTotal = array_sum($breakdown);

        // Trip duration
        $duration = $startTime ? $startTime->diff($endTime) : null;

        // Company info
        $company = Company::find(app()->bound('current_company_id') ? app('current_company_id') : null);

        $pdf = Pdf::loadView('trips.report', compact(
            'startTrip', 'endTrip', 'invoices',
            'breakdown', 'grandTotal', 'paymentLabels',
            'startTime', 'endTime', 'duration', 'company',
            'stockMovements'
        ))->setPaper('a4', 'portrait');

        return $pdf->stream('daily-sales-report-' . $endTrip->id . '.pdf');
    }

    /**
     * Build the stock movement dataset (opening/admin/sales/wastage/aneka/closing)
     * for an end trip. Shared by the PDF report and the ANEKA entry modal so both
     * always show the exact same product list and figures.
     */
    private function buildStockMovementData(Trip $endTrip): array
    {
        // Find the corresponding start trip (type=1) for this driver immediately before the end trip
        $startTrip = Trip::where('driver_id', $endTrip->driver_id)
            ->where('type', 1)
            ->where('id', '<', $endTrip->id)
            ->orderBy('id', 'desc')
            ->first();

        // Get all invoices created during this trip
        $invoices = collect();
        if ($startTrip) {
            $invoices = Invoice::where('trip_id', $startTrip->id)
                ->where('status', 1)
                ->with(['customer', 'invoicedetail.product'])
                ->get();
        }

        $startTime = $startTrip ? Carbon::parse($startTrip->getRawOriginal('date') ?? $startTrip->date) : null;
        $endTime   = Carbon::parse($endTrip->getRawOriginal('date') ?? $endTrip->date);

        $lorryId       = $startTrip ? $startTrip->lorry_id : $endTrip->lorry_id;
        $tripStartDate = $startTime ? $startTime->toDateTimeString() : null;
        $tripEndDate   = $endTime->toDateTimeString();

        $openingMap = collect($startTrip?->stock_snapshot ?? [])->keyBy('product_id');
        $closingMap = collect($endTrip->stock_snapshot   ?? [])->keyBy('product_id');

        $txBase = InventoryTransaction::where('lorry_id', $lorryId)
            ->when($tripStartDate, fn($q) => $q->where('date', '>=', $tripStartDate))
            ->where('date', '<=', $tripEndDate);

        $adminInMap  = (clone $txBase)->where('type', 1)
            ->selectRaw('product_id, SUM(quantity) as total')->groupBy('product_id')
            ->pluck('total', 'product_id');

        $adminOutMap = (clone $txBase)->where('type', 2)
            ->selectRaw('product_id, SUM(ABS(quantity)) as total')->groupBy('product_id')
            ->pluck('total', 'product_id');

        $wastageMap  = (clone $txBase)->where('type', 5)
            ->selectRaw('product_id, SUM(ABS(quantity)) as total')->groupBy('product_id')
            ->pluck('total', 'product_id');

        $salesMap = [];
        foreach ($invoices as $invoice) {
            foreach ($invoice->invoicedetail as $detail) {
                if ($detail->totalprice > 0 && $detail->product_id) {
                    $salesMap[$detail->product_id] = ($salesMap[$detail->product_id] ?? 0) + $detail->quantity;
                }
            }
        }

        $anekaMap = TripAnekaQuantity::where('trip_id', $endTrip->id)->pluck('quantity', 'product_id');

        $allProductIds = collect($openingMap->keys())
            ->merge($closingMap->keys())
            ->merge($adminInMap->keys())
            ->merge($adminOutMap->keys())
            ->merge($wastageMap->keys())
            ->merge(array_keys($salesMap))
            ->merge($anekaMap->keys())
            ->unique()->values();

        $productNames = Product::whereIn('id', $allProductIds)->pluck('name', 'id');

        $stockMovements = $allProductIds->map(function ($pid) use ($openingMap, $closingMap, $adminInMap, $adminOutMap, $wastageMap, $salesMap, $anekaMap, $productNames) {
            return [
                'product_id'    => (int) $pid,
                'product_name'  => $openingMap[$pid]['product_name'] ?? $closingMap[$pid]['product_name'] ?? ($productNames[$pid] ?? '-'),
                'opening_stock' => (int) ($openingMap[$pid]['quantity'] ?? 0),
                'admin_in'      => (int) ($adminInMap[$pid] ?? 0),
                'admin_out'     => (int) ($adminOutMap[$pid] ?? 0),
                'sales_used'    => (int) ($salesMap[$pid] ?? 0),
                'wastage'       => (int) ($wastageMap[$pid] ?? 0),
                'aneka'         => (int) ($anekaMap[$pid] ?? 0),
                'closing_stock' => (int) ($closingMap[$pid]['quantity'] ?? 0),
            ];
        })->sortBy('product_name')->values();

        return [
            'startTrip'      => $startTrip,
            'invoices'       => $invoices,
            'startTime'      => $startTime,
            'endTime'        => $endTime,
            'lorryId'        => $lorryId,
            'stockMovements' => $stockMovements,
        ];
    }

    /**
     * Return the product list + current ANEKA quantities for a trip, for the
     * admin's ANEKA entry modal (opened from the trip datatable's Report button).
     */
    public function anekaForm($id)
    {
        $id = Crypt::decrypt($id);
        $endTrip = Trip::with('lorry')->findOrFail($id);

        $tripData = $this->buildStockMovementData($endTrip);

        return response()->json([
            'lorry' => $endTrip->lorry?->lorryno ?? '-',
            'products' => $tripData['stockMovements']->map(function ($row) {
                return [
                    'product_id'    => $row['product_id'],
                    'product_name'  => $row['product_name'],
                    'opening_stock' => $row['opening_stock'],
                    'sales_used'    => $row['sales_used'],
                    'wastage'       => $row['wastage'],
                    'aneka'         => $row['aneka'],
                ];
            })->values(),
        ]);
    }

    /**
     * Save the admin-entered ANEKA quantities for a trip. Only the delta between
     * the previously saved quantity and the new quantity is applied to the
     * driver's lorry inventory balance, so editing a value down returns stock,
     * and editing it up deducts more.
     */
    public function saveAneka($id, Request $request)
    {
        $id = Crypt::decrypt($id);
        $endTrip = Trip::findOrFail($id);

        $validated = $request->validate([
            'products'               => 'present|array',
            'products.*.product_id'  => 'required|integer|exists:products,id',
            'products.*.quantity'    => 'required|integer|min:0',
        ]);

        $lorryId   = $endTrip->lorry_id;
        $companyId = app()->bound('current_company_id') ? app('current_company_id') : $endTrip->company_id;

        DB::beginTransaction();
        try {
            foreach ($validated['products'] as $row) {
                $productId = $row['product_id'];
                $newQty    = (int) $row['quantity'];

                $existing = TripAnekaQuantity::where('trip_id', $endTrip->id)
                    ->where('product_id', $productId)
                    ->first();
                $oldQty = $existing->quantity ?? 0;
                $delta  = $newQty - $oldQty;

                if ($delta !== 0) {
                    $balance = InventoryBalance::where('lorry_id', $lorryId)
                        ->where('product_id', $productId)
                        ->first();
                    if (empty($balance)) {
                        // No record yet — create with negative quantity (negative stock allowed)
                        $balance = new InventoryBalance();
                        $balance->lorry_id = $lorryId;
                        $balance->product_id = $productId;
                        $balance->quantity = 0 - $delta;
                    } else {
                        $balance->quantity = $balance->quantity - $delta;
                    }
                    $balance->save();

                    $transaction = new InventoryTransaction();
                    $transaction->lorry_id = $lorryId;
                    $transaction->product_id = $productId;
                    $transaction->quantity = $delta * -1;
                    $transaction->type = 6; // ANEKA adjustment
                    $transaction->date = date('Y-m-d H:i:s');
                    $transaction->user = Auth::user()->email ?? 'admin';
                    $transaction->save();
                }

                TripAnekaQuantity::updateOrCreate(
                    ['trip_id' => $endTrip->id, 'product_id' => $productId],
                    ['quantity' => $newQty, 'company_id' => $companyId]
                );
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 500);
        }

        return response()->json([
            'message'    => 'ANEKA quantities saved successfully.',
            'report_url' => route('trips.report', Crypt::encrypt($endTrip->id)),
        ]);
    }

    /**
     * Show the form for editing the specified Trip.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $trip = $this->tripRepository->find($id);

        if (empty($trip)) {
            Flash::error(__('trips.trip_not_found'));

            return redirect(route('trips.index'));
        }

        return view('trips.edit')->with('trip', $trip);
    }

    /**
     * Update the specified Trip in storage.
     *
     * @param int $id
     * @param UpdateTripRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateTripRequest $request)
    {
        $trip = $this->tripRepository->find($id);

        if (empty($trip)) {
            Flash::error(__('trips.trip_not_found'));

            return redirect(route('trips.index'));
        }

        $trip = $this->tripRepository->update($request->all(), $id);

        Flash::success('Trip updated successfully.');

        return redirect(route('trips.index'));
    }

    /**
     * Remove the specified Trip from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $trip = $this->tripRepository->find($id);

        if (empty($trip)) {
            Flash::error(__('trips.trip_not_found'));

            return redirect(route('trips.index'));
        }

        $this->tripRepository->delete($id);

        Flash::success('Trip deleted successfully.');

        return redirect(route('trips.index'));
    }
}
