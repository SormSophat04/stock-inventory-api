<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Models\Warehouse;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;

class WarehouseController extends Controller
{
    use ApiResponse;

    // List all warehouses
    public function index()
    {
        $warehouses = Warehouse::orderBy('warehouse_id', 'desc')->get();
        return $this->success($warehouses, 'Warehouses retrieved successfully.');
    }

    // Create a new warehouse
    public function store(StoreWarehouseRequest $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager'])) {
            return $this->error('Unauthorized', 403);
        }

        $warehouse = Warehouse::create($request->validated());

        return $this->success($warehouse, 'Warehouse created successfully', 201);
    }

    // Show single warehouse
    public function show($id)
    {
        $warehouse = Warehouse::find($id);
        if (!$warehouse) return $this->error('Warehouse not found', 404);

        return $this->success($warehouse, 'Warehouse retrieved successfully.');
    }

    // Update warehouse
    public function update(UpdateWarehouseRequest $request, $id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager'])) {
            return $this->error('Unauthorized', 403);
        }

        $warehouse = Warehouse::find($id);
        if (!$warehouse) return $this->error('Warehouse not found', 404);

        $warehouse->update($request->validated());

        return $this->success($warehouse, 'Warehouse updated successfully');
    }

    // Delete warehouse
    public function destroy($id)
    {
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return $this->error('Unauthorized', 403);
        }

        $warehouse = Warehouse::find($id);
        if (!$warehouse) return $this->error('Warehouse not found', 404);

        $warehouse->delete();

        return $this->success([], 'Warehouse deleted successfully');
    }
}
