<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StockCountController extends Controller
{
    use \App\Traits\ApiResponse;

    /**
     * Store a new stock count (batch adjustments).
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return $this->error('Unauthenticated', 401);
        }

        if (!in_array($user->role, ['admin', 'manager', 'warehouse_staff'])) {
            return $this->error('Unauthorized', 403);
        }

        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|integer|exists:warehouses,warehouse_id',
            'date' => 'required|date',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,product_id',
            'items.*.counted_qty' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors()->toArray());
        }

        try {
            $result = DB::transaction(function () use ($request, $user) {
                $warehouse_id = $request->warehouse_id;
                $reference = $request->reference_no ?? null;
                $notes = $request->notes ?? null;

                $createdAdjustments = [];

                foreach ($request->items as $item) {
                    $productId = $item['product_id'];
                    $counted = (int) $item['counted_qty'];

                    // Get existing aggregated stock record (or firstOrNew)
                    $stock = Stock::firstOrNew([
                        'warehouse_id' => $warehouse_id,
                        'product_id' => $productId,
                    ]);

                    $old_qty = $stock->quantity ?? 0;

                    // If no change, still record difference = 0? We'll skip creating adjustments for zero diff
                    if ($counted === (int) $old_qty) {
                        continue;
                    }

                    // 1. Create stock adjustment
                    $adjustment = StockAdjustment::create([
                        'warehouse_id' => $warehouse_id,
                        'product_id' => $productId,
                        'old_qty' => $old_qty,
                        'new_qty' => $counted,
                        'reason' => $notes ?? 'Stock Count Adjustment',
                        'created_by' => $user->user_id,
                    ]);

                    // 2. Update aggregated stock quantity
                    $stock->quantity = $counted;
                    $stock->warehouse_id = $warehouse_id;
                    $stock->product_id = $productId;
                    $stock->save();

                    // 3. Record a stock movement for traceability
                    $difference = $counted - $old_qty;
                    StockMovement::create([
                        'product_id' => $productId,
                        'warehouse_id' => $warehouse_id,
                        'type' => 'count_adjust',
                        'reference_no' => $reference,
                        'quantity' => $difference,
                        'note' => $notes ?? 'Adjustment from stock count',
                        'created_by' => $user->user_id,
                    ]);

                    $adjustment->load(['warehouse', 'product', 'user']);
                    $createdAdjustments[] = $adjustment;
                }

                return $this->success([
                    'message' => 'Stock count processed',
                    'adjustments_created' => count($createdAdjustments),
                    'adjustments' => $createdAdjustments,
                ], 'Stock count processed successfully', 201);
            });

            return $result;
        } catch (\Exception $e) {
            return $this->error('An error occurred while processing stock count: ' . $e->getMessage(), 500);
        }
    }
}
