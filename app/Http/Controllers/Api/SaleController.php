<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItems;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreSaleRequest;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesExport;

class SaleController extends Controller
{
    use ApiResponse;

    /**
     * Export sales to Excel
     */
    public function export()
    {
        return Excel::download(new SalesExport, 'sales.xlsx');
    }

    /**
     * Display all sales (Admin + Manager only)
     */
    public function index()
    {
        $user = Auth::user();

        if (!in_array($user->role, ['admin', 'manager', 'cashier'])) {
            return $this->error('Unauthorized', 403);
        }

        // Cashiers might only see their own sales, but for now giving access to list
        if ($user->role === 'cashier') {
             $sales = Sale::with(['items.product', 'user', 'customer', 'warehouse'])
                          ->where('created_by', $user->user_id)
                          ->orderBy('sale_id', 'desc')
                          ->get();
        } else {
             $sales = Sale::with(['items.product', 'user', 'customer', 'warehouse'])->orderBy('sale_id', 'desc')->get();
        }
        
        return $this->success($sales, 'Sales retrieved successfully.');
    }

    /**
     * Store a new sale (Admin, Manager, or Cashier)
     */
    public function store(StoreSaleRequest $request)
    {
        $user = Auth::user();
        $validated = $request->validated();

        try {
            return DB::transaction(function () use ($validated, $user) {
                // Removed stock availability check to allow negative stock/backorders


                // Create sale
                $sale = Sale::create([
                    'customer_id'   => $validated['customer_id'],
                    'warehouse_id'  => $validated['warehouse_id'],
                    'invoice_no'    => $validated['invoice_no'] ?? 'INV-' . time(), // Basic generation if null
                    'sale_date'     => $validated['sale_date'] ?? now(),
                    'total_amount'  => $validated['total_amount'],
                    'payment_method' => $validated['payment_method'],
                    'created_by'    => $user->user_id,
                ]);

                // Create sale items + update stock
                foreach ($validated['items'] as $item) {
                    // Find or create stock record (start at 0 if missing)
                    $stock = Stock::firstOrCreate(
                        [
                            'warehouse_id' => $validated['warehouse_id'],
                            'product_id'   => $item['product_id']
                        ],
                        [
                            'quantity'   => 0,
                            // Ideally, supplier_id should be set, but for sales logic we might miss it. 
                            // Setting nullable or default if possible, or picking from product if relationship exists.
                            // For safety, let's assume schema allows null or handle later. Here we just need a record to decrement.
                            'unit_price' => 0 // Fallback
                        ]
                    );

                    // Reduce product stock
                    $stock->decrement('quantity', $item['quantity']);

                    // Add sale item record
                    SaleItems::create([
                        'product_id' => $item['product_id'],
                        'sale_id'    => $sale->sale_id,
                        'quantity'   => $item['quantity'],
                        'sell_price'      => $item['sell_price'],
                        'subtotal'      => $item['quantity'] * $item['sell_price'],
                    ]);


                    $product = Product::find($item['product_id']);
                    
                    // Log Movement
                    StockMovement::create([
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $validated['warehouse_id'],
                        'type' => 'OUT', // Keeping it consistent with StockOut
                        'reference_no' => $sale->invoice_no,
                        'quantity' => $item['quantity'], // Negative logic or just magnitude. OUT implies negative.
                        'note' => 'Sale ID: ' . $sale->sale_id,
                        'created_by' => $user->user_id,
                    ]);

                    // Send Firebase notification if stock is low (assuming reorder_level exists on product)
                    if ($stock->quantity <= $product->reorder_level) {
                        // Assuming make function static or inject service
                        // FirebaseService::sendNotification(...) 
                    }
                }

                // Return sale details with items
                $sale->load(['items.product', 'customer', 'warehouse', 'user']);

                return $this->success($sale, 'Sale recorded successfully', 201);
            });
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Show single sale (All roles can view)
     */
    public function show($id)
    {
        $sale = Sale::with(['items.product', 'user', 'customer', 'warehouse'])->find($id);

        if (!$sale) {
            return $this->error('Sale not found.', 404);
        }

        return $this->success($sale, 'Sale retrieved successfully.');
    }

    /**
     * Get full sale details (redundant but requested by API route)
     */
    public function getFullSale($id)
    {
        return $this->show($id);
    }

    /**
     * Delete sale (Admin only)
     */
    public function destroy($id)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return $this->error('Unauthorized', 403);
        }

        $sale = Sale::find($id);

        if (!$sale) {
            return $this->error('Sale not found.', 404);
        }

        // Logic for restoring stock could be added here if needed
        $sale->items()->delete();
        $sale->delete();

        return $this->success([], 'Sale deleted successfully');
    }
}
