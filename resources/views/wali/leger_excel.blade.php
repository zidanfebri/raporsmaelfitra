<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .header-title { font-size: 16px; font-weight: bold; text-align: center; }
        th { background-color: #1a1a1a; color: #ffffff; font-weight: bold; border: 1px solid #000000; text-align: center; }
        td { border: 1px solid #000000; text-align: center; }
        .bg-primary { background-color: #cfe2ff; font-weight: bold; }
        .bg-info { background-color: #cff4fc; font-weight: bold; }
        .bg-danger { background-color: #f8d7da; font-weight: bold; }
        .bg-summary { background-color: #f8f9fa; font-weight: bold; }
    </style>
</head>
<body>

    <table>
        <tr>
            <td colspan="{{ count($allMapelsSorted) + 6 }}" class="header-title">
                BUKU LEGER NILAI AKHIR SISWA
            </td>
        </tr>
        <tr>
            <td colspan="{{ count($allMapelsSorted) + 6 }}" class="text-center">
                Kelas: {{ strtoupper($infoKelas->nama_kelas) }} | Semester: {{ $semester }} | Tahun Pelajaran: {{ $tp }}
            </td>
        </tr>
        <tr><td></td></tr>
    </table>

    <table border="1">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Lengkap Siswa</th>
                <th>NISN</th>
                @foreach($allMapelsSorted as $m)
                    <th>{{ $m->nama_mapel }}</th>
                @endforeach
                <th>Jumlah</th>
                <th>Rata-Rata</th>
                <th>Rangking</th>
            </tr>
        </thead>
        <tbody>
            @foreach($siswaLeger as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="text-left" style="text-transform: uppercase; font-weight: bold;">{{ $row['nama'] }}</td>
                <td>'{{ $row['nisn'] }}</td>
                @foreach($allMapelsSorted as $m)
                    <td>{{ $row['scores'][$m->id] ?? 0 }}</td>
                @endforeach
                <td class="bg-primary">{{ $row['total'] }}</td>
                <td class="bg-info">{{ number_format($row['rata_rata'], 2) }}</td>
                <td class="bg-danger">{{ $row['rangking'] }}</td>
            </tr>
            @endforeach

            <tr>
                <td colspan="3" class="text-right bg-summary">JUMLAH NILAI</td>
                @foreach($allMapelsSorted as $m)
                    <td class="bg-summary">{{ $statMapel[$m->id]['jumlah'] ?? 0 }}</td>
                @endforeach
                <td colspan="3" class="bg-summary"></td>
            </tr>
            <tr>
                <td colspan="3" class="text-right bg-summary" style="color: #0d6efd;">RATA-RATA MAPEL</td>
                @foreach($allMapelsSorted as $m)
                    <td class="bg-summary" style="color: #0d6efd;">{{ $statMapel[$m->id]['rata_rata'] ?? 0 }}</td>
                @endforeach
                <td colspan="3" class="bg-summary"></td>
            </tr>
            <tr>
                <td colspan="3" class="text-right bg-summary" style="color: #198754;">NILAI TERTINGGI</td>
                @foreach($allMapelsSorted as $m)
                    <td class="bg-summary" style="color: #198754;">{{ $statMapel[$m->id]['terbesar'] ?? 0 }}</td>
                @endforeach
                <td colspan="3" class="bg-summary"></td>
            </tr>
            <tr>
                <td colspan="3" class="text-right bg-summary" style="color: #dc3545;">NILAI TERENDAH</td>
                @foreach($allMapelsSorted as $m)
                    <td class="bg-summary" style="color: #dc3545;">{{ $statMapel[$m->id]['terkecil'] ?? 0 }}</td>
                @endforeach
                <td colspan="3" class="bg-summary"></td>
            </tr>
        </tbody>
    </table>

</body>
</html>