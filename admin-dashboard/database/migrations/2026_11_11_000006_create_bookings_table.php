<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique();
            $table->char('tracking_token_hash', 64);
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('therapist_id')->constrained()->restrictOnDelete();
            $table->boolean('therapist_requested')->default(false);
            $table->string('service_type', 20);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status', 20)->default('pending');
            $table->string('source', 10)->default('online');
            $table->string('customer_name', 100);
            $table->string('customer_phone', 20);
            $table->text('notes')->nullable();
            $table->text('address')->nullable();
            $table->string('address_notes')->nullable();
            $table->unsignedInteger('total_price');
            $table->text('change_request')->nullable();
            $table->timestamp('change_requested_at')->nullable();
            $table->timestamps();

            $table->index(['therapist_id', 'starts_at']);
            $table->index(['branch_id', 'starts_at']);
            $table->index(['status', 'starts_at']);
            $table->index('customer_phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
