<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('participants')
            ->where('age', '<', 13)
            ->update(['category' => 'Kanak-Kanak']);

        DB::table('participants')
            ->where('age', '>=', 13)
            ->where('age', '<', 18)
            ->update(['category' => 'Remaja']);

        DB::table('participants')
            ->where('age', '>=', 18)
            ->update(['category' => 'Dewasa']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This data correction is intentionally not reversible.
    }
};
