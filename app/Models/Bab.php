<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bab extends Model
{
    protected $table = 'bab';
    protected $fillable = ['nomor_bab', 'nama_bab', 'guru_id'];

    public function guru() {
        return $this->belongsTo(User::class, 'guru_id');
    }
}