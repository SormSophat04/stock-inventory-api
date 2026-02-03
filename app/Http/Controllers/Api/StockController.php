<?php

namespace App\Http\Controllers\Api;

use App\Exports\StockExport;
use App\Http\Controllers\Controller;
use App\Imports\StockImport;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\ApiResponse;

class StockController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $stocks = Stock::with(['warehouse', 'product', 'supplier'])->get();
        return $this->success($stocks);
    }
    
    // ... (rest of methods will be updated via other tools if needed, but I will replace the whole file for cleaner refactor if size permits, otherwise chunked)

    /**
     * Store multiple stock items (Bulk Stock-In).
     * This handles the entire Purchase Order in one transaction.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeBulk(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager', 'warehouse_staff'])) {
            return $this->error('Unauthorized', 403);
        }

        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'warehouse_id' => 'required|exists:warehouses,warehouse_id',
            'purchase_date' => 'required|date',
            'invoice' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,product_id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors()->toArray());
        }

        $createdStocks = [];

        try {
            DB::transaction(function () use ($request, $user, &$createdStocks) {
                foreach ($request->items as $item) {
                    // Check if stock exists for this product in this warehouse
                    $existingStock = Stock::where('warehouse_id', $request->warehouse_id)
                        ->where('product_id', $item['product_id'])
                        ->first();

                    if ($existingStock) {
                        // Update existing stock
                        $existingStock->quantity += $item['quantity'];
                        // Update unit price to latest purchase price
                        $existingStock->unit_price = $item['unit_price'];
                        $existingStock->invoice = $request->invoice; // Update invoice ref
                        $existingStock->save();
                        $createdStocks[] = $existingStock;
                    } else {
                        // Create new stock record
                        $newStock = Stock::create([
                            'warehouse_id' => $request->warehouse_id,
                            'product_id' => $item['product_id'],
                            'supplier_id' => $request->supplier_id,
                            'created_by' => $user->user_id,
                            'invoice' => $request->invoice,
                            'unit_price' => $item['unit_price'],
                            'quantity' => $item['quantity'],
                            'type' => 'Purchase', // Assuming type field exists or usage implied
                            'note' => $request->note,
                        ]);
                        $createdStocks[] = $newStock;
                    }

                    // Log Movement
                    StockMovement::create([
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $request->warehouse_id,
                        'type' => 'IN',
                        'reference_no' => $request->invoice ?? 'N/A',
                        'quantity' => $item['quantity'],
                        'note' => $request->note ?? 'Stock In',
                        'created_by' => $user->user_id,
                    ]);
                }
            });

            return $this->success($createdStocks, 'Stock-In processed successfully', 201);

        } catch (\Exception $e) {
            return $this->error('Stock-In Failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store multiple stock-out items (Inventory Deduction).
     * Handles Damaged, Expired, Internal Use, etc.
     */
    public function storeBulkOut(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager', 'warehouse_staff'])) {
            return $this->error('Unauthorized', 403);
        }

        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,warehouse_id',
            'reason' => 'required|string', // e.g. 'Damaged', 'Expired', 'Internal Use'
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,product_id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors()->toArray());
        }

        try {
            DB::transaction(function () use ($request, $user) {
                foreach ($request->items as $item) {
                    $stock = Stock::where('warehouse_id', $request->warehouse_id)
                        ->where('product_id', $item['product_id'])
                        ->first();

                    if (!$stock || $stock->quantity < $item['quantity']) {
                        $pName = $stock && $stock->product ? $stock->product->name : 'Product #' . $item['product_id'];
                        throw new \Exception("Insufficient stock for {$pName} (Requested: {$item['quantity']}, Available: " . ($stock ? $stock->quantity : 0) . ")");
                    }

                    // Deduct Stock
                    $stock->quantity -= $item['quantity'];
                    $stock->save();

                    // Log Movement
                    StockMovement::create([
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $request->warehouse_id,
                        'type' => $request->reason, // 'Damaged', 'Expired', etc.
                        'reference_no' => 'OUT-' . time(), // Simple reference
                        'quantity' => $item['quantity'],
                        'note' => $request->note,
                        'created_by' => $user->user_id,
                    ]);
                }
            });

            return $this->success([], 'Stock deduction processed successfully', 201);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400); // 400 for business logic error (insufficient stock)
        }
    }

    public function show($id)
    {
        $stock = Stock::with(['warehouse', 'product', 'supplier'])->find($id);
        if (!$stock) return $this->error('Stock not found', 404);
        return $this->success($stock);
    }

    // Keep single store for backward compatibility or individual adjustment
    public function store(Request $request)
    {
        // ... (Keep existing logic but wrap with ApiResponse if strictly needed, 
        // OR rely on storeBulk for new frontend. 
        // For now, let's update it to standardization just in case)
        
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager', 'warehouse_staff'])) {
            return $this->error('Unauthorized', 403);
        }

        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,warehouse_id',
            'product_id' => 'required|exists:products,product_id',
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'invoice' => 'nullable|string|max:255',
            'unit_price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) return $this->error('Validation Error', 422, $validator->errors()->toArray());

        // ... existing logic simplified ...
        $stock = null;
        DB::transaction(function () use ($request, $user, &$stock) {
             // ... same logic as before ...
             // Re-implementing briefly for completeness of file
            $existingStock = Stock::where('warehouse_id', $request->warehouse_id)
                ->where('product_id', $request->product_id)
                ->first();

            if ($existingStock) {
                $existingStock->quantity += $request->quantity;
                $existingStock->unit_price = $request->unit_price;
                $existingStock->invoice = $request->invoice;
                $existingStock->note = $request->note;
                $existingStock->save();
                $stock = $existingStock;
            } else {
                $stock = Stock::create([
                    'warehouse_id' => $request->warehouse_id,
                    'product_id' => $request->product_id,
                    'supplier_id' => $request->supplier_id,
                    'created_by' => $user->user_id,
                    'invoice' => $request->invoice,
                    'unit_price' => $request->unit_price,
                    'quantity' => $request->quantity,
                    'note' => $request->note,
                ]);
            }

            StockMovement::create([
                'product_id' => $stock->product_id,
                'warehouse_id' => $stock->warehouse_id,
                'type' => 'IN',
                'reference_no' => $stock->invoice,
                'quantity' => $request->quantity,
                'note' => $stock->note,
                'created_by' => $user->user_id,
            ]);
        });
        
        return $this->success($stock, 'Stock updated successfully', 201);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager'])) {
            return $this->error('Unauthorized', 403);
        }

        $stock = Stock::find($id);
        if (!$stock) return $this->error('Stock not found', 404);

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:0',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) return $this->error('Validation Error', 422, $validator->errors()->toArray());

        DB::transaction(function () use ($request, $user, $stock) {
            $diff = $request->quantity - $stock->quantity;
            $stock->update([
                'quantity' => $request->quantity,
                'note' => $request->note,
            ]);

            StockMovement::create([
                'product_id' => $stock->product_id,
                'warehouse_id' => $stock->warehouse_id,
                'type' => 'ADJUSTMENT',
                'quantity' => $diff,
                'note' => $request->note,
                'created_by' => $user->user_id,
            ]);
        });

        return $this->success($stock, 'Stock corrected successfully');
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if ($user->role !== 'admin') return $this->error('Unauthorized', 403);

        $stock = Stock::find($id);
        if (!$stock) return $this->error('Stock not found', 404);

        $stock->delete();
        return $this->success([], 'Stock deleted successfully');
    }

    // Keep Import/Export as is or wrap response if needed.
    // Minimizing changes to import/export for now unless requested.
    public function export()
    {
        return Excel::download(new StockExport, 'stocks.xlsx');
    }

    public function import(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager'])) return $this->error('Unauthorized', 403);

        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls,csv|max:2048',
        ]);

        if ($validator->fails()) return $this->error('Validation Error', 422, $validator->errors()->toArray());

        try {
            Excel::import(new StockImport($user->user_id), $request->file('file'));
            return $this->success([], 'Stocks imported successfully');
        } catch (\Exception $e) {
            return $this->error('Import failed: ' . $e->getMessage(), 500);
        }
    }

    public function lowStockAlerts()
    {
        // Fetch stocks where quantity is less than or equal to product's reorder_level
        // We need to join with products table to compare columns
        // NOTE: Stock table is 'stock', Product table is 'products'
        $lowStocks = Stock::join('products', 'stock.product_id', '=', 'products.product_id')
            ->whereColumn('stock.quantity', '<=', 'products.reorder_level')
            ->with(['product.category', 'product.brand', 'warehouse'])
            ->select('stock.*') // Select stock columns to avoid collision
            ->get();

         $alerts = $lowStocks->map(function ($stock) {
            if (!$stock->product) return null;
            return [
                'product_id' => $stock->product->product_id,
                'name' => $stock->product->name,
                'category_id' => $stock->product->category_id,
                'category_name' => $stock->product->category ? $stock->product->category->name : 'N/A',
                'brand_id' => $stock->product->brand_id,
                'brand_name' => $stock->product->brand ? $stock->product->brand->name : 'N/A',
                'warehouse_id' => $stock->warehouse->warehouse_id,
                'warehouse_name' => $stock->warehouse->name,
                'current_stock' => $stock->quantity,
                'min_stock_level' => $stock->product->reorder_level ?? 0,
                'reorder_qty' => 10, // Static for now, or add column to product/settings
                'last_purchase' => $stock->created_at ? $stock->created_at->format('Y-m-d') : 'N/A',
            ];
        })->filter();

        return $this->success($alerts->values());
    }
}
