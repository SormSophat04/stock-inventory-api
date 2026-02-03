<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('return_orders', function (Blueprint $table) {
            $table->id('return_id');
            $table->string('return_no')->unique();
            $table->foreignId('sale_id')->nullable()->constrained('sales', 'sale_id')->onDelete('set null');
            $table->foreignId('customer_id')->constrained('customers', 'customer_id')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('warehouses', 'warehouse_id')->onDelete('cascade');
            $table->date('return_date');
            $table->decimal('total_refund', 10, 2);
            $table->enum('status', ['Draft', 'Confirmed', 'Cancelled'])->default('Draft');
            $table->string('reason')->nullable();
            $table->enum('refund_type', ['Cash', 'Credit Note', 'Exchange'])->default('Cash');
            $table->foreignId('created_by')->nullable()->constrained('users', 'user_id')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('return_orders');
    }
};
