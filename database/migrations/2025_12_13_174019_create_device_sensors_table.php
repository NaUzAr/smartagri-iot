<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('device_sensors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('sensor_name');      // Nama sensor (misal: temperature, humidity)
            $table->string('sensor_label');     // Label tampilan (misal: Suhu, Kelembaban)
            $table->string('unit')->nullable(); // Satuan (misal: °C, %, mm)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_sensors');
    }
};
