<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductsImport;

class ProductController extends Controller
{
    use ApiResponse;

    /**
     * Import products from Excel
     */
    public function import(Request $request) 
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv,txt',
        ]);

        try {
            Excel::import(new ProductsImport, $request->file('file'));
            return $this->success([], 'Products imported successfully.');
        } catch (\Exception $e) {
            \Log::error('Import Error: ' . $e->getMessage());
            return $this->error('Import Failed: ' . $e->getMessage(), 500);
        }
    }

    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display all products (All roles can view)
     */
    public function index()
    {
        $products = $this->productService->getAllProducts();
        return $this->success($products, 'Products retrieved successfully.');
    }

    /**
     * Store a new product (Only admin or manager)
     */
    public function store(StoreProductRequest $request)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['admin', 'manager'])) {
            return $this->error('Unauthorized', 403);
        }

        $product = $this->productService->createProduct(
            $request->validated(),
            $request->file('image')
        );

        return $this->success($product, 'Product created successfully.', 201);
    }

    /**
     * Show single product (All roles can view)
     */
    public function show($id)
    {
        $product = $this->productService->getProductById($id);

        if (!$product) {
            return $this->error('Product not found.', 404);
        }

        return $this->success($product, 'Product retrieved successfully.');
    }

    /**
     * Update product (Admin or Manager or Warehouse Staff)
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['admin', 'manager', 'warehouse_staff'])) {
            return $this->error('Unauthorized', 403);
        }

        if (!$product) {
            return $this->error('Product not found.', 404);
        }

        $updatedProduct = $this->productService->updateProduct(
            $product,
            $request->validated(),
            $request->file('image')
        );

        return $this->success($updatedProduct, 'Product updated successfully.');
    }

    /**
     * Delete product (Admin only)
     */
    public function destroy(Product $product)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['admin'])) {
            return $this->error('Unauthorized', 403);
        }

        if (!$product) {
            return $this->error('Product not found.', 404);
        }

        $this->productService->deleteProduct($product);

        return $this->success([], 'Product deleted successfully.');
    }
}
