<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_method_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('type', 10);
            $table->unsignedInteger('amount');
            $table->string('reference', 100)->nullable();
            $table->string('note')->nullable();
            $table->string('receipt_number', 30)->nullable()->unique();
            $table->uuid('idempotency_key')->unique();
            $table->dateTime('paid_at');
            $table->timestamps();

            $table->index(['branch_id', 'paid_at']);
            $table->index(['payment_method_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
