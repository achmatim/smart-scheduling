@extends('layouts.app')

@section('page_title', 'Laporan Jadwal Pelajaran')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filter & Pengurutan Laporan</h3>
            @if($schedules->isNotEmpty())
                <div style="display:flex; gap:12px;">
                    <a href="{{ route('reports.print', ['academic_year_id' => $selectedYearId, 'sort_by' => $sortBy]) }}" target="_blank" class="btn btn-secondary" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:6px;">
                        📄 Cetak / PDF
                    </a>
                    <a href="{{ route('reports.excel', ['academic_year_id' => $selectedYearId, 'sort_by' => $sortBy]) }}" class="btn btn-secondary" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:6px;">
                        📊 Export ke Excel
                    </a>
                </div>
            @endif
        </div>
        <div class="card-body">
            <form action="{{ route('reports.index') }}" method="GET" id="reportFilterForm">
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:16px; align-items:flex-end;">
                    <div>
                        <label class="form-label" for="academic_year_id">Semester & Tahun Ajaran</label>
                        <select class="form-control" name="academic_year_id" id="academic_year_id" onchange="document.getElementById('reportFilterForm').submit()">
                            @foreach($academicYears as $ay)
                                <option value="{{ $ay->id }}" {{ $ay->id == $selectedYearId ? 'selected' : '' }}>
                                    Semester {{ $ay->semester }} - TA {{ $ay->year }} {{ $ay->is_active ? '(Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="sort_by">Urutkan Berdasarkan</label>
                        <select class="form-control" name="sort_by" id="sort_by" onchange="document.getElementById('reportFilterForm').submit()">
                            <option value="day" {{ $sortBy === 'day' ? 'selected' : '' }}>Hari & Jam Sesi</option>
                            <option value="teacher" {{ $sortBy === 'teacher' ? 'selected' : '' }}>Nama Guru</option>
                            <option value="rombel" {{ $sortBy === 'rombel' ? 'selected' : '' }}>Kelas (Rombel)</option>
                            <option value="subject" {{ $sortBy === 'subject' ? 'selected' : '' }}>Mata Pelajaran</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if(!$selectedYearId)
        <div class="card" style="text-align: center; padding: 40px;">
            <div class="card-body">
                <p style="color:var(--text-muted);">Silakan pilih Semester & Tahun Ajaran terlebih dahulu.</p>
            </div>
        </div>
    @elseif($schedules->isEmpty())
        <div class="card" style="text-align: center; padding: 60px 20px;">
            <div class="card-body">
                <svg width="64" height="64" fill="none" stroke="var(--text-muted)" stroke-width="1.5" viewBox="0 0 24 24" style="margin-bottom:16px;">
                    <path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3>Tidak Ada Data Jadwal</h3>
                <p style="color:var(--text-muted); margin-top:8px; font-size:14px;">
                    Belum ada jadwal yang disusun untuk Semester tersembut.
                </p>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-header" style="background-color:#f8fafc;">
                <h3 class="card-title" style="color:var(--primary);">
                    Laporan Jadwal Pelajaran - TA {{ $selectedYear->year }} ({{ $selectedYear->semester }})
                </h3>
            </div>
            <div class="card-body" style="padding:0;">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th width="60">No</th>
                                <th>Nama Guru</th>
                                <th>Kelas (Rombel)</th>
                                <th>Mata Pelajaran</th>
                                <th>Ruangan</th>
                                <th>Hari</th>
                                <th>Sesi / Jam Kerja</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($schedules as $index => $sch)
                                @php
                                    // Format time range
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
                                    <td>{{ $index + 1 }}</td>
                                    <td><strong>{{ $sch->teacher->name }}</strong><br><span style="font-size:11px; color:var(--text-muted);">NIP: {{ $sch->teacher->nip ?? '-' }}</span></td>
                                    <td><span class="badge badge-primary">{{ $sch->rombel->name }}</span></td>
                                    <td>{{ $sch->subject->name }}</td>
                                    <td><strong>{{ $sch->room->code }}</strong> ({{ $sch->room->name }})</td>
                                    <td><strong>{{ $daysMap[$sch->day_of_week] ?? '-' }}</strong></td>
                                    <td>
                                        <span class="badge badge-success" style="background-color: var(--success-light); color:#065f46; font-size:12px; padding: 6px 10px;">
                                            {{ $sesiStr }} {{ $timeStr }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
