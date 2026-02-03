<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\TransferItem;
use App\Models\Warehouse;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StockTransferController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $transfers = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'items.product', 'user'])
            ->orderBy('transfer_id', 'desc')
            ->get();
        return $this->success($transfers);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['admin', 'manager', 'warehouse_staff'])) {
            return $this->error('Unauthorized', 403);
        }

        $validator = Validator::make($request->all(), [
            'from_warehouse_id' => 'required|exists:warehouses,warehouse_id',
            'to_warehouse_id'   => 'required|exists:warehouses,warehouse_id',
            'transfer_date'     => 'required|date',
            'note'              => 'nullable|string',
            'items'             => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,product_id',
            'items.*.quantity'  => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        // Check from_warehouse != to_warehouse
        if ($request->from_warehouse_id == $request->to_warehouse_id) {
            return $this->error('Source and destination warehouses must be different', 422);
        }

        try {
            $transfer = DB::transaction(function () use ($request, $user) {
                // 1. Stock Check
                foreach ($request->items as $item) {
                    $stock = Stock::where('warehouse_id', $request->from_warehouse_id)
                        ->where('product_id', $item['product_id'])
                        ->first();

                    if (!$stock || $stock->quantity < $item['quantity']) {
                        throw new \Exception("Insufficient stock for product ID {$item['product_id']}");
                    }
                }

                // 2. Create Transfer
                $transfer = StockTransfer::create([
                    'from_warehouse'    => $request->from_warehouse_id,
                    'to_warehouse'      => $request->to_warehouse_id,
                    'note'              => $request->note,
                    'transfer_date'     => $request->transfer_date,
                    'created_by'        => $user->user_id,
                ]);

                // 3. Process Items
                foreach ($request->items as $item) {
                    // Get source stock BEFORE decrementing
                    $sourceStock = Stock::where('warehouse_id', $request->from_warehouse_id)
                        ->where('product_id', $item['product_id'])
                        ->first();

                    // Decrement Source
                    $sourceStock->decrement('quantity', $item['quantity']);
                    
                    // Log Outgoing Movement
                    StockMovement::create([
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $request->from_warehouse_id,
                        'type' => 'TRANSFER_OUT',
                        'quantity' => $item['quantity'], // Negative logic can be handled in UI or Reporting, usually stored as absolute magnitude with type
                        'reference_no' => 'TRF-' . $transfer->transfer_id,
                        'note' => 'Transfer to Warehouse ' . $request->to_warehouse_id,
                        'created_by' => $user->user_id,
                    ]);

                    // Increment Destination
                    $destStock = Stock::where('warehouse_id', $request->to_warehouse_id)
                        ->where('product_id', $item['product_id'])
                        ->first();

                    if ($destStock) {
                        $destStock->increment('quantity', $item['quantity']);
                    } else {
                        Stock::create([
                            'warehouse_id' => $request->to_warehouse_id,
                            'product_id' => $item['product_id'],
                            'supplier_id' => $sourceStock->supplier_id, // Inherit supplier or default
                            'quantity' => $item['quantity'],
                            'type' => 'Transfer',
                            'unit_price' => $sourceStock->unit_price,
                            'created_by' => $user->user_id,
                        ]);
                    }

                    // Log Incoming Movement
                    StockMovement::create([
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $request->to_warehouse_id,
                        'type' => 'TRANSFER_IN',
                        'quantity' => $item['quantity'],
                        'reference_no' => 'TRF-' . $transfer->transfer_id,
                        'note' => 'Transfer from Warehouse ' . $request->from_warehouse_id,
                        'created_by' => $user->user_id,
                    ]);

                    // Create Item Record
                    $transfer->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity'   => $item['quantity']
                    ]);
                }

                return $transfer;
            });

            $transfer->load(['fromWarehouse', 'toWarehouse', 'items.product', 'user']);

            return $this->success($transfer, 'Stock transfer created successfully', 201);
            
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400); // 400 Bad Request if logic fails (like insufficient stock)
        }
    }
}
