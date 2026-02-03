<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Stock;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Warehouse;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * Get dashboard statistics and data
     */
    public function index()
    {
        try {
            $data = [
                'stats' => $this->getStats(),
                'sales_chart' => $this->getSalesChart(),
                'profit_chart' => $this->getProfitChart(),
                'warehouse_stock' => $this->getWarehouseStock(),
                'low_stock' => $this->getLowStockItems(),
                'top_categories' => $this->getTopCategories(),
                'top_brands' => $this->getTopBrands(),
                'recent_sales' => $this->getRecentSales(),
                'recent_purchases' => $this->getRecentPurchases(),
            ];

            return $this->success($data, 'Dashboard data retrieved successfully.');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve dashboard data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get summary statistics
     */
    private function getStats()
    {
        $totalProducts = Product::count();
        
        // Calculate total stock value (sum of all stock quantities * sell_price)
        $totalStockValue = DB::table('stock')
            ->join('products', 'stock.product_id', '=', 'products.product_id')
            ->sum(DB::raw('stock.quantity * products.sell_price'));

        // Today's sales
        $todaySales = Sale::whereDate('sale_date', Carbon::today())
            ->sum('total_amount');

        // Low stock count (products where total stock <= reorder_level)
        $lowStockCount = Product::whereHas('stocks', function($query) {
            $query->select('product_id')
                ->groupBy('product_id')
                ->havingRaw('SUM(quantity) <= products.reorder_level');
        })->count();

        return [
            'total_products' => $totalProducts,
            'total_stock_value' => round($totalStockValue, 2),
            'today_sales' => round($todaySales, 2),
            'low_stock_count' => $lowStockCount,
        ];
    }

    /**
     * Get sales data for the last 7 days
     */
    private function getSalesChart()
    {
        $salesData = Sale::where('sale_date', '>=', Carbon::now()->subDays(6))
            ->select(
                DB::raw('DATE(sale_date) as date'),
                DB::raw('SUM(total_amount) as total')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Fill in missing days with 0
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dayName = Carbon::now()->subDays($i)->format('D');
            
            $sale = $salesData->firstWhere('date', $date);
            $chartData[] = [
                'date' => $dayName,
                'total' => $sale ? round($sale->total, 2) : 0,
            ];
        }

        return $chartData;
    }

    /**
     * Get profit/loss data for the last 7 days
     */
    private function getProfitChart()
    {
        $profitData = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            
            // Revenue from sales
            $revenue = Sale::whereDate('sale_date', $dateStr)->sum('total_amount');
            
            // COGS from purchases (simplified - using purchase amounts for the day)
            $cogs = Purchase::whereDate('purchase_date', $dateStr)->sum('total_amount');
            
            $profit = $revenue - $cogs;
            
            $profitData[] = [
                'date' => $date->format('M j'),
                'revenue' => round($revenue, 2),
                'cogs' => round($cogs, 2),
                'profit' => round($profit, 2),
            ];
        }

        return $profitData;
    }

    /**
     * Get warehouse stock overview
     */
    private function getWarehouseStock()
    {
        return Warehouse::withSum('stocks', 'quantity')
            ->get()
            ->map(function($warehouse) {
                return [
                    'id' => $warehouse->warehouse_id,
                    'warehouse_name' => $warehouse->name,
                    'total_stock' => $warehouse->stocks_sum_quantity ?? 0,
                ];
            });
    }

    /**
     * Get low stock items
     */
    private function getLowStockItems()
    {
        return Product::select('products.product_id as id', 'products.name', 'products.reorder_level as min_level')
            ->selectRaw('COALESCE(SUM(stock.quantity), 0) as current_stock')
            ->leftJoin('stock', 'products.product_id', '=', 'stock.product_id')
            ->groupBy('products.product_id', 'products.name', 'products.reorder_level')
            ->havingRaw('COALESCE(SUM(stock.quantity), 0) <= products.reorder_level')
            ->orderBy('current_stock', 'asc')
            ->limit(10)
            ->get();
    }

    /**
     * Get top categories by product count
     */
    private function getTopCategories()
    {
        return Category::withCount('products')
            ->orderBy('products_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function($category) {
                return [
                    'id' => $category->category_id,
                    'name' => $category->name,
                    'products_count' => $category->products_count,
                ];
            });
    }

    /**
     * Get top brands by product count
     */
    private function getTopBrands()
    {
        return Brand::withCount('products')
            ->orderBy('products_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function($brand) {
                return [
                    'id' => $brand->brand_id,
                    'name' => $brand->name,
                    'products_count' => $brand->products_count,
                ];
            });
    }

    /**
     * Get recent sales
     */
    private function getRecentSales()
    {
        return Sale::with('customer')
            ->orderBy('sale_date', 'desc')
            ->limit(5)
            ->get()
            ->map(function($sale) {
                return [
                    'id' => $sale->sale_id,
                    'invoice_no' => $sale->invoice_no,
                    'customer_name' => $sale->customer->name ?? 'Walk-in',
                    'total_amount' => round($sale->total_amount, 2),
                ];
            });
    }

    /**
     * Get recent purchases
     */
    private function getRecentPurchases()
    {
        return Purchase::with('supplier')
            ->orderBy('purchase_date', 'desc')
            ->limit(5)
            ->get()
            ->map(function($purchase) {
                return [
                    'id' => $purchase->purchase_id,
                    'invoice_no' => $purchase->invoice_no,
                    'supplier_name' => $purchase->supplier->name ?? 'Unknown',
                    'total_amount' => round($purchase->total_amount, 2),
                ];
            });
    }
}
