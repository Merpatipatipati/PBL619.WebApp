<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = \App\Models\Admin::create([
            'username' => 'admin',
            'email' => 'admin@admin.com',
            'password' => bcrypt('password'),
        ]);

        $user = \App\Models\User::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'poin' => 0,
            'coin' => 0,
            'level' => 1,
        ]);

        \App\Models\Misi::create([
            'id_admin' => $admin->id_admin,
            'nama_misi' => 'Misi Jaga pH Air',
            'deskripsi_misi' => 'Pastikan pH air berada di angka 6.0 - 7.0',
            'status_misi' => 'aktif',
            'tipe_misi' => 'harian',
            'poin' => 50,
            'tipe_trigger' => 'sensor_range',
            'kondisi_parameter' => 'ph',
            'nilai_min' => 6.0,
            'nilai_max' => 7.0,
            'durasi_hari' => 1,
        ]);

        \App\Models\Misi::create([
            'id_admin' => $admin->id_admin,
            'nama_misi' => 'Misi Suhu Optimal',
            'deskripsi_misi' => 'Jaga suhu air tetap dingin di bawah 25 derajat',
            'status_misi' => 'aktif',
            'tipe_misi' => 'harian',
            'poin' => 100,
            'tipe_trigger' => 'sensor_range',
            'kondisi_parameter' => 'temperature',
            'nilai_min' => null,
            'nilai_max' => 25.0,
            'durasi_hari' => 1,
        ]);
    }
}
