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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('custom_product_id')->nullable();

            $table->string('name');
            
            $table->string('image');
            
            $table->text('description');

            $table->decimal('cp');

            $table->decimal('sp');
            
            $table->string('opening_stock')->nullable();
            
            $table->unsignedBigInteger('product_cat_id');
            
            $table->foreign('product_cat_id')->references('id')->on('product_categories')->onDelete('cascade');
 
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
        Schema::dropIfExists('products');
    }
};
