<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    use ApiResponse;

    // List all customers
    public function index()
    {
        $customers = Customer::orderBy('customer_id', 'desc')->get();
        return $this->success($customers, 'Customers retrieved successfully.');
    }

    // Create a new customer (admin + manager + cashier)
    public function store(StoreCustomerRequest $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager', 'cashier'])) {
            return $this->error('Unauthorized', 403);
        }

        $customer = Customer::create($request->validated());

        return $this->success($customer, 'Customer created successfully', 201);
    }

    // Show single customer
    public function show($id)
    {
        $customer = Customer::find($id);
        if (!$customer) return $this->error('Customer not found', 404);

        return $this->success($customer, 'Customer retrieved successfully.');
    }

    // Update customer
    public function update(UpdateCustomerRequest $request, $id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager'])) {
            return $this->error('Unauthorized', 403);
        }

        $customer = Customer::find($id);
        if (!$customer) return $this->error('Customer not found', 404);

        $customer->update($request->validated());

        return $this->success($customer, 'Customer updated successfully');
    }

    // Delete customer (admin only)
    public function destroy($id)
    {
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return $this->error('Unauthorized', 403);
        }

        $customer = Customer::find($id);
        if (!$customer) return $this->error('Customer not found', 404);

        $customer->delete();

        return $this->success([], 'Customer deleted successfully');
    }
}
