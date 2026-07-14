@extends('layouts.app')

@section('page_title', 'Jadwal Pelajaran')

@section('styles')
<style>
    @media print {
        body {
            background: white !important;
            color: black !important;
        }
        .sidebar, .topbar, .card-header, .schedule-header-actions, .alert, form, .btn {
            display: none !important;
        }
        .main-wrapper {
            margin-left: 0 !important;
            width: 100% !important;
        }
        .content-container {
            padding: 0 !important;
        }
        .schedule-grid-container {
            border: none !important;
            padding: 0 !important;
            box-shadow: none !important;
        }
        .schedule-grid {
            grid-template-columns: 80px repeat(5, 1fr) !important;
            border: 2px solid #000 !important;
        }
        .schedule-grid-header {
            background-color: #eee !important;
            border-bottom: 2px solid #000 !important;
            border-right: 1px solid #000 !important;
        }
        .schedule-grid-time {
            background-color: #eee !important;
            border-bottom: 1px solid #000 !important;
            border-right: 2px solid #000 !important;
        }
        .schedule-grid-cell {
            border-bottom: 1px solid #000 !important;
            border-right: 1px solid #000 !important;
            background: white !important;
            min-height: 80px !important;
        }
        .schedule-card {
            background: none !important;
            border-left: 3px solid #000 !important;
            padding: 4px !important;
            box-shadow: none !important;
        }
        .schedule-card-subject {
            color: black !important;
        }
        .schedule-card-room {
            background: none !important;
            border: 1px solid #000 !important;
            color: black !important;
        }
        .print-title {
            display: block !important;
            margin-bottom: 20px;
            text-align: center;
        }
    }
    .print-title {
        display: none;
    }
</style>
@endsection

@section('content')
    <!-- Print Only Header -->
    <div class="print-title">
        <h2>SMP MANGGALA</h2>
        <h3>Jadwal Pelajaran - Semester {{ $activeYear->semester }} TA {{ $activeYear->year }}</h3>
        @if($filterType === 'rombel')
            <p>Rombongan Belajar: Kelas {{ $rombels->where('id', $filterId)->first()->name ?? '' }}</p>
        @elseif($filterType === 'teacher')
            <p>Guru Pengampu: {{ $teachers->where('id', $filterId)->first()->name ?? '' }}</p>
        @elseif($filterType === 'room')
            <p>Ruangan: {{ $rooms->where('id', $filterId)->first()->name ?? '' }}</p>
        @endif
        <hr style="margin-top: 10px; border: 1px solid black;">
    </div>

    <!-- Filter Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filter Penampil Jadwal</h3>
            <div style="display:flex; gap:12px;">
                @if(!$isLocked)
                    <button class="btn btn-primary" onclick="openGenModal()">⚙️ Generate Jadwal Otomatis</button>
                    @if($schedules->isNotEmpty())
                        <form action="{{ route('schedules.lock') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin jadwal ini sudah benar? Setelah dikunci, jadwal tidak bisa di-regenerate.');">
                            @csrf
                            <button type="submit" class="btn btn-success">✓ Verifikasi & Kunci Jadwal</button>
                        </form>
                    @endif
                @else
                    <div style="display:flex; align-items:center; gap:12px;">
                        <span class="badge badge-danger" style="font-size:13px; padding:10px 16px;">🔒 Jadwal Terkunci (Locked)</span>
                        <form action="{{ route('schedules.unlock') }}" method="POST" onsubmit="return confirm('Membuka kunci akan memungkinkan jadwal disusun ulang / di-generate ulang. Lanjutkan?');">
                            @csrf
                            <button type="submit" class="btn btn-secondary">🔓 Buka Kunci</button>
                        </form>
                    </div>
                @endif
                @if($schedules->isNotEmpty())
                    <a href="{{ route('schedules.export', ['filter_type' => $filterType]) }}" class="btn btn-secondary" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                        📊 Export ke Excel
                    </a>
                    <button class="btn btn-secondary" onclick="window.print()">🖨️ Cetak Jadwal</button>
                @endif
            </div>
        </div>
        <div class="card-body">
            <form action="{{ route('schedules.index') }}" method="GET" id="searchForm">
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px; align-items:flex-end;">
                    <div>
                        <label class="form-label" for="filter_type">Tipe Penampil</label>
                        <select class="form-control" name="filter_type" id="filter_type" onchange="updateFilterDropdowns()">
                            <option value="rombel" {{ $filterType === 'rombel' ? 'selected' : '' }}>Berdasarkan Kelas (Rombel)</option>
                            <option value="teacher" {{ $filterType === 'teacher' ? 'selected' : '' }}>Berdasarkan Guru</option>
                            <option value="room" {{ $filterType === 'room' ? 'selected' : '' }}>Berdasarkan Ruangan</option>
                        </select>
                    </div>
                    
                    <!-- Rombel Dropdown -->
                    <div id="div-rombel" class="filter-dropdown">
                        <label class="form-label" for="filter_id_rombel">Pilih Kelas</label>
                        <select class="form-control filter-select" id="filter_id_rombel" onchange="submitSearch('rombel')">
                            @foreach($rombels as $r)
                                <option value="{{ $r->id }}" {{ ($filterType === 'rombel' && $r->id == $filterId) ? 'selected' : '' }}>{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Teacher Dropdown -->
                    <div id="div-teacher" class="filter-dropdown" style="display:none;">
                        <label class="form-label" for="filter_id_teacher">Pilih Guru</label>
                        <select class="form-control filter-select" id="filter_id_teacher" onchange="submitSearch('teacher')">
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}" {{ ($filterType === 'teacher' && $t->id == $filterId) ? 'selected' : '' }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Room Dropdown -->
                    <div id="div-room" class="filter-dropdown" style="display:none;">
                        <label class="form-label" for="filter_id_room">Pilih Ruangan</label>
                        <select class="form-control filter-select" id="filter_id_room" onchange="submitSearch('room')">
                            @foreach($rooms as $rm)
                                <option value="{{ $rm->id }}" {{ ($filterType === 'room' && $rm->id == $filterId) ? 'selected' : '' }}>{{ $rm->code }} - {{ $rm->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <input type="hidden" name="filter_id" id="real_filter_id" value="{{ $filterId }}">
                </div>
            </form>
        </div>
    </div>

    <!-- Schedule Matrix -->
    @if($schedules->isEmpty())
        <div class="card" style="text-align: center; padding: 60px 20px;">
            <div class="card-body">
                <svg width="64" height="64" fill="none" stroke="var(--text-muted)" stroke-width="1.5" viewBox="0 0 24 24" style="margin-bottom:16px;">
                    <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <h3>Jadwal Pelajaran Kosong</h3>
                <p style="color:var(--text-muted); margin-bottom: 24px; max-width: 400px; margin-left:auto; margin-right:auto; font-size:14px; margin-top:8px;">
                    Belum ada jadwal yang disusun untuk Semester ini. Tekan tombol **Generate Jadwal Otomatis** untuk membuat jadwal baru menggunakan AI Engine.
                </p>
                @if(!$isLocked)
                    <button class="btn btn-primary" onclick="openGenModal()">⚙️ Susun Jadwal Sekarang</button>
                @endif
            </div>
        </div>
    @else
        @if($totalAllocatedJp > $weeklyCapacity)
            <div class="card" style="border: 2px solid #ea580c; background-color: #fff7ed; margin-bottom: 24px;">
                <div class="card-header" style="background-color: #ffedd5; border-bottom: 1px solid #fed7aa; color: #c2410c; font-weight: 700; display:flex; align-items:center; gap:8px;">
                    🚨 Kapasitas Jadwal Sekolah Melebihi Batas (Kebutuhan: {{ $totalAllocatedJp }} JP > Kapasitas Ketersediaan: {{ $weeklyCapacity }} JP)
                </div>
                <div class="card-body" style="padding: 16px;">
                    <p style="font-size: 13px; color: #7c2d12; margin-bottom: 12px; font-weight: 600;">
                        Jadwal secara matematis <strong>mustahil disusun dengan 0 bentrok</strong> karena total kebutuhan beban jam pelajaran ({{ $totalAllocatedJp }} JP) melebihi batas slot waktu yang bersedia dari ketersediaan guru ({{ $weeklyCapacity }} JP). 
                    </p>
                    <p style="font-size: 13px; color: #7c2d12; margin-bottom: 12px;">
                        Berikut adalah daftar slot waktu bottleneck (di mana guru yang bersedia kurang dari {{ $rombels->count() }} orang). Silakan buka status ketersediaan waktu mengajar mereka untuk mengatasi bottleneck ini:
                    </p>
                    <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #9a3412; display: flex; flex-direction: column; gap: 6px; max-height: 180px; overflow-y: auto; margin-bottom: 12px;">
                        @foreach($bottlenecks as $b)
                            @if($b['day'] === 'Jumat' && $b['period'] >= 9)
                                <li>Hari <strong>{{ $b['day'] }} Jam ke-{{ $b['period'] }}</strong>: Waktu tutup sekolah (seluruh guru tidak bersedia).</li>
                            @else
                                <li>Hari <strong>{{ $b['day'] }} Jam ke-{{ $b['period'] }}</strong>: Hanya {{ $b['available'] }} guru bersedia (butuh {{ $b['needed'] }}). Guru tidak bersedia: <em style="color:#b45309;">{{ implode(', ', $b['unavailable_teachers']) }}</em></li>
                            @endif
                        @endforeach
                    </ul>
                    <p style="font-size: 12px; color: #9a3412; font-style: italic; margin: 0;">
                        Tips: Buka status bersedia mengajar untuk guru-guru di atas pada hari Rabu/Kamis Jam ke-9 & 10 agar AI dapat menyusun jadwal dengan sempurna.
                    </p>
                </div>
            </div>
        @elseif(!empty($clashes))
            <div class="card" style="border: 2px solid var(--danger); background-color: #fef2f2; margin-bottom: 24px;">
                <div class="card-header" style="background-color: #fee2e2; border-bottom: 1px solid #fecaca; color: #b91c1c; font-weight: 700; display:flex; align-items:center; gap:8px;">
                    ⚠️ Terdeteksi {{ count($clashes) }} Bentrok Waktu (Clashes) pada Jadwal Aktif
                </div>
                <div class="card-body" style="padding: 16px;">
                    <p style="font-size: 13px; color: #7f1d1d; margin-bottom: 12px; font-weight: 500;">
                        Bentrok terjadi karena batasan ketersediaan guru atau keterbatasan ruangan yang saling bertabrakan. Silakan sesuaikan Ketersediaan Guru atau Alokasi Mengajar, lalu jalankan optimasi ulang dengan Populasi & Generasi yang lebih tinggi.
                    </p>
                    <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #991b1b; display: flex; flex-direction: column; gap: 6px; max-height: 180px; overflow-y: auto;">
                        @foreach($clashes as $clash)
                            <li>{!! $clash !!}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- Grid View -->
        <div class="schedule-grid-container">
            <h4 style="margin-bottom: 20px; font-weight: 600; color: var(--primary);">
                @if($filterType === 'rombel')
                    Tabel Jadwal Kelas: <strong>{{ $rombels->where('id', $filterId)->first()->name ?? '' }}</strong>
                @elseif($filterType === 'teacher')
                    Tabel Jadwal Guru: <strong>{{ $teachers->where('id', $filterId)->first()->name ?? '' }}</strong>
                @elseif($filterType === 'room')
                    Tabel Jadwal Ruangan: <strong>{{ $rooms->where('id', $filterId)->first()->name ?? '' }}</strong>
                @endif
            </h4>
            
            <div class="schedule-grid">
                <!-- Row Header: Days of the week -->
                <div class="schedule-grid-header">Jam Ke-</div>
                <div class="schedule-grid-header">Senin</div>
                <div class="schedule-grid-header">Selasa</div>
                <div class="schedule-grid-header">Rabu</div>
                <div class="schedule-grid-header">Kamis</div>
                <div class="schedule-grid-header">Jumat</div>

                <!-- Timetable rows (8 periods) -->
                <!-- Timetable rows (Dynamic periods) -->
                @foreach($periods as $pModel)
                    @php
                        $period = $pModel->period_number;
                        $startTime = $pModel->start_time;
                        $endTime = $pModel->end_time;
                        $isBreak = $pModel->is_break;
                    @endphp

                    <!-- Period Number / Time Info -->
                    <div class="schedule-grid-time" style="{{ $isBreak ? 'background-color: #fee2e2; border-bottom: 2px solid var(--border-color);' : '' }}">
                        <strong>Jam {{ $period }}</strong>
                        <span style="font-size:11px; color:var(--text-muted); margin-top:2px;">
                            {{ $startTime }} - {{ $endTime }}
                        </span>
                    </div>

                    @if($isBreak)
                        <!-- Render a full width break row span 5 -->
                        <div class="schedule-grid-cell" style="grid-column: span 5; background-color: #fef2f2; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #b91c1c; border-bottom: 2px solid var(--border-color); min-height: 50px;">
                            ☕ ISTIRAHAT / NON-AKTIF ({{ $startTime }} - {{ $endTime }})
                        </div>
                    @else
                        <!-- Days cells -->
                        @for($day = 1; $day <= 5; $day++)
                            <div class="schedule-grid-cell">
                                <!-- Find schedule overlapping this period and day -->
                                @php
                                    $cellSchedule = $schedules->first(function($sch) use ($day, $period) {
                                        return $sch->day_of_week == $day && $period >= $sch->start_period && $period <= $sch->end_period;
                                    });
                                @endphp

                                @if($cellSchedule)
                                    <!-- Only display details on the start period of the session to prevent repeating cards -->
                                    @if($cellSchedule->start_period == $period)
                                        @php
                                            $duration = $cellSchedule->end_period - $cellSchedule->start_period + 1;
                                            $cardStyle = "height: calc(" . ($duration * 100) . "% - 8px); position: absolute; top: 4px; left: 4px; right: 4px; z-index: 10;";
                                        @endphp
                                        
                                        <div class="schedule-card" style="{{ $cardStyle }}">
                                            <div>
                                                <div class="schedule-card-subject">{{ $cellSchedule->subject->name }}</div>
                                                
                                                @if($filterType !== 'teacher')
                                                    <div class="schedule-card-teacher" style="font-size: 11px;">
                                                        👨‍🏫 {{ $cellSchedule->teacher->name }}
                                                    </div>
                                                @endif

                                                @if($filterType !== 'rombel')
                                                    <div class="schedule-card-teacher" style="font-size: 11px; font-weight: 500;">
                                                        👥 Kelas: {{ $cellSchedule->rombel->name }}
                                                    </div>
                                                @endif
                                            </div>
                                            
                                            @if($filterType !== 'room')
                                                <div class="schedule-card-room">
                                                    📍 {{ $cellSchedule->room->code }}
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                @endif
                            </div>
                        @endfor
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    <!-- Generator Config / Progress Modal -->
    <div class="modal-backdrop" id="genModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="card-title">Konfigurasi AI Scheduling Engine</h3>
                <button onclick="closeGenModal()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
            </div>
            <div class="modal-body" id="genFormBody">
                <div class="alert alert-success" style="background-color: var(--primary-light); color: var(--primary); border:none; margin-bottom: 20px;">
                    Jadwal akan disusun otomatis berdasarkan batasan (constraints) dan data alokasi mengajar. Proses ini berjalan asinkron.
                </div>
                <div class="form-group">
                    <label class="form-label" for="pop_size">Ukuran Populasi (Population Size)</label>
                    <select class="form-control" id="pop_size">
                        <option value="80">80 (Standard)</option>
                        <option value="120" selected>120 (Direkomendasikan)</option>
                        <option value="180">180 (Sangat Akurat)</option>
                        <option value="250">250 (Paling Akurat)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="max_generations">Jumlah Generasi Maksimal</label>
                    <select class="form-control" id="max_generations">
                        <option value="100">100 Generasi (Standard)</option>
                        <option value="150">150 Generasi</option>
                        <option value="250" selected>250 Generasi (Direkomendasikan)</option>
                        <option value="400">400 Generasi (Untuk data padat)</option>
                        <option value="600">600 Generasi (Pencarian Intensif)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer" id="genFormFooter">
                <button type="button" class="btn btn-secondary" onclick="closeGenModal()">Batal</button>
                <button type="button" class="btn btn-primary" onclick="startGeneration()">Mulai Susun Jadwal</button>
            </div>

            <!-- Active progress tracker body -->
            <div class="modal-body" id="genProgressBody" style="display:none; text-align:center;">
                <div class="progress-title" style="margin-bottom:12px;">Menyusun Jadwal Sekolah...</div>
                <p id="progress-status-text" style="font-size:14px; color:var(--text-muted); margin-bottom: 20px;">Mengantrekan tugas...</p>
                
                <div class="progress-bar-container" style="margin-bottom: 24px;">
                    <div class="progress-bar-fill" id="progress-bar-fill"></div>
                </div>

                <div class="progress-stats" style="margin-bottom: 20px;">
                    <div class="progress-stat-item">
                        <span class="progress-stat-label">Generasi</span>
                        <span class="progress-stat-value" id="stat-generation">0 / 0</span>
                    </div>
                    <div class="progress-stat-item">
                        <span class="progress-stat-label">Bentrok (Clashes)</span>
                        <span class="progress-stat-value" id="stat-conflicts" style="color:var(--danger);">999</span>
                    </div>
                    <div class="progress-stat-item" style="grid-column: span 2;">
                        <span class="progress-stat-label">Nilai Kelayakan (Fitness)</span>
                        <span class="progress-stat-value" id="stat-fitness" style="color:var(--success);">0.0000</span>
                    </div>
                </div>

                <div class="progress-log" id="progress-log">
                    [SYSTEM] AI Engine siap dijalankan...
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Update filter display based on selected filter type
        function updateFilterDropdowns() {
            const filterType = document.getElementById('filter_type').value;
            
            // Hide all
            document.querySelectorAll('.filter-dropdown').forEach(el => el.style.display = 'none');
            
            // Show selected
            const activeDiv = document.getElementById('div-' + filterType);
            if (activeDiv) {
                activeDiv.style.display = 'block';
                
                // Get the value of the active select and set it to the hidden input
                const selectEl = activeDiv.querySelector('.filter-select');
                document.getElementById('real_filter_id').value = selectEl.value;
            }
        }

        // Handle dropdown selection change
        function submitSearch(type) {
            const selectEl = document.querySelector('#div-' + type + ' .filter-select');
            document.getElementById('real_filter_id').value = selectEl.value;
            document.getElementById('searchForm').submit();
        }

        // Initialize display state
        window.addEventListener('DOMContentLoaded', function() {
            updateFilterDropdowns();
        });

        // Modal Functions
        function openGenModal() {
            document.getElementById('genModal').classList.add('show');
        }
        function closeGenModal() {
            document.getElementById('genModal').classList.remove('show');
        }

        // AJAX Scheduling Logic
        let pollInterval = null;

        function startGeneration() {
            const popSize = document.getElementById('pop_size').value;
            const maxGenerations = document.getElementById('max_generations').value;

            // Transition UI to progress view
            document.getElementById('genFormBody').style.display = 'none';
            document.getElementById('genFormFooter').style.display = 'none';
            document.getElementById('genProgressBody').style.display = 'block';

            // Send POST request to start process
            fetch("{{ route('schedules.generate') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    pop_size: popSize,
                    max_generations: maxGenerations
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    resetModalUI();
                    return;
                }

                logToConsole("[INFO] Pekerjaan berhasil dimasukkan dalam antrian database. Memulai optimasi...");
                // Start polling progress
                pollProgress(data.job_id);
            })
            .catch(error => {
                console.error("Error:", error);
                alert("Terjadi kesalahan koneksi server saat mencoba memulai penjadwalan.");
                resetModalUI();
            });
        }

        function resetModalUI() {
            document.getElementById('genFormBody').style.display = 'block';
            document.getElementById('genFormFooter').style.display = 'flex';
            document.getElementById('genProgressBody').style.display = 'none';
            if (pollInterval) clearInterval(pollInterval);
        }

        function pollProgress(jobId) {
            pollInterval = setInterval(function() {
                fetch("/schedules/progress/" + jobId)
                .then(response => response.json())
                .then(job => {
                    if (job.error) {
                        logToConsole("[ERROR] " + job.error);
                        clearInterval(pollInterval);
                        return;
                    }

                    // Update UI
                    document.getElementById('progress-bar-fill').style.width = job.progress + '%';
                    document.getElementById('progress-status-text').innerText = "Status: " + job.status.toUpperCase() + " (" + job.progress + "%)";
                    document.getElementById('stat-generation').innerText = job.current_generation + " / " + job.max_generations;
                    document.getElementById('stat-conflicts').innerText = job.conflicts;
                    document.getElementById('stat-fitness').innerText = job.best_fitness;

                    if (job.status === 'running') {
                        logToConsole("[GA-ENGINE] Generasi " + job.current_generation + "/" + job.max_generations + ". Fitness Terbaik: " + job.best_fitness + ", Konflik: " + job.conflicts);
                    }

                    if (job.status === 'completed') {
                        logToConsole("[SUCCESS] Penjadwalan selesai dengan sukses! Merender hasil...");
                        clearInterval(pollInterval);
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else if (job.status === 'failed') {
                        logToConsole("[ERROR] Gagal: " + job.error_message);
                        document.getElementById('progress-status-text').innerText = "Status: GAGAL";
                        document.getElementById('progress-status-text').style.color = "var(--danger)";
                        clearInterval(pollInterval);
                        
                        // Add reset button
                        const footer = document.createElement('div');
                        footer.className = 'modal-footer';
                        footer.innerHTML = '<button class="btn btn-secondary" onclick="resetModalUI()">Tutup</button>';
                        document.getElementById('genProgressBody').appendChild(footer);
                    }
                })
                .catch(error => {
                    console.error("Polling error:", error);
                });
            }, 600);
        }

        function logToConsole(message) {
            const consoleBox = document.getElementById('progress-log');
            consoleBox.innerHTML += "<br>" + message;
            consoleBox.scrollTop = consoleBox.scrollHeight;
        }
    </script>
@endsection
