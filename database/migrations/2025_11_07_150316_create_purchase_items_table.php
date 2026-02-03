<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id('purchase_item_id');
            $table->foreignId('purchase_id')->constrained('purchases', 'purchase_id')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products', 'product_id')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('cost_price', 10, 2);
            $table->decimal('subtotal', 10, 2);
        });
    }

    public function down(): void {
        Schema::dropIfExists('purchase_items');
    }
};
