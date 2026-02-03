<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Dropping the raw Postgres check constraint that lingers even after enum change
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE stock DROP CONSTRAINT IF EXISTS stock_type_check');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ideally recreate it, but since we don't know the exact enum options easily
        // used previously without hardcoding, and we likely don't want it back, we can skip or:
        // DB::statement("ALTER TABLE stock ADD CONSTRAINT stock_type_check CHECK (type::text = ANY (ARRAY['Initial'::character varying, 'Adjustment'::character varying, 'Transfer'::character varying]::text[]))");
    }
};
