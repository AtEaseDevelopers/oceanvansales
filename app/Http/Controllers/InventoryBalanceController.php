<?php

namespace App\Http\Controllers;

use App\DataTables\InventoryBalanceDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateInventoryBalanceRequest;
use App\Http\Requests\UpdateInventoryBalanceRequest;
use App\Repositories\InventoryBalanceRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use App\Models\InventoryBalance;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryBalanceController extends AppBaseController
{
    /** @var InventoryBalanceRepository $inventoryBalanceRepository*/
    private $inventoryBalanceRepository;

    public function __construct(InventoryBalanceRepository $inventoryBalanceRepo)
    {
        $this->inventoryBalanceRepository = $inventoryBalanceRepo;
    }

    /**
     * Display a listing of the InventoryBalance.
     *
     * @param InventoryBalanceDataTable $inventoryBalanceDataTable
     *
     * @return Response
     */
    public function index(InventoryBalanceDataTable $inventoryBalanceDataTable)
    {
        return $inventoryBalanceDataTable->render('inventory_balances.index');
    }
	public function stockin(Request $request)
	{
		$data = $request->all();
		$lorryIds = $data['lorry_id'];  // This will be an array of selected lorry IDs

		foreach ($lorryIds as $lorryId) {
			$inventoryBalance = InventoryBalance::where('product_id', $data['product_id'])
				->where('lorry_id', $lorryId)
				->first();

			if (!empty($inventoryBalance)) {
				// Update the existing inventory balance
				$inventoryBalance->quantity = $inventoryBalance->quantity + $data['quantity'];
				$inventoryBalance->save();

				// Create an inventory transaction record
				$inventoryTransaction = new InventoryTransaction();
				$inventoryTransaction->type = 1;
				$inventoryTransaction->lorry_id = $lorryId;
				$inventoryTransaction->product_id = $inventoryBalance->product_id;
				$inventoryTransaction->quantity = $data['quantity'];
				$inventoryTransaction->date = date("Y-m-d H:i:s");
				$inventoryTransaction->user = Auth::user()->email . ' (' . Auth::user()->name . ')';
				$inventoryTransaction->save();

				Flash::success('Inventory Balance for lorry ID ' . $lorryId . ' has been updated successfully.');
			} else {
				// Insert a new inventory balance
				$newInventoryBalance = new InventoryBalance();
				$newInventoryBalance->product_id = $data['product_id'];
				$newInventoryBalance->lorry_id = $lorryId;
				$newInventoryBalance->quantity = $data['quantity'];
				$newInventoryBalance->save();

				// Create an inventory transaction record
				$inventoryTransaction = new InventoryTransaction();
				$inventoryTransaction->type = 1;
				$inventoryTransaction->lorry_id = $lorryId;
				$inventoryTransaction->product_id = $data['product_id'];
				$inventoryTransaction->quantity = $data['quantity'];
				$inventoryTransaction->date = date("Y-m-d H:i:s");
				$inventoryTransaction->user = Auth::user()->email . ' (' . Auth::user()->name . ')';
				$inventoryTransaction->save();

				Flash::success('Inventory Balance for lorry ID ' . $lorryId . ' has been inserted successfully.');
			}
		}

		return redirect(route('inventoryBalances.index'));
	}

    public function getstock($lorry_id,$product_id)
    {
        // Support comma-separated lorry IDs (multi-lorry selection from frontend)
        $lorryIds = explode(',', $lorry_id);
        $totalQuantity = 0;
        foreach ($lorryIds as $lid) {
            $inventoryBalance = InventoryBalance::where('product_id',$product_id)->where('lorry_id',$lid)->first();
            if (!empty($inventoryBalance)) {
                $totalQuantity += $inventoryBalance->quantity;
            }
        }
        // Always return the real quantity (can be zero or negative)
        if ($totalQuantity > 0) {
            return response()->json(['status' => true, 'message' => 'Stock found!', 'quantity' => $totalQuantity]);
        } elseif ($totalQuantity == 0) {
            return response()->json(['status' => false, 'message' => 'No stock available.', 'quantity' => 0]);
        } else {
            return response()->json(['status' => 'negative', 'message' => 'Stock is negative!', 'quantity' => $totalQuantity]);
        }
    }

    public function stockout(Request $request)
    {
        $data = $request->all();
        $lorryIds = is_array($data['lorry_id']) ? $data['lorry_id'] : [$data['lorry_id']];

        foreach ($lorryIds as $lorryId) {
            $inventoryBalance = InventoryBalance::where('product_id',$data['product_id'])->where('lorry_id',$lorryId)->first();
            if (!empty($inventoryBalance)) {
                $newQty = $inventoryBalance->quantity - $data['quantity'];
                $inventoryBalance->quantity = $newQty;
                $inventoryBalance->save();
            } else {
                // No existing balance — create a negative record
                $inventoryBalance = new InventoryBalance();
                $inventoryBalance->lorry_id = $lorryId;
                $inventoryBalance->product_id = $data['product_id'];
                $inventoryBalance->quantity = 0 - $data['quantity'];
                $inventoryBalance->save();
                $newQty = $inventoryBalance->quantity;
            }

            $inventorytransaction = new InventoryTransaction();
            $inventorytransaction->type = 2;
            $inventorytransaction->lorry_id = $lorryId;
            $inventorytransaction->product_id = $data['product_id'];
            $inventorytransaction->quantity = $data['quantity'] * -1;
            $inventorytransaction->date = date("Y-m-d H:i:s");
            $inventorytransaction->user = Auth::user()->email . ' (' . Auth::user()->name . ')';
            $inventorytransaction->save();

            if ($newQty < 0) {
                Flash::warning('Stock Out recorded. Lorry ' . $lorryId . ' now has negative balance (' . $newQty . ').');
            } else {
                Flash::success('Inventory Balance updated successfully.');
            }
        }

        return redirect(route('inventoryBalances.index'));
    }
}
