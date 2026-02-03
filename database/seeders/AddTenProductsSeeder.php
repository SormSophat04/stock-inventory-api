<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Unit;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Models\Supplier;
use Faker\Factory as Faker;

class AddTenProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Ensure we have some base data
        $category = Category::firstOrCreate(['name' => 'General'], ['description' => 'General items']);
        $brand = Brand::firstOrCreate(['name' => 'Generic Brand']);
        // Removed short_name as it does not exist in the schema
        $unit = Unit::firstOrCreate(['name' => 'Piece']);
        
        $warehouse = Warehouse::first() ?? Warehouse::create(['name' => 'Default Warehouse', 'location' => 'Main St']);
        $supplier = Supplier::first() ?? Supplier::create(['name' => 'Default Supplier', 'email' => 'supplier@example.com', 'phone' => '123456789']);

        // Get all available IDs to randomize
        $categoryIds = Category::pluck('category_id')->toArray();
        $brandIds = Brand::pluck('brand_id')->toArray();
        $unitIds = Unit::pluck('unit_id')->toArray();

        for ($i = 0; $i < 10; $i++) {
            $product = Product::create([
                'name' => 'New Product ' . $faker->unique()->word . ' ' . $faker->numberBetween(100, 999),
                'sku' => 'SKU-' . $faker->unique()->bothify('?????-#####'),
                'barcode' => $faker->unique()->ean13,
                'category_id' => $categoryIds[array_rand($categoryIds)] ?? $category->category_id,
                'brand_id' => $brandIds[array_rand($brandIds)] ?? $brand->brand_id,
                'unit_id' => $unitIds[array_rand($unitIds)] ?? $unit->unit_id,
                'sell_price' => $faker->randomFloat(2, 10, 500),
                'reorder_level' => 10,
                'status' => 'active',
                // Removed description as it's not in fillable
            ]);

            // Add some initial stock
            Stock::create([
                'product_id' => $product->product_id,
                'warehouse_id' => $warehouse->warehouse_id,
                'quantity' => $faker->numberBetween(10, 100),
                'supplier_id' => $supplier->supplier_id,
                'unit_price' => $product->sell_price * 0.7, // Cost is 70% of sell price
                'invoice' => 'INV-' . $faker->bothify('#####'),
                'type' => 'in', // Assuming 'in' for initial stock
                'note' => 'Initial seeded stock',
            ]);
        }
    }
}
