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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->nullable();
            $table->unsignedBigInteger("user_id")->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->integer('discount')->default(0);
            $table->string('discount_string')->nullable();
            $table->integer('total_amount')->default(0);
            $table->unsignedInteger('credit_days')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('status')->nullable();
            $table->timestamp('ordered_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('companies');

            $table->foreign('supplier_id')
                ->references('id')
                ->on('suppliers');

            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
