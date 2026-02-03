<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PurchaseItemController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\StockAdjustmentController;
use App\Http\Controllers\Api\SaleItemController;
use App\Http\Controllers\Api\TransferItemController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\StockTransferController;
use App\Http\Controllers\Api\StockCountController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\ReturnOrderController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

// ----- Public (No Auth) -----
Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
});

// Low stock alerts (public endpoint)
Route::get('/low-stock-alerts', [StockController::class, 'lowStockAlerts']);


// -------------------------------------------------------
// 🔐 AUTHENTICATED ROUTES (REQUIRES TOKEN)
// -------------------------------------------------------
Route::middleware(['auth:api'])->group(function () {

    // 🔄 Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // 📊 Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);


    // -------------------------------------------------------
    // 👑 ADMIN ONLY
    // -------------------------------------------------------
    Route::apiResource('users', UserController::class)->only(['index', 'store']);
    Route::apiResource('accounts', AccountController::class);
    Route::apiResource('audit-logs', AuditLogController::class)->only(['index']);



    // -------------------------------------------------------
    // 📦 MANAGER (inventory + products + categories + brands)
    // -------------------------------------------------------
    Route::apiResource('categories', CategoryController::class);
    Route::post('products/import', [ProductController::class, 'import']);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('brands', BrandController::class);
    Route::apiResource('units', UnitController::class);
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('warehouses', WarehouseController::class);
    Route::post('stocks/bulk', [StockController::class, 'storeBulk']);
    Route::post('stocks/bulk-out', [StockController::class, 'storeBulkOut']);
    Route::apiResource('stocks', StockController::class);
    Route::get('stocks/export', [StockController::class, 'export']);
    Route::post('stocks/import', [StockController::class, 'import']);




    // -------------------------------------------------------
    // 💵 CASHIER (process sales only)
    // -------------------------------------------------------
    Route::get('sales/export', [SaleController::class, 'export']);
    Route::apiResource('sales', SaleController::class);
    Route::get('sales/{sale}/full', [SaleController::class, 'getFullSale']);
    
    // Return Orders
    Route::apiResource('return-orders', ReturnOrderController::class);

    Route::apiResource('customers', CustomerController::class);


    // -------------------------------------------------------
    // 🏭 WAREHOUSE STAFF (stock movement only)
    // -------------------------------------------------------

    // Stock Transfers
    Route::apiResource('stock-transfers', StockTransferController::class);

    Route::post('stock-transfers/{transfer}/items', [TransferItemController::class, 'store']);
    Route::put('stock-transfers/{transfer}/items/{item}', [TransferItemController::class, 'update']);
    Route::delete('stock-transfers/{transfer}/items/{item}', [TransferItemController::class, 'destroy']);

    // Adjustments
    Route::apiResource('stock-adjustments', StockAdjustmentController::class)
        ->only(['index', 'store']);

    // Stock Counts (batch adjustments from frontend stock count page)
    Route::post('stock-counts', [StockCountController::class, 'store']);

    // Movements
    Route::apiResource('stock-movements', StockMovementController::class)
        ->only(['index']);


    // -------------------------------------------------------
    // 📦 PURCHASE (Manager + Admin)
    // -------------------------------------------------------
    Route::apiResource('purchases', PurchaseController::class);
    Route::post('purchases/{purchase}/items', [PurchaseItemController::class, 'store']);
    Route::put('/purchases/{id}/status', [PurchaseController::class, 'updateStatus']);


    // -------------------------------------------------------
    // 🔔 Notifications (All Auth Users)
    // -------------------------------------------------------
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
    Route::patch('notifications/{notification}/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);
});
