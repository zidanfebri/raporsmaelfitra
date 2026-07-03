<?php

namespace App\Imports;

use App\Models\Nilai;
use App\Models\Jadwal;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class NilaiHarianImport implements ToModel, WithHeadingRow
{
    protected $jadwal_id;
    protected $tanggal;
    public $parsedData = [];

    public function __construct($jadwal_id)
    {
        $this->jadwal_id = $jadwal_id;
        $this->tanggal = Jadwal::findOrFail($jadwal_id)->tanggal;
    }

    public function model(array $row)
    {
        // Ambil ID Siswa dari kolom pertama excel
        $siswa_id = $row['id_siswa_jangan_diubah'] ?? $row['id_siswa'] ?? null;
        if (!$siswa_id || !is_numeric($siswa_id)) {
            return null;
        }

        // Normalisasi data input harian dari baris baris excel
        $modelRaw = trim($row['model_nilai_tugaspostest_harianpostest_bulananulangan_harianpraktikum'] ?? $row['model_nilai'] ?? 'tugas');
        $model_nilai = in_array(strtolower($modelRaw), ['tugas', 'postest harian', 'postest bulanan', 'ulangan harian', 'praktikum']) ? strtolower($modelRaw) : 'tugas';
        
        $nomor_bab = isset($row['nomor_bab']) && is_numeric($row['nomor_bab']) ? (int)$row['nomor_bab'] : 1;
        $nama_bab = trim($row['nama_materi_bab'] ?? $row['nama_bab'] ?? 'Materi Bab');
        
        $presensiRaw = ucfirst(strtolower(trim($row['presensi_hadirsakitizinalfa'] ?? $row['presensi'] ?? 'Hadir')));
        $presensi = in_array($presensiRaw, ['Hadir', 'Sakit', 'Izin', 'Alfa']) ? $presensiRaw : 'Hadir';
        
        $nilaiRaw = $row['nilai_0_100'] ?? $row['nilai'] ?? null;
        $skor = (is_numeric($nilaiRaw) && $nilaiRaw !== '') ? (int)$nilaiRaw : null;

        // Eksekusi Auto-Save langsung ke database per baris excel
        $nilaiModel = Nilai::updateOrCreate(
            [
                'siswa_id'    => $siswa_id,
                'jadwal_id'   => $this->jadwal_id,
                'jenis'       => 'harian'
            ],
            [
                'model_nilai' => $model_nilai,
                'id_bab'      => $nomor_bab,
                'nama_bab'    => $nama_bab, 
                'mingguan'    => $skor, 
                'presensi'    => $presensi,
                'tanggal'     => $this->tanggal
            ]
        );

        // Simpan ke properti public agar bisa dioper balik sebagai respon JSON live preview
        $this->parsedData[] = [
            'siswa_id'    => (int)$siswa_id,
            'model_nilai' => $model_nilai,
            'nomor_bab'   => $nomor_bab,
            'nama_bab'    => $nama_bab,
            'presensi'    => $presensi,
            'nilai'       => $skor !== null ? $skor : ''
        ];

        return $nilaiModel;
    }
}