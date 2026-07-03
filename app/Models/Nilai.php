<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nilai extends Model
{
    protected $table = 'nilai';
    protected $fillable = ['siswa_id', 'jadwal_id', 'jenis', 'model', 'mingguan', 'uts', 'uas', 'skor', 'tanggal', 'id_bab', 'nama_bab', 'model_nilai', 'presensi'];

    public function siswa()
    {
        return $this->belongsTo(User::class, 'siswa_id');
    }

    public function jadwal()
    {
        return $this->belongsTo(Jadwal::class, 'jadwal_id');
    }
}