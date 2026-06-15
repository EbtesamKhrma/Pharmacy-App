<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('cutomers')->onDelete('set null');
            $table->foreignId('pharmacist_id')->constrained('pharmacists')->onDelete('cascade');$table->date('date');
            $table->decimal('total_price');
            $table->enum('payment_method', ['cache', 'card', 'insurance']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
