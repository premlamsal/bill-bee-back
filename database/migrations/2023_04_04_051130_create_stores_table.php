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
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // $table->string('custom_store_id')->nullable();
            $table->string('address');
            $table->string('phone');
            $table->text('detail')->nullable();
            $table->string('mobile');
            $table->string('email')->unique();
            $table->string('url')->nullable();
            $table->string('tax_number')->nullable();
            $table->decimal('tax_percentage')->nullable();
            $table->decimal('profit_percentage');
            $table->string('product_id_count')->default('PRO-0');
            $table->string('invoice_id_count')->default('INV-0');
            $table->string('purchase_id_count')->default('PUR-0');
            $table->string('customer_id_count')->default('CUS-0');
            $table->string('supplier_id_count')->default('SUP-0');
            $table->string('unit_id_count')->default('UNI-0');
            $table->string('tax_id_count')->default('TAX-0');
            $table->string('return_invoice_id_count')->default('RINV-0');
            $table->string('return_purchase_id_count')->default('RPUR-0');
            $table->string('store_logo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
