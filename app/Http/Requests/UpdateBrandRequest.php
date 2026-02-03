<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateBrandRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $brandId = $this->route('brand') ? $this->route('brand') : $this->route('id'); 
        // Note: Route parameter might be 'brand' or just 'id' depending on route definition. 
        // In api.php: Route::apiResource('brands', BrandController::class); -> parameter is 'brand'
        // But if custom route, check. Resource route uses singular name.

        return [
            'name' => 'required|string|max:150|unique:brands,name,' . $brandId . ',brand_id',
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
