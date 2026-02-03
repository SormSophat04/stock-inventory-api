<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Purchase;
use App\Models\Stock;
use App\Models\PurchaseItems;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Traits\ApiResponse;

class PurchaseController extends Controller
{
    use ApiResponse;

    // List all purchases
    public function index()
    {
        $purchases = Purchase::with(['items.product', 'supplier', 'warehouse'])
            ->orderBy('purchase_id', 'desc')
            ->get();

        return $this->success($purchases);
    }

    // Create a new purchase (Status: Pending)
    public function store(Request $request)
    {
        // 1. Validation
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'warehouse_id' => 'required|exists:warehouses,warehouse_id',
            'invoice_no' => 'required|string',
            'purchase_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,product_id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.cost_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
             return $this->error('Validation Error', 422, $validator->errors()->toArray());
        }

        try {
            DB::beginTransaction();

            $userId = Auth::id() ? Auth::id() : 1;

            // Calculate Total
            $total_amount = 0;
            foreach ($request->items as $item) {
                $total_amount += $item['quantity'] * $item['cost_price'];
            }

            // 2. Create Purchase
            $purchase = Purchase::create([
                'supplier_id' => $request->supplier_id,
                'warehouse_id' => $request->warehouse_id,
                'invoice_no' => $request->invoice_no,
                'purchase_date' => $request->purchase_date,
                'total_amount' => $total_amount,
                'payment_status' => 'Unpaid', // Default to Unpaid as per DB Enum
                'created_by' => $userId
            ]);

            // 3. Create Items
            foreach ($request->items as $item) {
                PurchaseItems::create([
                    'purchase_id' => $purchase->purchase_id,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'cost_price'  => $item['cost_price'],
                    'subtotal'    => $item['quantity'] * $item['cost_price']
                ]);
            }

            DB::commit();
            
            $newPurchase = Purchase::with(['items.product', 'supplier', 'warehouse'])->find($purchase->purchase_id);

            return $this->success($newPurchase, 'Purchase recorded successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Purchase Creation Failed: " . $e->getMessage());
            return $this->error('Database Error: ' . $e->getMessage(), 500);
        }
    }

    // Show a specific purchase
    public function show($id)
    {
        $purchase = Purchase::with(['items.product', 'supplier', 'warehouse'])->find($id);
        if (!$purchase) return $this->error('Purchase not found', 404);
        return $this->success($purchase);
    }

    // Update a purchase
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'warehouse_id' => 'required|exists:warehouses,warehouse_id',
            'invoice_no' => 'required|string',
            'purchase_date' => 'required|date',
            'items' => 'required|array|min:1',
             'items.*.product_id' => 'required|exists:products,product_id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.cost_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors()->toArray());
        }

        try {
            DB::beginTransaction();

            $purchase = Purchase::find($id);
            if (!$purchase) return $this->error('Purchase not found', 404);

            // --- Prevent editing if already paid/received ---
            if ($purchase->payment_status === 'Received' || $purchase->payment_status === 'Paid') {
                return $this->error('Cannot edit a received/paid order.', 403);
            }

            $total_amount = 0;
            foreach ($request->items as $item) {
                $total_amount += $item['quantity'] * $item['cost_price'];
            }

            $purchase->update([
                'supplier_id' => $request->supplier_id,
                'warehouse_id' => $request->warehouse_id,
                'invoice_no' => $request->invoice_no,
                'purchase_date' => $request->purchase_date,
                'total_amount' => $total_amount,
            ]);

            // --- Sync Items (Delete old, add new) ---
            $purchase->items()->delete();

            foreach ($request->items as $item) {
                PurchaseItems::create([
                    'purchase_id' => $purchase->purchase_id,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'cost_price'  => $item['cost_price'],
                    'subtotal'    => $item['quantity'] * $item['cost_price']
                ]);
            }

            DB::commit();

            $updatedPurchase = Purchase::with(['items.product', 'supplier', 'warehouse'])->find($purchase->purchase_id);

            return $this->success($updatedPurchase, 'Purchase updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Database Error on update: ' . $e->getMessage(), 500);
        }
    }

    // Receive the Order (Update Status & Add Stock)
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Paid,Received,Unpaid' // Allow flexibility
        ]);

        if ($validator->fails()) {
             return $this->error('Validation Error', 422, $validator->errors()->toArray());
        }

        $purchase = Purchase::with('items')->find($id);
        if (!$purchase) return $this->error('Purchase not found', 404);

        if ($purchase->payment_status === 'Received' || $purchase->payment_status === 'Paid') {
            return $this->error('Order is already processed', 400);
        }

        try {
            DB::beginTransaction();

            $userId = Auth::id() ? Auth::id() : 1;
            
            // 1. Update PO Status
            // Map 'Received' to 'Paid' because DB only supports Paid/Unpaid/Partial
            $status = $request->status;
            if ($status === 'Received') {
                $status = 'Paid';
            }
            $purchase->payment_status = $status;
            $purchase->save();

            // 2. Update Stock Levels ONLY if status implies stock entry (e.g. Received)
            // If Paid and Received are distinct steps, ensure logic matches. 
            // Assuming 'Received' adds stock.
            if ($status === 'Received' || $status === 'Paid') {
                foreach ($purchase->items as $item) {
                    $stock = Stock::firstOrCreate(
                        [
                            'product_id' => $item->product_id,
                            'warehouse_id' => $purchase->warehouse_id
                        ],
                        [
                            'quantity' => 0, 
                            'supplier_id' => $purchase->supplier_id,
                            'unit_price' => $item->cost_price,
                            'type' => 'Initial'
                        ]
                    );
                    $stock->increment('quantity', $item->quantity);
                    
                    // Log movement
                     StockMovement::create([
                        'product_id' => $item->product_id,
                        'warehouse_id' => $purchase->warehouse_id,
                        'type' => 'IN',
                        'quantity' => $item->quantity,
                        'reference_no' => $purchase->invoice_no,
                        'note' => "Purchase Order #{$purchase->purchase_id}",
                        'created_by' => $userId,
                    ]);
                }
            }

            DB::commit();
            
            // Re-fetch to return latest data
            $purchase->refresh();

            return $this->success($purchase, 'Order status updated and stock processed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to update status: ' . $e->getMessage(), 500);
        }
    }

    // Delete a purchase
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $purchase = Purchase::find($id);
            if (!$purchase) return $this->error('Purchase not found', 404);

            // --- Prevent deleting if already received ---
            if ($purchase->payment_status === 'Received' || $purchase->payment_status === 'Paid') {
                return $this->error('Cannot delete a processed order.', 403);
            }

            // Delete related items first
            $purchase->items()->delete();

            // Delete the purchase
            $purchase->delete();

            DB::commit();

            return $this->success([], 'Purchase deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Database Error on delete: ' . $e->getMessage(), 500);
        }
    }
}
