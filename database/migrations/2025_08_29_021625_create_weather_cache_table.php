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
        Schema::create('weather_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->string('type'); // current, forecast, etc.
            $table->date('date')->nullable(); // For forecast data
            $table->json('data');
            $table->timestamp('expires_at');
            $table->timestamps();

            // Create a unique index for location, type, and date
            $table->unique(['location_id', 'type', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weather_cache');
    }
};
