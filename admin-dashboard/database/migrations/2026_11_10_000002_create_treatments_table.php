<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_category_id')->constrained()->restrictOnDelete();
            $table->string('name', 150);
            $table->string('slug', 170)->unique();
            $table->text('description')->nullable();
            $table->string('service_type', 20);
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedInteger('price');
            $table->unsignedInteger('normal_price')->nullable();
            $table->date('promo_starts_on')->nullable();
            $table->date('promo_ends_on')->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'service_type', 'treatment_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatments');
    }
};
