<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Misi extends Model
{
    use HasFactory;

    protected $table = 'misi';

    protected $primaryKey = 'id_misi';

    protected $fillable = [
        'id_admin',
        'nama_misi',
        'deskripsi_misi',
        'status_misi',
        'tipe_misi',
        'poin',
        'tipe_trigger',
        'kondisi_parameter',
        'nilai_min',
        'nilai_max',
        'durasi_hari',
    ];


    public function admin()
    {
        return $this->belongsTo(Admin::class, 'id_admin');
    }
}