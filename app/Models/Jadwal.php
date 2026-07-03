<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jadwal extends Model
{
    protected $table = 'jadwal';
    protected $fillable = ['guru_id', 'mapel_id', 'kelas_id', 'hari', 'jam_mulai', 'jam_akhir', 'tanggal', 'tipe', 'semester', 'tahun_pelajaran'];

    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function mapel()
    {
        return $this->belongsTo(Mapel::class, 'mapel_id');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function nilais()
    {
        return $this->hasMany(Nilai::class, 'jadwal_id');
    }
}