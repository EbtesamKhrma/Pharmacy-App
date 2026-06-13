<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pharmacist_id');
            $table->date('date');
            $table->unsignedTinyInteger('stars');
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
