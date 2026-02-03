<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productId = $this->route('product') ? $this->route('product')->product_id : null;

        return [
            'name'          => 'sometimes|string|max:255',
            'sku'           => 'sometimes|string|unique:products,sku,' . $productId . ',product_id',
            'barcode'       => 'sometimes|nullable|string|unique:products,barcode,' . $productId . ',product_id',
            'category_id'   => 'sometimes|exists:categories,category_id',
            'brand_id'      => 'sometimes|exists:brands,brand_id',
            'unit_id'       => 'sometimes|exists:units,unit_id',
            'sell_price'    => 'sometimes|numeric|min:0',
            'reorder_level' => 'sometimes|integer|min:0',
            'status'        => 'sometimes|string|in:active,inactive',
            'image'         => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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
