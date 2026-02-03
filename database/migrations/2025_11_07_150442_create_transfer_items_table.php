<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('transfer_items', function (Blueprint $table) {
            $table->id('transfer_item_id');
            $table->foreignId('transfer_id')->constrained('stock_transfers', 'transfer_id')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products', 'product_id')->onDelete('cascade');
            $table->integer('quantity');
        });
    }

    public function down(): void {
        Schema::dropIfExists('transfer_items');
    }
};
