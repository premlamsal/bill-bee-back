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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('custom_account_id');
            $table->string('name'); //hold account name
            $table->decimal('balance'); //hold balance
            $table->decimal('opening_balance'); //hold balance
            $table->text('account_info'); //hold extra information of account
            $table->string('holder_name')->nullable(); //account holder name
            $table->string('bank_name')->nullable(); //bank name 
            $table->string('bank_acc_num')->nullable(); //bank account number
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
        Schema::dropIfExists('accounts');
    }
};
