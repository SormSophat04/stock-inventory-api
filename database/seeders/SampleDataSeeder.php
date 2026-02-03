<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Brand;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Supplier;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
      use WithoutModelEvents;

      /**
       * Seed the application's database with sample data.
       */
      public function run(): void
      {
            // Create categories
            $electronics = Category::firstOrCreate(
                  ['name' => 'Electronics'],
                  ['description' => 'Electronic devices and gadgets']
            );
            $groceries = Category::firstOrCreate(
                  ['name' => 'Groceries'],
                  ['description' => 'Grocery items and food products']
            );

            // Create brands
            $apple = Brand::firstOrCreate(['name' => 'Apple']);
            $samsung = Brand::firstOrCreate(['name' => 'Samsung']);
            $happyCow = Brand::firstOrCreate(['name' => 'Happy Cow']);

            // Create warehouses
            $mainWarehouse = Warehouse::firstOrCreate(
                  ['name' => 'Main Warehouse'],
                  ['location' => 'Main City']
            );
            $branchA = Warehouse::firstOrCreate(
                  ['name' => 'Branch A'],
                  ['location' => 'Downtown']
            );            // Create supplier
            $supplier = Supplier::firstOrCreate(
                  ['name' => 'Tech Supplies Inc'],
                  ['email' => 'tech@example.com', 'phone' => '555-0001']
            );

            // Create products with low stock
            // Product 1: iPhone 15 Pro - LOW STOCK
            $product1 = Product::firstOrCreate(
                  ['name' => 'iPhone 15 Pro'],
                  [
                        'sku' => 'IPHONE-15-PRO',
                        'category_id' => $electronics->category_id,
                        'brand_id' => $apple->brand_id,
                        'sell_price' => 999,
                        'reorder_level' => 5,
                  ]
            );
            Stock::firstOrCreate(
                  ['product_id' => $product1->product_id, 'warehouse_id' => $mainWarehouse->warehouse_id],
                  ['quantity' => 3, 'supplier_id' => $supplier->supplier_id, 'unit_price' => 800]
            );

            // Product 2: Samsung Galaxy S25 - LOW STOCK
            $product2 = Product::firstOrCreate(
                  ['name' => 'Samsung Galaxy S25'],
                  [
                        'sku' => 'SAMSUNG-S25',
                        'category_id' => $electronics->category_id,
                        'brand_id' => $samsung->brand_id,
                        'sell_price' => 899,
                        'reorder_level' => 10,
                  ]
            );
            Stock::firstOrCreate(
                  ['product_id' => $product2->product_id, 'warehouse_id' => $branchA->warehouse_id],
                  ['quantity' => 8, 'supplier_id' => $supplier->supplier_id, 'unit_price' => 700]
            );

            // Product 3: Organic Whole Milk - LOW STOCK
            $product3 = Product::firstOrCreate(
                  ['name' => 'Organic Whole Milk'],
                  [
                        'sku' => 'MILK-ORG-1L',
                        'category_id' => $groceries->category_id,
                        'brand_id' => $happyCow->brand_id,
                        'sell_price' => 5.99,
                        'reorder_level' => 20,
                  ]
            );
            Stock::firstOrCreate(
                  ['product_id' => $product3->product_id, 'warehouse_id' => $mainWarehouse->warehouse_id],
                  ['quantity' => 12, 'supplier_id' => $supplier->supplier_id, 'unit_price' => 3.50]
            );

            // Product 4: MacBook Pro 16-inch - LOW STOCK
            $product4 = Product::firstOrCreate(
                  ['name' => 'MacBook Pro 16-inch'],
                  [
                        'sku' => 'MACBOOK-16',
                        'category_id' => $electronics->category_id,
                        'brand_id' => $apple->brand_id,
                        'sell_price' => 2499,
                        'reorder_level' => 5,
                  ]
            );
            Stock::firstOrCreate(
                  ['product_id' => $product4->product_id, 'warehouse_id' => $mainWarehouse->warehouse_id],
                  ['quantity' => 2, 'supplier_id' => $supplier->supplier_id, 'unit_price' => 1900]
            );

            // Product 5: Normal stock level (not low)
            $product5 = Product::firstOrCreate(
                  ['name' => 'iPad Air'],
                  [
                        'sku' => 'IPAD-AIR',
                        'category_id' => $electronics->category_id,
                        'brand_id' => $apple->brand_id,
                        'sell_price' => 599,
                        'reorder_level' => 5,
                  ]
            );
            Stock::firstOrCreate(
                  ['product_id' => $product5->product_id, 'warehouse_id' => $mainWarehouse->warehouse_id],
                  ['quantity' => 25, 'supplier_id' => $supplier->supplier_id, 'unit_price' => 450]
            );
      }
}
