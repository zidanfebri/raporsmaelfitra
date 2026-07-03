<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username', // ssw001, gr001, dsb
        'nama',
        'password',
        'role',     // admin, walikelas, guru, siswa
        'kelas_id', // foreign key untuk siswa dan walikelas
        'nisn',
        'status',
        'foto',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    // Relasi: Siswa memiliki banyak nilai
    public function nilai()
    {
        return $this->hasMany(Nilai::class, 'siswa_id');
    }

    // Relasi: Siswa atau Walikelas berada di satu kelas
    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    // Relasi: Guru memiliki banyak jadwal mengajar
    public function jadwal()
    {
        return $this->hasMany(Jadwal::class, 'guru_id');
    }

    public function mapels()
    {
        return $this->belongsToMany(Mapel::class, 'mapel_guru', 'user_id', 'mapel_id')->withPivot('kkm');
    }
}