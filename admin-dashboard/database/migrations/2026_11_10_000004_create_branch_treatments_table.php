<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_treatments', function (Blueprint $table) {
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('price')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->primary(['branch_id', 'treatment_id']);
            $table->index('treatment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_treatments');
    }
};
