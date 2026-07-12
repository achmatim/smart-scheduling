<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Jadwal Pelajaran - SMP Manggala</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 30px;
            color: #333;
        }
        .header-print {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px double #111;
            padding-bottom: 12px;
        }
        .header-print h2 {
            margin: 0 0 6px 0;
            font-size: 20px;
            text-transform: uppercase;
        }
        .header-print p {
            margin: 0;
            font-size: 13px;
            color: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #111;
            padding: 8px 10px;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .no-col {
            text-align: center;
            width: 40px;
        }
        .badge-print {
            border: 1px solid #333;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            background-color: #f9f9f9;
            display: inline-block;
        }
        @media print {
            body {
                padding: 10px;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="header-print">
        <h2>Laporan Jadwal Pelajaran</h2>
        <h2>SMP Manggala</h2>
        <p>Tahun Ajaran: {{ $selectedYear->year }} - Semester {{ $selectedYear->semester }}</p>
        <p style="margin-top:4px; font-weight:bold; font-size:12px;">Urutan: Berdasarkan {{ $sortBy === 'teacher' ? 'Nama Guru' : ($sortBy === 'rombel' ? 'Kelas' : ($sortBy === 'subject' ? 'Mata Pelajaran' : 'Hari & Sesi')) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="no-col">No</th>
                <th>Nama Guru</th>
                <th>Kelas (Rombel)</th>
                <th>Mata Pelajaran</th>
                <th>Ruangan</th>
                <th>Hari</th>
                <th>Sesi / Jam</th>
            </tr>
        </thead>
        <tbody>
            @foreach($schedules as $index => $sch)
                @php
                    $pStart = $periodsMap[$sch->start_period] ?? null;
                    $pEnd = $periodsMap[$sch->end_period] ?? null;
                    
                    $timeStr = '';
                    if ($pStart && $pEnd) {
                        $timeStr = "({$pStart->start_time} - {$pEnd->end_time})";
                    }
                    
                    $sesiStr = ($sch->start_period == $sch->end_period) 
                        ? "Sesi {$sch->start_period}" 
                        : "Sesi {$sch->start_period}-{$sch->end_period}";
                @endphp
                <tr>
                    <td class="no-col">{{ $index + 1 }}</td>
                    <td><strong>{{ $sch->teacher->name }}</strong><br><span style="font-size:10px; color:#555;">NIP: {{ $sch->teacher->nip ?? '-' }}</span></td>
                    <td><span class="badge-print">{{ $sch->rombel->name }}</span></td>
                    <td>{{ $sch->subject->name }}</td>
                    <td>{{ $sch->room->code }} ({{ $sch->room->name }})</td>
                    <td>{{ $daysMap[$sch->day_of_week] ?? '-' }}</td>
                    <td><strong>{{ $sesiStr }}</strong> {{ $timeStr }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
