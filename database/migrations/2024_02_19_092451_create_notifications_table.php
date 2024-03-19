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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();       // Receiver
            $table->string('reference_no')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->integer('amount')->nullable();
            $table->string('message')->nullable();
            $table->boolean('read')->nullable()->default(0);
            $table->integer('notifiable_id')->nullable();               // Sender
            $table->string('notifiable_type')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('companies');

            $table->foreign('supplier_id')
                ->references('id')
                ->on('suppliers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
