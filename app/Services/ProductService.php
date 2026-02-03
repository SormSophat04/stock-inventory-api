<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * list all products
     */
    public function getAllProducts()
    {
        return Product::with(['category', 'brand', 'unit'])
            ->withSum('stocks as quantity', 'quantity')
            ->orderBy('product_id', 'desc')
            ->get();
    }

    /**
     * Get a single product by ID
     */
    public function getProductById($id)
    {
        return Product::with(['category', 'brand', 'unit'])
            ->withSum('stocks as quantity', 'quantity')
            ->find($id);
    }

    /**
     * Create a new product.
     *
     * @param array $data
     * @param \Illuminate\Http\UploadedFile|null $image
     * @return Product
     */
    public function createProduct(array $data, $image = null)
    {
        // Handle image upload
        if ($image) {
            $data['image'] = $this->uploadImage($image);
        }

        // Auto-generate SKU if not provided
        if (empty($data['sku'])) {
            $data['sku'] = 'SKU-' . strtoupper(uniqid());
        }

        return Product::create($data);
    }

    /**
     * Update an existing product.
     *
     * @param Product $product
     * @param array $data
     * @param \Illuminate\Http\UploadedFile|null $image
     * @return Product
     */
    public function updateProduct(Product $product, array $data, $image = null)
    {
        // Handle image update
        if ($image) {
            // Delete old image if exists
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $this->uploadImage($image);
        }

        $product->update($data);

        return $product->fresh(['category', 'brand', 'unit']);
    }

    /**
     * Delete a product.
     *
     * @param Product $product
     * @return bool|null
     */
    public function deleteProduct(Product $product)
    {
        // Delete image if exists
        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        return $product->delete();
    }

    /**
     * Upload image to storage.
     *
     * @param \Illuminate\Http\UploadedFile $image
     * @return string
     */
    protected function uploadImage($image)
    {
        return $image->store('product-images', 'public');
    }
}
