<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stock', function (Blueprint $table) {
             // Change 'type' column to string to allow varied inputs like 'Purchase'
             $table->string('type')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock', function (Blueprint $table) {
             // Revert to original enum if needed. 
             // Note: data incompatible with enum will cause failure on rollback.
             // Ideally we shouldn't revert strictly if data is preserved.
             // But for completeness:
             // $table->enum('type', ['Initial', 'Adjustment', 'Transfer'])->default('Initial')->change();
             
             // Safer to just keep as string or leave empty for now as rollback might lose data
        });
    }
};
