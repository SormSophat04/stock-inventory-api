<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StockAdjustmentController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager'])) {
            return $this->error('Unauthorized', 403);
        }

        $adjustments = StockAdjustment::with(['warehouse', 'product', 'user'])
            ->orderBy('adjustment_id', 'desc')
            ->get();

        return $this->success($adjustments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager', 'warehouse_staff'])) {
            return $this->error('Unauthorized', 403);
        }

        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|integer|exists:warehouses,warehouse_id',
            'product_id' => 'required|integer|exists:products,product_id',
            'new_qty' => 'required|integer|min:0',
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        try {
            return DB::transaction(function () use ($request, $user) {
                $stock = Stock::firstOrNew([
                    'warehouse_id' => $request->warehouse_id,
                    'product_id' => $request->product_id,
                ]);

                $old_qty = $stock->quantity ?? 0;
                $new_qty = $request->new_qty;
                $diff = $new_qty - $old_qty;

                // 1. Create the adjustment record
                $adjustment = StockAdjustment::create([
                    'warehouse_id' => $request->warehouse_id,
                    'product_id' => $request->product_id,
                    'old_qty' => $old_qty,
                    'new_qty' => $new_qty,
                    'reason' => $request->reason ?? 'Stock Adjustment',
                    'created_by' => $user->user_id,
                ]);

                // 2. Update the stock quantity
                // If new stock, set supplier_id if mandatory, or handle nullable in DB. 
                // Stock::firstOrNew doesn't save yet.
                if (!$stock->exists) {
                     $stock->supplier_id = 1; // Default or null if allowed. Ideally should be handled.
                     $stock->created_by = $user->user_id;
                     $stock->unit_price = 0; // Default
                }

                $stock->quantity = $new_qty;
                $stock->save();

                // 3. Log Stock Movement
                if ($diff !== 0) {
                    StockMovement::create([
                        'product_id' => $request->product_id,
                        'warehouse_id' => $request->warehouse_id,
                        'type' => 'ADJUSTMENT',
                        'quantity' => $diff, // Positive or Negative
                        'reference_no' => 'ADJ-' . $adjustment->adjustment_id,
                        'note' => $request->reason ?? 'Manual Adjustment',
                        'created_by' => $user->user_id,
                    ]);
                }

                $adjustment->load(['warehouse', 'product', 'user']);

                return $this->success($adjustment, 'Stock adjustment created successfully', 201);
            });
        } catch (\Exception $e) {
            return $this->error('An error occurred during the stock adjustment: ' . $e->getMessage(), 500);
        }
    }
}
