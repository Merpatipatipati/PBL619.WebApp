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
        Schema::table('misi', function (Blueprint $table) {
            $table->string('tipe_trigger')->default('manual'); // 'manual', 'sensor_range', 'streak'
            $table->string('kondisi_parameter')->nullable(); // 'ph', 'tds', 'suhu', dsb.
            $table->double('nilai_min')->nullable();
            $table->double('nilai_max')->nullable();
            $table->integer('durasi_hari')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('misi', function (Blueprint $table) {
            $table->dropColumn(['tipe_trigger', 'kondisi_parameter', 'nilai_min', 'nilai_max', 'durasi_hari']);
        });
    }
};
