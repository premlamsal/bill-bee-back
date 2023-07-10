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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('product_id');

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            $table->decimal('quantity');

            $table->decimal('price');

            $table->unsignedBigInteger('unit_id');

            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');

            $table->unsignedBigInteger('store_id');

            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
