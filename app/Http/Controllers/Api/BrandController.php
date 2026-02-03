<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Models\Brand;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;

class BrandController extends Controller
{
    use ApiResponse;

    // List all brands
    public function index()
    {
        $brands = Brand::orderBy('brand_id', 'desc')->get();
        return $this->success($brands, 'Brands retrieved successfully.');
    }

    // Create a new brand (admin + manager)
    public function store(StoreBrandRequest $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager'])) {
            return $this->error('Unauthorized', 403);
        }

        $brand = Brand::create($request->validated());

        return $this->success($brand, 'Brand created successfully', 201);
    }

    // Show single brand
    public function show($id)
    {
        $brand = Brand::find($id);
        if (!$brand) return $this->error('Brand not found', 404);
        
        return $this->success($brand, 'Brand retrieved successfully.');
    }

    // Update brand
    public function update(UpdateBrandRequest $request, $id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager'])) {
            return $this->error('Unauthorized', 403);
        }

        $brand = Brand::find($id);
        if (!$brand) return $this->error('Brand not found', 404);

        $brand->update($request->validated());

        return $this->success($brand, 'Brand updated successfully');
    }

    // Delete brand (admin only)
    public function destroy($id)
    {
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return $this->error('Unauthorized', 403);
        }

        $brand = Brand::find($id);
        if (!$brand) return $this->error('Brand not found', 404);

        $brand->delete();

        return $this->success([], 'Brand deleted successfully');
    }
}
