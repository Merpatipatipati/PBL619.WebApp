<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMisiProgress extends Model
{
    use HasFactory;

    protected $table = 'user_misi_progress';
    protected $primaryKey = 'id_progress';

    protected $fillable = [
        'id_user',
        'id_misi',
        'hari_terpenuhi',
        'nilai_terakhir',
        'persentase',
        'status',
        'selesai_at',
        'claimed_at',
        'bisa_diklaim',
    ];

    protected $casts = [
        'selesai_at' => 'datetime',
        'claimed_at' => 'datetime',
        'bisa_diklaim' => 'boolean',
    ];

    public function misi()
    {
        return $this->belongsTo(Misi::class, 'id_misi', 'id_misi');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }
}
