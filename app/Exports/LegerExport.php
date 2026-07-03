<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LegerExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $siswaLeger;
    protected $allMapelsSorted;

    public function __construct($siswaLeger, $allMapelsSorted)
    {
        $this->siswaLeger = collect($siswaLeger);
        $this->allMapelsSorted = $allMapelsSorted;
    }

    /**
     * Kembalikan data koleksi siswa
     */
    public function collection()
    {
        return $this->siswaLeger;
    }

    /**
     * Saring judul header kolom Excel
     */
    public function headings(): array
    {
        $headers = ['No', 'Nama Lengkap Siswa', 'NIS'];
        
        foreach ($this->allMapelsSorted as $m) {
            $headers[] = $m->nama_mapel;
        }

        $headers[] = 'Jumlah';
        $headers[] = 'Rata-Rata';
        $headers[] = 'Rangking';

        return $headers;
    }

    /**
     * Mapping baris data siswa satu per satu
     */
    public function map($row): array
    {
        static $no = 1;
        
        $data = [
            $no++,
            $row['nama'],
            $row['nisn']
        ];

        // Masukkan skor nilai akhir mapel sesuai urutan header
        foreach ($this->allMapelsSorted as $m) {
            $data[] = isset($row['scores'][$m->id]) && $row['scores'][$m->id] !== '-' ? $row['scores'][$m->id] : 0;
        }

        $data[] = $row['total'];
        $data[] = $row['rata_rata'];
        $data[] = $row['rangking'];

        return $data;
    }

    /**
     * Desain manipulasi style sheet, border, warna, dan rumus statistik bawah
     */
    public function styles(Worksheet $sheet)
    {
        $totalSiswa = $this->siswaLeger->count();
        $totalMapel = $this->allMapelsSorted->count();
        
        // --- FIXED: Menambahkan tanda $ pada variabel totalMapel di bawah ini ---
        $kolomAwalMapel = 'D';
        $kolomAkhirMapel = chr(64 + 3 + $totalMapel); // Menghitung kolom mapel terakhir
        $kolomJumlah = chr(64 + 3 + $totalMapel + 1);
        $kolomRata = chr(64 + 3 + $totalMapel + 2);
        $kolomRangking = chr(64 + 3 + $totalMapel + 3);

        $barisTerakhirData = $totalSiswa + 1; // +1 karena ada baris header

        // Baris rumus statistik di bawah data siswa
        $barisJumlah = $barisTerakhirData + 1;
        $barisRataRata = $barisTerakhirData + 2;
        $barisTerbesar = $barisTerakhirData + 3;
        $barisTerkecil = $barisTerakhirData + 4;

        // --- TULIS FORMULA RUMUS STATISTIK EXCEL PADA TIAP MAPEL ---
        $sheet->setCellValue("B{$barisJumlah}", 'JUMLAH');
        $sheet->setCellValue("B{$barisRataRata}", 'RATA-RATA');
        $sheet->setCellValue("B{$barisTerbesar}", 'TERBESAR');
        $sheet->setCellValue("B{$barisTerkecil}", 'TERKECIL');

        // Loop rumus horizontal berjalannya kolom mapel (Dari kolom D ke kanan)
        for ($i = 0; $i < $totalMapel; $i++) {
            $hurufKolom = chr(68 + $i); // 68 adalah ASCII untuk huruf 'D'
            
            $sheet->setCellValue("{$hurufKolom}{$barisJumlah}", "=SUM({$hurufKolom}2:{$hurufKolom}{$barisTerakhirData})");
            $sheet->setCellValue("{$hurufKolom}{$barisRataRata}", "=AVERAGE({$hurufKolom}2:{$hurufKolom}{$barisTerakhirData})");
            $sheet->setCellValue("{$hurufKolom}{$barisTerbesar}", "=MAX({$hurufKolom}2:{$hurufKolom}{$barisTerakhirData})");
            $sheet->setCellValue("{$hurufKolom}{$barisTerkecil}", "=MIN({$hurufKolom}2:{$hurufKolom}{$barisTerakhirData})");
        }

        // --- DESAIN DEKORASI WARNA & BORDER ---
        // Style Header (Baris 1)
        $sheet->getStyle("A1:{$kolomRangking}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Calibri'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '212529']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
        ]);

        // Border untuk seluruh isi tabel data siswa hingga footer statistik
        $sheet->getStyle("A1:{$kolomRangking}{$barisTerkecil}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Bold komponen hasil kalkulasi rangking kanan dan footer bawah
        $sheet->getStyle("{$kolomJumlah}2:{$kolomRangking}{$barisTerakhirData}")->getFont()->setBold(true);
        $sheet->getStyle("A{$barisJumlah}:{$kolomRangking}{$barisTerkecil}")->getFont()->setBold(true);

        // Beri warna background abu-abu terang pada baris rumus statistik bawah
        $sheet->getStyle("A{$barisJumlah}:{$kolomRangking}{$barisTerkecil}")->getFill()->applyFromArray([
            'fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F2F2']
        ]);

        // Format angka desimal untuk baris Rata-rata agar bersih (.00)
        if ($totalMapel > 0) {
            $sheet->getStyle("{$kolomAwalMapel}{$barisRataRata}:{$kolomAkhirMapel}{$barisRataRata}")->getNumberFormat()->setFormatCode('0.00');
        }

        return [];
    }
}