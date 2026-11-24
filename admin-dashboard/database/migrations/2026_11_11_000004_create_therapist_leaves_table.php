<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('therapist_leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('therapist_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('reason', 150)->nullable();
            $table->timestamps();

            $table->unique(['therapist_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('therapist_leaves');
    }
};
