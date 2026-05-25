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
        Schema::create('user_misi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('misi_id')->nullable()->constrained('misi', 'id_misi')->cascadeOnDelete();
            
            // Kolom untuk misi otomatis
            $table->string('nama_misi')->nullable();
            $table->text('deskripsi_misi')->nullable();
            $table->integer('poin')->default(0);
            $table->string('parameter_type')->nullable(); // pH, TDS, dll
            $table->double('target_value')->nullable();
            $table->string('trigger_condition')->nullable();
            $table->double('trigger_min_value')->nullable();
            $table->double('trigger_max_value')->nullable();
            
            // Flags
            $table->boolean('is_auto_generated')->default(false);
            $table->enum('status', ['aktif', 'selesai', 'expired'])->default('aktif');
            
            // Timestamps tambahan
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_misi');
    }
};
