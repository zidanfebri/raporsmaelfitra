<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mapel extends Model
{
    protected $table = 'mapel';
    protected $fillable = ['nama_mapel'];

    public function gurus()
    {
        // Menyuntikkan withPivot('kkm') agar data KKM dapat diakses secara instan
        return $this->belongsToMany(User::class, 'mapel_guru', 'mapel_id', 'user_id')->withPivot('kkm');
    }

    public function jadwals()
    {
        return $this->hasMany(Jadwal::class, 'mapel_id');
    }
}