<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\StockTransfer;
use App\Models\TransferItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransferItemController extends Controller
{
    /**
     * Add new items to an existing stock transfer.
     * (Admin, Manager, Warehouse Staff)
     */
    public function store(Request $request, StockTransfer $transfer)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager', 'warehouse_staff'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,product_id', // Removed 'integer' to be safe, ensures ID exists
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Use transaction to ensure data integrity
            DB::transaction(function () use ($request, $transfer) {
                foreach ($request->items as $item) {
                    // 1. Check stock availability in the 'from' warehouse
                    $fromStock = Stock::where('warehouse_id', $transfer->from_warehouse_id)
                        ->where('product_id', $item['product_id'])
                        ->first();

                    if (!$fromStock || $fromStock->quantity < $item['quantity']) {
                        $product = Product::find($item['product_id']);
                        $prodName = $product ? $product->name : 'Unknown Product';
                        throw new \Exception("Insufficient stock for product: {$prodName}");
                    }

                    // 2. Decrement stock from 'from' warehouse
                    $fromStock->decrement('quantity', $item['quantity']);

                    // 3. Increment stock in 'to' warehouse
                    // Using firstOrCreate to handle cases where stock row doesn't exist yet
                    $toStock = Stock::firstOrCreate(
                        ['warehouse_id' => $transfer->to_warehouse_id, 'product_id' => $item['product_id']],
                        ['quantity' => 0]
                    );
                    $toStock->increment('quantity', $item['quantity']);

                    // 4. Create the transfer item record
                    $transfer->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }
            });

            // Load relationship fresh from DB
            $transfer->load('items.product');

            return response()->json([
                'message' => 'Items added to transfer successfully',
                'transfer' => $transfer
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Update a specific item in a stock transfer.
     * (Admin, Manager)
     */
    public function update(Request $request, StockTransfer $transfer, TransferItem $item)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::transaction(function () use ($request, $transfer, $item) {
                $oldQty = $item->quantity;
                $newQty = $request->quantity;
                $qtyDiff = $newQty - $oldQty;

                if ($qtyDiff == 0) return; // No change

                if ($qtyDiff > 0) {
                    // Quantity increased: Need to take MORE from 'from' warehouse
                    $fromStock = Stock::where('warehouse_id', $transfer->from_warehouse_id)
                        ->where('product_id', $item->product_id)
                        ->first();

                    if (!$fromStock || $fromStock->quantity < $qtyDiff) {
                        $product = Product::find($item->product_id);
                        throw new \Exception("Insufficient stock to increase quantity for: {$product->name}");
                    }

                    $fromStock->decrement('quantity', $qtyDiff);

                    $toStock = Stock::firstOrCreate(
                        ['warehouse_id' => $transfer->to_warehouse_id, 'product_id' => $item->product_id],
                        ['quantity' => 0]
                    );
                    $toStock->increment('quantity', $qtyDiff);
                } elseif ($qtyDiff < 0) {
                    // Quantity decreased: Need to send BACK to 'from' warehouse (take from 'to')
                    $absQtyDiff = abs($qtyDiff);

                    $toStock = Stock::where('warehouse_id', $transfer->to_warehouse_id)
                        ->where('product_id', $item->product_id)
                        ->first();

                    if (!$toStock || $toStock->quantity < $absQtyDiff) {
                        $product = Product::find($item->product_id);
                        throw new \Exception("Insufficient stock in destination to decrease quantity for: {$product->name}");
                    }

                    $toStock->decrement('quantity', $absQtyDiff);

                    $fromStock = Stock::firstOrCreate(
                        ['warehouse_id' => $transfer->from_warehouse_id, 'product_id' => $item->product_id],
                        ['quantity' => 0]
                    );
                    $fromStock->increment('quantity', $absQtyDiff);
                }

                // Update the transfer item quantity
                $item->update(['quantity' => $newQty]);
            });

            $transfer->load('items.product');

            return response()->json([
                'message' => 'Transfer item updated successfully',
                'transfer' => $transfer
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Remove a specific item from a stock transfer.
     * (Admin only)
     */
    public function destroy(StockTransfer $transfer, TransferItem $item)
    {
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            DB::transaction(function () use ($transfer, $item) {
                $quantity = $item->quantity;

                // 1. Check if the 'to' warehouse has enough stock to revert (give back)
                $toStock = Stock::where('warehouse_id', $transfer->to_warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->first();

                if (!$toStock || $toStock->quantity < $quantity) {
                    $product = Product::find($item->product_id);
                    throw new \Exception("Cannot delete item. Destination warehouse has used/sold the stock for: {$product->name}");
                }

                // 2. Revert stock quantities
                $toStock->decrement('quantity', $quantity);

                $fromStock = Stock::firstOrCreate(
                    ['warehouse_id' => $transfer->from_warehouse_id, 'product_id' => $item->product_id],
                    ['quantity' => 0]
                );
                $fromStock->increment('quantity', $quantity);

                // 3. Delete the transfer item
                $item->delete();
            });

            $transfer->load('items.product');

            return response()->json([
                'message' => 'Transfer item deleted successfully',
                'transfer' => $transfer
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
