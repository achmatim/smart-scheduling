@extends('layouts.app')

@section('page_title', 'Dashboard')

@section('content')
    <!-- Statistics Grid -->
    <div class="grid-stats">
        <div class="card-stat">
            <span class="stat-title">Total Guru</span>
            <span class="stat-value">{{ $stats['teachers'] }}</span>
        </div>
        <div class="card-stat">
            <span class="stat-title">Mata Pelajaran</span>
            <span class="stat-value">{{ $stats['subjects'] }}</span>
        </div>
        <div class="card-stat">
            <span class="stat-title">Ruangan</span>
            <span class="stat-value">{{ $stats['rooms'] }}</span>
        </div>
        <div class="card-stat">
            <span class="stat-title">Rombongan Belajar</span>
            <span class="stat-value">{{ $stats['rombels'] }}</span>
        </div>
        <div class="card-stat" style="grid-column: span 1;">
            <span class="stat-title">Alokasi Mengajar</span>
            <span class="stat-value">{{ $stats['lessons'] }} JP</span>
        </div>
        <div class="card-stat" style="grid-column: span 1;">
            <span class="stat-title">Jadwal Terbentuk</span>
            <span class="stat-value">
                {{ $stats['schedules'] }} JP
                @if($stats['is_locked'])
                    <span style="font-size: 14px; font-weight: normal; color: var(--danger);">[Kunci 🔒]</span>
                @elseif($stats['schedules'] > 0)
                    <span style="font-size: 14px; font-weight: normal; color: var(--warning);">[Draft 📝]</span>
                @endif
            </span>
        </div>
    </div>

    <!-- Academic Year Management -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Manajemen Tahun Akademik & Semester</h3>
            <button class="btn btn-primary" onclick="openModal()">+ Tambah Semester</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tahun Akademik</th>
                            <th>Semester</th>
                            <th>Status Aktif</th>
                            <th>Status Jadwal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($academicYears as $year)
                            <tr>
                                <td><strong>{{ $year->year }}</strong></td>
                                <td>
                                    <span class="badge {{ $year->semester === 'Ganjil' ? 'badge-primary' : 'badge-success' }}">
                                        {{ $year->semester }}
                                    </span>
                                </td>
                                <td>
                                    @if($year->is_active)
                                        <span class="badge badge-success">Aktif</span>
                                    @else
                                        <span class="badge badge-danger" style="background-color:#e2e8f0; color:#475569;">Non-aktif</span>
                                    @endif
                                </td>
                                <td>
                                    @if($year->is_locked)
                                        <span class="badge badge-danger">🔒 Terkunci (Locked)</span>
                                    @else
                                        <span class="badge badge-warning">📝 Terbuka (Draft)</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!$year->is_active)
                                        <form action="{{ route('academic-years.activate', $year->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">Aktifkan</button>
                                        </form>
                                    @else
                                        <button class="btn btn-success" style="padding: 6px 12px; font-size: 12px; cursor: default;" disabled>Sedang Aktif</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted);">Belum ada data Tahun Akademik. Silakan buat baru.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <div class="modal-backdrop" id="yearModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="card-title">Tambah Tahun Akademik / Semester</h3>
                <button onclick="closeModal()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
            </div>
            <form action="{{ route('academic-years.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="year">Tahun Akademik</label>
                        <input type="text" class="form-control" id="year" name="year" placeholder="Contoh: 2026/2027" required>
                        <span style="font-size: 12px; color: var(--text-muted); display: block; margin-top: 4px;">Format harus YYYY/YYYY (contoh: 2026/2027)</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="semester">Semester</label>
                        <select class="form-control" id="semester" name="semester" required>
                            <option value="Ganjil">Ganjil</option>
                            <option value="Genap">Genap</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function openModal() {
            const modal = document.getElementById('yearModal');
            modal.classList.add('show');
        }

        function closeModal() {
            const modal = document.getElementById('yearModal');
            modal.classList.remove('show');
        }

        // Close on escape key
        window.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
@endsection
