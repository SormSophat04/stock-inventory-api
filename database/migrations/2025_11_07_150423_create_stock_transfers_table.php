<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id('transfer_id');
            $table->foreignId('from_warehouse')->constrained('warehouses', 'warehouse_id')->onDelete('cascade');
            $table->foreignId('to_warehouse')->constrained('warehouses', 'warehouse_id')->onDelete('cascade');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users', 'user_id')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('stock_transfers');
    }
};
