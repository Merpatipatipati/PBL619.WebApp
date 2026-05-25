<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMisi extends Model
{
    use HasFactory;

    protected $table = 'user_misi';

    protected $fillable = [
        'user_id',
        'misi_id',
        'nama_misi',
        'deskripsi_misi',
        'poin',
        'parameter_type',
        'target_value',
        'trigger_condition',
        'trigger_min_value',
        'trigger_max_value',
        'is_auto_generated',
        'status',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'is_auto_generated' => 'boolean',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function misi()
    {
        return $this->belongsTo(Misi::class, 'misi_id', 'id_misi');
    }
}
