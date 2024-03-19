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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->nullable();
            $table->unsignedBigInteger("user_id")->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->string('shipping_carrier')->nullable();
            $table->integer('discount')->default(0);
            $table->string('discount_string')->nullable();
            $table->integer('total_amount')->default(0);
            $table->text('note')->nullable();
            $table->boolean('status')->default(0);
            $table->timestamp('purchased_at')->nullable();
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('companies');

            $table->foreign('store_id')
                ->references('id')
                ->on('stores');

            $table->foreign('supplier_id')
                ->references('id')
                ->on('suppliers');

            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('purchase_orders');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
