<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id('entry_id');
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->foreignId('debit_account_id')->constrained('accounts', 'account_id')->onDelete('cascade');
            $table->foreignId('credit_account_id')->constrained('accounts', 'account_id')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('journal_entries');
    }
};
