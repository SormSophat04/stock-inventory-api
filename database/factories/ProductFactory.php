<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'sku' => $this->faker->unique()->ean8,
            'barcode' => $this->faker->ean13,
            'category_id' => Category::factory(),
            'brand_id' => Brand::factory(),
            'unit_id' => Unit::factory(),
            'sell_price' => $this->faker->randomFloat(2, 20, 200),
            'reorder_level' => $this->faker->numberBetween(5, 20),
            'status' => 'active',
        ];
    }
}
