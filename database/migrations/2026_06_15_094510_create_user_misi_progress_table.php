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
        Schema::create('user_misi_progress', function (Blueprint $table) {
            $table->id('id_progress');
            $table->foreignId('id_user')->constrained('users', 'id')->cascadeOnDelete();
            $table->unsignedBigInteger('id_misi');
            $table->foreign('id_misi')->references('id_misi')->on('misi')->cascadeOnDelete();
            $table->integer('hari_terpenuhi')->default(0);
            $table->double('nilai_terakhir')->nullable();
            $table->integer('persentase')->default(0);
            $table->enum('status', ['aktif', 'selesai'])->default('aktif');
            $table->timestamp('selesai_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->boolean('bisa_diklaim')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_misi_progress');
    }
};
