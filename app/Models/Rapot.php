<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rapot extends Model
{
    use HasFactory;

    protected $table = 'rapots';
    protected $fillable = [
        'siswa_id', 'semester', 'tahun_pelajaran', 'kokurikuler', 
        'eskul_nama1', 'eskul_pred1', 'eskul_desc1', 
        'eskul_nama2', 'eskul_pred2', 'eskul_desc2', 
        'abs_sakit', 'abs_izin', 'abs_alfa', 'catatan_wali'
    ];

    public function siswa()
    {
        return $this->belongsTo(User::class, 'siswa_id');
    }
}