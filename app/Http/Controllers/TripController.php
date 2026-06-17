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
use Carbon\Carbon;
use App\Models\Trip;
use App\Models\Invoice;
use App\Models\Company;
use App\Models\Customer;
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
        $startTime = $startTrip ? Carbon::parse($startTrip->getRawOriginal('date') ?? $startTrip->date) : null;
        $endTime   = Carbon::parse($endTrip->getRawOriginal('date') ?? $endTrip->date);
        $duration  = $startTime ? $startTime->diff($endTime) : null;

        // Company info
        $company = Company::find(app()->bound('current_company_id') ? app('current_company_id') : null);

        // ── Stock Movement Table ───────────────────────────────────────────────
        $lorryId       = $startTrip ? $startTrip->lorry_id : $endTrip->lorry_id;
        $tripStartDate = $startTime ? $startTime->toDateTimeString() : null;
        $tripEndDate   = $endTime->toDateTimeString();

        $openingMap = collect($startTrip?->stock_snapshot ?? [])->keyBy('product_id');
        $closingMap = collect($endTrip->stock_snapshot   ?? [])->keyBy('product_id');

        $txBase = \App\Models\InventoryTransaction::where('lorry_id', $lorryId)
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

        $allProductIds = collect($openingMap->keys())
            ->merge($closingMap->keys())
            ->merge($adminInMap->keys())
            ->merge($adminOutMap->keys())
            ->merge($wastageMap->keys())
            ->merge(array_keys($salesMap))
            ->unique()->values();

        $productNames = \App\Models\Product::whereIn('id', $allProductIds)->pluck('name', 'id');

        $stockMovements = $allProductIds->map(function ($pid) use ($openingMap, $closingMap, $adminInMap, $adminOutMap, $wastageMap, $salesMap, $productNames) {
            return [
                'product_name'  => $openingMap[$pid]['product_name'] ?? $closingMap[$pid]['product_name'] ?? ($productNames[$pid] ?? '-'),
                'opening_stock' => (int) ($openingMap[$pid]['quantity'] ?? 0),
                'admin_in'      => (int) ($adminInMap[$pid] ?? 0),
                'admin_out'     => (int) ($adminOutMap[$pid] ?? 0),
                'sales_used'    => (int) ($salesMap[$pid] ?? 0),
                'wastage'       => (int) ($wastageMap[$pid] ?? 0),
                'closing_stock' => (int) ($closingMap[$pid]['quantity'] ?? 0),
            ];
        })->sortBy('product_name')->values();
        // ──────────────────────────────────────────────────────────────────────

        $pdf = Pdf::loadView('trips.report', compact(
            'startTrip', 'endTrip', 'invoices',
            'breakdown', 'grandTotal', 'paymentLabels',
            'startTime', 'endTime', 'duration', 'company',
            'stockMovements'
        ))->setPaper('a4', 'portrait');

        return $pdf->stream('daily-sales-report-' . $endTrip->id . '.pdf');
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
