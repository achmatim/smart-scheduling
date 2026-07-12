<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .header-excel {
            text-align: center;
            margin-bottom: 20px;
        }
        .title-main {
            font-size: 16px;
            font-weight: bold;
            color: #1e1b4b;
        }
        .title-sub {
            font-size: 12px;
            color: #4b5563;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #9ca3af;
            padding: 8px 10px;
            font-size: 11px;
            vertical-align: middle;
        }
        th {
            background-color: #f3f4f6;
            font-weight: bold;
            text-align: center;
        }
        .no-col {
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="header-excel">
        <div class="title-main">LAPORAN JADWAL PELAJARAN SMP MANGGALA</div>
        <div class="title-sub">Tahun Ajaran: {{ $selectedYear->year }} - Semester {{ $selectedYear->semester }}</div>
        <div class="title-sub" style="font-weight:bold;">Urutan: Berdasarkan {{ $sortBy === 'teacher' ? 'Nama Guru' : ($sortBy === 'rombel' ? 'Kelas' : ($sortBy === 'subject' ? 'Mata Pelajaran' : 'Hari & Sesi')) }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="40">No</th>
                <th width="220">Nama Guru</th>
                <th width="120">Kelas (Rombel)</th>
                <th width="180">Mata Pelajaran</th>
                <th width="150">Ruangan</th>
                <th width="100">Hari</th>
                <th width="180">Sesi / Jam</th>
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
                    <td class="no-col" align="center">{{ $index + 1 }}</td>
                    <td><strong>{{ $sch->teacher->name }}</strong><br>NIP: {{ $sch->teacher->nip ?? '-' }}</td>
                    <td align="center">{{ $sch->rombel->name }}</td>
                    <td>{{ $sch->subject->name }}</td>
                    <td>{{ $sch->room->code }} ({{ $sch->room->name }})</td>
                    <td>{{ $daysMap[$sch->day_of_week] ?? '-' }}</td>
                    <td align="center">{{ $sesiStr }} {{ $timeStr }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
