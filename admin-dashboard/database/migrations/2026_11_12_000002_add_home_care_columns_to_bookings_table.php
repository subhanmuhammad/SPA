<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('homecare_service_area_id')->nullable()->after('service_type')->constrained()->nullOnDelete();
            $table->string('area_name', 100)->nullable()->after('homecare_service_area_id');
            $table->unsignedInteger('travel_fee')->default(0)->after('total_price');
            $table->unsignedSmallInteger('travel_minutes')->default(0)->after('travel_fee');
            $table->string('location_url', 500)->nullable()->after('address_notes');
            $table->dateTime('departure_at')->nullable()->after('ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('homecare_service_area_id');
            $table->dropColumn(['area_name', 'travel_fee', 'travel_minutes', 'location_url', 'departure_at']);
        });
    }
};
