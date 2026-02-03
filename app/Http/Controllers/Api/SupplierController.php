<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    use ApiResponse;

    // List all suppliers
    public function index()
    {
        $suppliers = Supplier::orderBy('supplier_id', 'desc')->get();
        return $this->success($suppliers, 'Suppliers retrieved successfully.');
    }

    // Create a new supplier
    public function store(StoreSupplierRequest $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager'])) {
            return $this->error('Unauthorized', 403);
        }

        $supplier = Supplier::create($request->validated());

        return $this->success($supplier, 'Supplier created successfully', 201);
    }

    // Show single supplier
    public function show($id)
    {
        $supplier = Supplier::find($id);
        if (!$supplier) return $this->error('Supplier not found', 404);

        return $this->success($supplier, 'Supplier retrieved successfully.');
    }

    // Update supplier
    public function update(UpdateSupplierRequest $request, $id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager'])) {
            return $this->error('Unauthorized', 403);
        }

        $supplier = Supplier::find($id);
        if (!$supplier) return $this->error('Supplier not found', 404);

        $supplier->update($request->validated());

        return $this->success($supplier, 'Supplier updated successfully');
    }

    // Delete supplier
    public function destroy($id)
    {
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return $this->error('Unauthorized', 403);
        }

        $supplier = Supplier::find($id);
        if (!$supplier) return $this->error('Supplier not found', 404);

        $supplier->delete();

        return $this->success([], 'Supplier deleted successfully');
    }
}
