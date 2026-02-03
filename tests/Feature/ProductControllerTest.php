<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create necessary dependencies if they don't exist, or mock them
    }

    public function test_can_list_products()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Product::factory()->count(3)->create();

        $response = $this->actingAs($user, 'api')->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'product_id',
                        'name',
                        'sku',
                        'sell_price'
                    ]
                ]
            ]);
    }

    public function test_can_create_product()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        $unit = Unit::factory()->create();

        $data = [
            'name' => 'Test Product',
            'category_id' => $category->category_id,
            'brand_id' => $brand->brand_id,
            'unit_id' => $unit->unit_id,
            'sell_price' => 100,
            'status' => 'active'
        ];

        $response = $this->actingAs($user, 'api')->postJson('/api/products', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Product created successfully.',
                'data' => [
                    'name' => 'Test Product',
                    'sell_price' => 100
                ]
            ]);

        $this->assertDatabaseHas('products', ['name' => 'Test Product']);
    }

    public function test_can_update_product()
    {
        $user = User::factory()->create(['role' => 'manager']);
        $product = Product::factory()->create();

        $data = [
            'name' => 'Updated Product Name',
            'sell_price' => 150
        ];

        $response = $this->actingAs($user, 'api')->putJson("/api/products/{$product->product_id}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Product updated successfully.',
                'data' => [
                    'name' => 'Updated Product Name',
                    'sell_price' => 150
                ]
            ]);

        $this->assertDatabaseHas('products', ['product_id' => $product->product_id, 'name' => 'Updated Product Name']);
    }

    public function test_can_delete_product()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();

        $response = $this->actingAs($user, 'api')->deleteJson("/api/products/{$product->product_id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Product deleted successfully.'
            ]);

        $this->assertDatabaseMissing('products', ['product_id' => $product->product_id]);
    }
}
