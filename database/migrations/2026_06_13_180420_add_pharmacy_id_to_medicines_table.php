<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->foreignId('pharmacy_id')
                ->constrained('pharmacies')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->dropForeign(['pharmacy_id']);
            $table->dropColumn('pharmacy_id');
        });
    }
};
