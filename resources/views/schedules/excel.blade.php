<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .title-block {
            margin-bottom: 25px;
            text-align: center;
        }
        .title-main {
            font-size: 20px;
            font-weight: bold;
            color: #1e1b4b;
        }
        .title-sub {
            font-size: 13px;
            color: #4b5563;
            margin-top: 4px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 10px;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #9ca3af;
            padding: 8px 12px;
            text-align: center;
            vertical-align: middle;
            font-size: 12px;
        }
        th {
            background-color: #1e1b4b;
            color: #ffffff;
            font-weight: bold;
        }
        .grid-time-header {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #111827;
        }
        .break-row {
            background-color: #fee2e2;
            color: #b91c1c;
            font-weight: bold;
            font-size: 12px;
        }
        .schedule-card-cell {
            background-color: #e0e7ff;
            color: #312e81;
            text-align: center;
        }
        .subject-title {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 3px;
        }
        .info-detail {
            font-size: 10px;
            color: #4f46e5;
        }
    </style>
</head>
<body>

    <div class="title-block">
        <div class="title-main">JADWAL PELAJARAN KESELURUHAN SMP MANGGALA</div>
        <div class="title-sub">
            Tahun Ajaran: {{ $activeYear->year }} - Semester {{ $activeYear->semester }}
        </div>
        <div class="title-sub" style="font-weight: bold; margin-top: 6px; font-size: 14px; color: #4338ca; text-transform: uppercase;">
            Diurutkan Berdasarkan: {{ $title }}
        </div>
    </div>

    @foreach($entities as $entity)
        @php
            $entityName = '';
            $entitySchedules = collect();
            
            if ($filterType === 'rombel') {
                $entityName = "KELAS: " . $entity->name;
                $entitySchedules = $schedules->where('rombel_id', $entity->id);
            } elseif ($filterType === 'teacher') {
                $entityName = "GURU: " . $entity->name;
                $entitySchedules = $schedules->where('teacher_id', $entity->id);
            } elseif ($filterType === 'room') {
                $entityName = "RUANGAN: " . $entity->code . " - " . $entity->name;
                $entitySchedules = $schedules->where('room_id', $entity->id);
            }
        @endphp

        <h3 style="margin-top: 30px; margin-bottom: 10px; color: #1e1b4b; text-align: left; font-size: 14px; border-bottom: 2px solid #1e1b4b; padding-bottom: 4px; text-transform: uppercase;">
            {{ $entityName }}
        </h3>

        <table>
            <thead>
                <tr>
                    <th width="120">Jam Ke- / Waktu</th>
                    <th width="180">Senin</th>
                    <th width="180">Selasa</th>
                    <th width="180">Rabu</th>
                    <th width="180">Kamis</th>
                    <th width="180">Jumat</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $skippedSlots = [];
                @endphp
                @foreach($periods as $pModel)
                    @php
                        $period = $pModel->period_number;
                        $startTime = $pModel->start_time;
                        $endTime = $pModel->end_time;
                        $isBreak = $pModel->is_break;
                    @endphp

                    @if($isBreak)
                        <tr class="break-row">
                            <td class="grid-time-header" style="background-color: #fee2e2;">
                                Jam {{ $period }}<br>
                                <span style="font-size:9px; font-weight:normal; color:#4b5563;">{{ $startTime }} - {{ $endTime }}</span>
                            </td>
                            <td colspan="5" align="center" style="background-color: #fee2e2;">
                                ☕ ISTIRAHAT / NON-AKTIF
                            </td>
                        </tr>
                    @else
                        <tr>
                            <td class="grid-time-header" style="background-color: #f3f4f6;">
                                Jam {{ $period }}<br>
                                <span style="font-size:9px; font-weight:normal; color:#4b5563;">{{ $startTime }} - {{ $endTime }}</span>
                            </td>

                            @for($day = 1; $day <= 5; $day++)
                                @if(isset($skippedSlots[$day][$period]))
                                    {{-- Skip this cell because it is covered by a rowspan --}}
                                @else
                                    @php
                                        $cellSchedule = $entitySchedules->first(function($sch) use ($day, $period) {
                                            return $sch->day_of_week == $day && $period >= $sch->start_period && $period <= $sch->end_period;
                                        });
                                    @endphp

                                    @if($cellSchedule)
                                        @php
                                            $duration = $cellSchedule->end_period - $cellSchedule->start_period + 1;
                                            for ($offset = 1; $offset < $duration; $offset++) {
                                                $skippedSlots[$day][$period + $offset] = true;
                                            }
                                        @endphp
                                        <td rowspan="{{ $duration }}" class="schedule-card-cell">
                                            <div class="subject-title">{{ $cellSchedule->subject->name }}</div>
                                            
                                            @if($filterType !== 'teacher')
                                                <div class="info-detail">👨‍🏫 {{ $cellSchedule->teacher->name }}</div>
                                            @endif
                                            
                                            @if($filterType !== 'rombel')
                                                <div class="info-detail">👥 Kelas: {{ $cellSchedule->rombel->name }}</div>
                                            @endif

                                            @if($filterType !== 'room')
                                                <div class="info-detail">📍 {{ $cellSchedule->room->code }}</div>
                                            @endif
                                        </td>
                                    @else
                                        <td>-</td>
                                    @endif
                                @endif
                            @endfor
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
        
        {{-- Excel page break --}}
        <div style="page-break-after: always;"></div>
    @endforeach

</body>
</html>
