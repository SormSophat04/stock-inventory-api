<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItems;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SaleFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $manager;
    protected $cashier;
    protected $customer;
    protected $warehouse;
    protected $product;
    protected $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->manager = User::factory()->create(['role' => 'manager']);
        $this->cashier = User::factory()->create(['role' => 'cashier']);
        $this->customer = Customer::factory()->create();
        $this->warehouse = Warehouse::factory()->create();
        $this->product = Product::factory()->create(['reorder_level' => 5]);
        $this->supplier = Supplier::factory()->create();

        Stock::create([
            'product_id' => $this->product->product_id,
            'warehouse_id' => $this->warehouse->warehouse_id,
            'supplier_id' => $this->supplier->supplier_id,
            'quantity' => 100,
            'unit_price' => 50.00,
            'type' => 'Initial',
        ]);
    }

    public function test_admin_can_get_full_sale_details()
    {
        $sale = Sale::create([
            'customer_id' => $this->customer->customer_id,
            'warehouse_id' => $this->warehouse->warehouse_id,
            'total_amount' => 100.00,
            'payment_method' => 'Cash',
            'created_by' => $this->admin->user_id,
            'invoice_no' => 'INV-ADMIN',
            'sale_date' => now(),
        ]);

        SaleItems::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $this->product->product_id,
            'quantity' => 10,
            'sell_price' => 10.00,
            'subtotal' => 100.00,
        ]);

        $response = $this->actingAs($this->admin)->getJson("/api/sales/{$sale->sale_id}/full");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'sale_id',
                    'customer_id',
                    'warehouse_id',
                    'total_amount',
                    'payment_method',
                    'created_by',
                    'items' => [
                        '*' => [
                            'sale_item_id',
                            'sale_id',
                            'product_id',
                            'quantity',
                            'sell_price',
                            'subtotal',
                            'product' => [
                                'product_id',
                                'name',
                            ]
                        ]
                    ]
                ]
            ])
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.product.product_id', $this->product->product_id);
    }

    public function test_manager_can_get_full_sale_details()
    {
        $sale = Sale::create([
            'customer_id' => $this->customer->customer_id,
            'warehouse_id' => $this->warehouse->warehouse_id,
            'total_amount' => 100.00,
            'payment_method' => 'Cash',
            'created_by' => $this->manager->user_id,
            'invoice_no' => 'INV-MANAGER',
            'sale_date' => now(),
        ]);

        SaleItems::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $this->product->product_id,
            'quantity' => 10,
            'sell_price' => 10.00,
            'subtotal' => 100.00,
        ]);

        $response = $this->actingAs($this->manager)->getJson("/api/sales/{$sale->sale_id}/full");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'sale_id',
                    'customer_id',
                    'warehouse_id',
                    'total_amount',
                    'payment_method',
                    'created_by',
                    'items' => [
                        '*' => [
                            'sale_item_id',
                            'sale_id',
                            'product_id',
                            'quantity',
                            'sell_price',
                            'subtotal',
                            'product' => [
                                'product_id',
                                'name',
                            ]
                        ]
                    ]
                ]
            ])
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.product.product_id', $this->product->product_id);
    }

    public function test_cashier_can_get_full_sale_details()
    {
        $sale = Sale::create([
            'customer_id' => $this->customer->customer_id,
            'warehouse_id' => $this->warehouse->warehouse_id,
            'total_amount' => 100.00,
            'payment_method' => 'Cash',
            'created_by' => $this->cashier->user_id,
            'invoice_no' => 'INV-CASHIER',
            'sale_date' => now(),
        ]);

        SaleItems::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $this->product->product_id,
            'quantity' => 10,
            'sell_price' => 10.00,
            'subtotal' => 100.00,
        ]);

        $response = $this->actingAs($this->cashier)->getJson("/api/sales/{$sale->sale_id}/full");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'sale_id',
                    'customer_id',
                    'warehouse_id',
                    'total_amount',
                    'payment_method',
                    'created_by',
                    'items' => [
                        '*' => [
                            'sale_item_id',
                            'sale_id',
                            'product_id',
                            'quantity',
                            'sell_price',
                            'subtotal',
                            'product' => [
                                'product_id',
                                'name',
                            ]
                        ]
                    ]
                ]
            ])
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.product.product_id', $this->product->product_id);
    }

    public function test_unauthenticated_user_cannot_get_full_sale_details()
    {
        $sale = Sale::create([
            'customer_id' => $this->customer->customer_id,
            'warehouse_id' => $this->warehouse->warehouse_id,
            'total_amount' => 100.00,
            'payment_method' => 'Cash',
            'created_by' => $this->admin->user_id,
            'invoice_no' => 'INV-UNAUTH',
            'sale_date' => now(),
        ]);

        $response = $this->getJson("/api/sales/{$sale->sale_id}/full");

        $response->assertStatus(401);
    }

    public function test_get_full_sale_details_for_non_existent_sale()
    {
        $response = $this->actingAs($this->admin)->getJson("/api/sales/99999/full");

        $response->assertStatus(404);
    }
}
