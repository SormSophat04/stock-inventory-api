<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Unit;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;

class UnitController extends Controller
{
    use ApiResponse;

    // List all units
    public function index()
    {
        $units = Unit::orderBy('unit_id', 'desc')->get();
        return $this->success($units, 'Units retrieved successfully.');
    }

    // Create a new unit
    public function store(StoreUnitRequest $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager'])) {
            return $this->error('Unauthorized', 403);
        }

        $unit = Unit::create($request->validated());

        return $this->success($unit, 'Unit created successfully', 201);
    }

    // Show single unit
    public function show($id)
    {
        $unit = Unit::find($id);
        if (!$unit) return $this->error('Unit not found', 404);

        return $this->success($unit, 'Unit retrieved successfully.');
    }

    // Update unit
    public function update(UpdateUnitRequest $request, $id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager'])) {
            return $this->error('Unauthorized', 403);
        }

        $unit = Unit::find($id);
        if (!$unit) return $this->error('Unit not found', 404);

        $unit->update($request->validated());

        return $this->success($unit, 'Unit updated successfully');
    }

    // Delete unit
    public function destroy($id)
    {
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return $this->error('Unauthorized', 403);
        }

        $unit = Unit::find($id);
        if (!$unit) return $this->error('Unit not found', 404);

        $unit->delete();

        return $this->success([], 'Unit deleted successfully');
    }
}
