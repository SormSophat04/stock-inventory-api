<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockMovementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = StockMovement::with(['product', 'warehouse', 'user']);

        // You can add filtering here later, e.g., by product, warehouse, or type
        // if ($request->has('product_id')) {
        //     $query->where('product_id', $request->product_id);
        // }

        $movements = $query->orderBy('movement_id', 'desc')->paginate(25);

        return response()->json($movements);
    }
}
