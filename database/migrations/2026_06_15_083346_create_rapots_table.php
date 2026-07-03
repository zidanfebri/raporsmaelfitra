<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('rapots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('siswa_id');
            $table->string('semester', 2);
            $table->string('tahun_pelajaran', 15);
            $table->text('kokurikuler')->nullable();
            $table->string('eskul_nama1')->nullable();
            $table->string('eskul_pred1', 2)->nullable();
            $table->text('eskul_desc1')->nullable();
            $table->string('eskul_nama2')->nullable();
            $table->string('eskul_pred2', 2)->nullable();
            $table->text('eskul_desc2')->nullable();
            $table->integer('abs_sakit')->default(0);
            $table->integer('abs_izin')->default(0);
            $table->integer('abs_alfa')->default(0);
            $table->text('catatan_wali')->nullable();
            $table->timestamps();

            // Set indeks relasi ke data master user/siswa
            $table->foreign('siswa_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('rapots');
    }
};