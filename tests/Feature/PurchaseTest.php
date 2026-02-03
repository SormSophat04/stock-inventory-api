<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_purchase_and_updates_stock()
    {
        // 1. Create necessary data
        $user = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();
        $unit = Unit::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->category_id,
            'brand_id' => $brand->brand_id,
            'unit_id' => $unit->unit_id,
        ]);

        // 2. Authenticate as the user
        $this->actingAs($user);

        // 3. Send a POST request to create a purchase
        $purchaseData = [
            'supplier_id' => $supplier->supplier_id,
            'warehouse_id' => $warehouse->warehouse_id,
            'invoice_no' => 'INV-12345',
            'purchase_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $product->product_id,
                    'quantity' => 10,
                    'cost_price' => 100,
                ],
            ],
        ];

        $response = $this->postJson('/api/purchases', $purchaseData);

        // 4. Assert the purchase was created
        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Purchase recorded successfully',
            ]);

        $this->assertDatabaseHas('purchases', [
            'invoice_no' => 'INV-12345',
        ]);
        
        // Stock should NOT be updated yet
        $this->assertDatabaseMissing('stock', [
            'product_id' => $product->product_id,
            'warehouse_id' => $warehouse->warehouse_id,
        ]);

        // 5. Update Status to Received
        $purchaseId = $response->json('data.purchase_id');
        $this->putJson("/api/purchases/{$purchaseId}/status", ['status' => 'Received'])
             ->assertStatus(200);

        // 6. Assert the stock was updated
        $this->assertDatabaseHas('stock', [
            'product_id' => $product->product_id,
            'warehouse_id' => $warehouse->warehouse_id,
            'quantity' => 10,
        ]);
    }
}
