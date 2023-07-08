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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->string('custom_transaction_id');

            $table->enum('transaction_type', ['income', 'expense', 'sales_payment', 'purchase_payment','opening_balance']);

            $table->integer('refID')->nullable(); //will keep explicitly inserted transactions like sales payment and purchase payment.

            $table->decimal('amount');

            $table->string('transaction_name')->nullable(); //sale-payment, purchase-payment, 

            $table->text('date');

            $table->text('image')->nullable();

            $table->unsignedBigInteger('store_id');

            $table->text('notes')->nullable(); //description for the particluar transaction

            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');

            $table->unsignedBigInteger('account_id');

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');

     
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
