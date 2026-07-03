<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    protected $fillable = ['nama_kelas'];

    // Mendapatkan daftar siswa di kelas ini
    public function siswa()
    {
        return $this->hasMany(User::class, 'kelas_id')->where('role', 'siswa');
    }

    // Mendapatkan wali kelas (user dengan role walikelas yang terplot ke kelas ini)
    public function walikelas()
    {
        return $this->hasOne(User::class, 'kelas_id')->where('role', 'walikelas');
    }

    public function jadwals()
    {
        return $this->hasMany(Jadwal::class, 'kelas_id');
    }
}