<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class StoreSaleRequest extends FormRequest
{
      /**
       * Determine if the user is authorized to make this request.
       */
      public function authorize(): bool
      {
            $user = Auth::user();
            return in_array($user->role, ['admin', 'manager', 'cashier']);
      }

      /**
       * Get the validation rules that apply to the request.
       *
       * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
       */
      public function rules(): array
      {
            return [
                  'customer_id' => 'nullable|integer|exists:customers,customer_id',
                  'warehouse_id' => 'required|integer|exists:warehouses,warehouse_id',
                  'total_amount'  => 'required|numeric|min:0',
                  'payment_method' => 'required|string',
                  'invoice_no' => 'nullable|string|unique:sales,invoice_no',
                  'sale_date' => 'nullable|date',
                  'items'         => 'required|array|min:1',
                  'items.*.product_id' => 'required|exists:products,product_id',
                  'items.*.quantity'   => 'required|integer|min:1',
                  'items.*.sell_price'      => 'required|numeric|min:0',
            ];
      }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation Error',
            'errors' => $validator->errors()
        ], 422));
    }
}
