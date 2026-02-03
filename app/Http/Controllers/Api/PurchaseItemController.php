<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\Stock;
use App\Models\PurchaseItems;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PurchaseItemController extends Controller
{
    // Add items to an existing purchase
    public function store(Request $request, $purchase_id)
    {
        $user = Auth::user();
        if (!$user->role == 'admin' && !$user->role == 'manager' && !$user->role == 'warehouse_staff') {
            return response()->json(['error'=>'Unauthorized'],403);
        }

        $purchase = Purchase::find($purchase_id);
        if (!$purchase) return response()->json(['message'=>'Purchase not found'],404);

        $validator = Validator::make($request->all(), [
            'items'=>'required|array|min:1',
            'items.*.product_id'=>'required|exists:products,product_id',
            'items.*.quantity'=>'required|integer|min:1',
            'items.*.cost_price'=>'required|numeric|min:0'
        ]);

        if ($validator->fails()) return response()->json(['errors'=>$validator->errors()],422);

        $totalAdded = 0;

        foreach($request->items as $item){
            Stock::updateOrCreate(
                ['product_id' => $item['product_id'], 'warehouse_id' => $purchase->warehouse_id],
                ['quantity' => Stock::raw('quantity + '.$item['quantity'])]
            );

            $subtotal = $item['quantity'] * $item['cost_price'];
            $totalAdded += $subtotal;

            PurchaseItems::create([
                'purchase_id'=>$purchase->purchase_id,
                'product_id'=>$item['product_id'],
                'quantity'=>$item['quantity'],
                'cost_price'=>$item['cost_price'],
                'subtotal'=>$subtotal
            ]);
        }

        // Update total amount of purchase
        $purchase->increment('total_amount', $totalAdded);
        $purchase->load('items.product');

        return response()->json([
            'message'=>'Purchase items added successfully',
            'purchase'=>$purchase
        ]);
    }
}
