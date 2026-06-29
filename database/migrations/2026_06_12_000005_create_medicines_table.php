<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->foreignId('pharmacy_id')->nullable()->constrained('pharmacies')->onDelete('cascade');
            $table->string('name');
            $table->decimal('cost_price');
            $table->decimal('selling_price');
            $table->string('manufacturer');
            $table->integer('quantity');
            $table->integer('reorder_level')->default(30);
            $table->date('expire_date');
            $table->string('category_medicine');
            $table->decimal('qr_code');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};
