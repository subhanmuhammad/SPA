<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('therapist_treatments', function (Blueprint $table) {
            $table->foreignId('therapist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_id')->constrained()->cascadeOnDelete();
            $table->primary(['therapist_id', 'treatment_id']);
            $table->index('treatment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('therapist_treatments');
    }
};
