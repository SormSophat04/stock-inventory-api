<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReturnOrderController extends Controller
{
    use \App\Traits\ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $returns = \App\Models\ReturnOrder::with(['items.product', 'customer', 'warehouse', 'sale', 'creator'])
            ->orderBy('return_id', 'desc')
            ->get();
        return $this->success($returns, 'Return orders retrieved successfully.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(\App\Http\Requests\StoreReturnOrderRequest $request)
    {
        $validated = $request->validated();
        $user = \Illuminate\Support\Facades\Auth::user();

        // Lookup Sale Reference
        $sale = \App\Models\Sale::where('invoice_no', $validated['sale_ref'])->first();
        $saleId = $sale ? $sale->sale_id : null;

        // Generate ID if not provided (though request doesn't ask for it, we generate it)
        $returnNo = 'RET-' . strtoupper(uniqid()); 

        try {
            return \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $user, $saleId, $returnNo) {
                // Create Return Order
                $returnOrder = \App\Models\ReturnOrder::create([
                    'return_no'     => $returnNo,
                    'sale_id'       => $saleId,
                    'customer_id'   => $validated['customer_id'],
                    'warehouse_id'  => $validated['warehouse_id'],
                    'return_date'   => $validated['return_date'] ?? now(),
                    'total_refund'  => $validated['items'] ? collect($validated['items'])->sum(function ($item) {
                        return $item['quantity'] * $item['price'];
                    }) : 0,
                    'status'        => $validated['status'],
                    'reason'        => $validated['reason'],
                    'refund_type'   => $validated['refund_type'],
                    'created_by'    => $user->user_id,
                ]);

                // Create Items and Update Stock
                foreach ($validated['items'] as $itemData) {
                    $itemTotal = $itemData['quantity'] * $itemData['price'];
                    
                    \App\Models\ReturnItem::create([
                        'return_id'  => $returnOrder->return_id,
                        'product_id' => $itemData['product_id'],
                        'quantity'   => $itemData['quantity'],
                        'price'      => $itemData['price'],
                        'subtotal'   => $itemTotal,
                    ]);

                    // If Confirmed, Update Stock (Increase)
                    if ($validated['status'] === 'Confirmed') {
                        $stock = \App\Models\Stock::firstOrCreate(
                            ['warehouse_id' => $validated['warehouse_id'], 'product_id' => $itemData['product_id']],
                            ['quantity' => 0]
                        );
                        $stock->increment('quantity', $itemData['quantity']);

                        // Log Movement
                        \App\Models\StockMovement::create([
                            'product_id'   => $itemData['product_id'],
                            'warehouse_id' => $validated['warehouse_id'],
                            'type'         => 'IN',
                            'reference_no' => $returnNo,
                            'quantity'     => $itemData['quantity'],
                            'note'         => 'Return Order: ' . $returnNo,
                            'created_by'   => $user->user_id,
                        ]);
                    }
                }

                $returnOrder->load(['items.product', 'customer', 'warehouse']);
                return $this->success($returnOrder, 'Return order created successfully.', 201);
            });
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $returnOrder = \App\Models\ReturnOrder::with(['items.product', 'customer', 'warehouse', 'sale'])->find($id);

        if (!$returnOrder) {
            return $this->error('Return order not found.', 404);
        }

        return $this->success($returnOrder, 'Return order retrieved successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $returnOrder = \App\Models\ReturnOrder::find($id);

        if (!$returnOrder) {
            return $this->error('Return order not found.', 404);
        }

        // Ideally preventing delete if Confirmed or reversing stock. 
        // For simplicity allow delete but warn or restrict. 
        // Let's restrict for now if Confirmed.
        if ($returnOrder->status === 'Confirmed') {
             return $this->error('Cannot delete a confirmed return order. Void it instead (not implemented).', 400);
        }

        $returnOrder->delete();
        return $this->success([], 'Return order deleted successfully.');
    }
}
