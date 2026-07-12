@extends('layouts.app')

@section('page_title', 'Master Jam Pelajaran & Istirahat')

@section('content')
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Daftar Jam Kerja & Sesi Sekolah</h3>
                <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
                    Atur jam pelajaran (sesi) sekolah dan waktu istirahat. Sistem tidak akan menjadwalkan kelas pada jam istirahat.
                </p>
            </div>
            <button class="btn btn-primary" onclick="openAddModal()">+ Tambah Jam / Sesi</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Jam Ke- (Sesi)</th>
                            <th>Waktu Mulai</th>
                            <th>Waktu Selesai</th>
                            <th>Kategori Sesi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($periods as $period)
                            <tr>
                                <td>
                                    <span class="badge badge-primary" style="font-size: 13px; padding: 6px 12px;">
                                        Sesi {{ $period->period_number }}
                                    </span>
                                </td>
                                <td><strong>{{ $period->start_time }}</strong></td>
                                <td><strong>{{ $period->end_time }}</strong></td>
                                <td>
                                    @if($period->is_break)
                                        <span class="badge badge-danger" style="background-color: var(--danger-light); color: var(--danger);">
                                            ☕ Istirahat / Jam Khusus (Non-Aktif)
                                        </span>
                                    @else
                                        <span class="badge badge-success" style="background-color: var(--success-light); color: #065f46;">
                                            📖 Jam Pelajaran Aktif
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;" 
                                            onclick="openEditModal({{ json_encode($period) }})">
                                        Edit
                                    </button>
                                    <form action="{{ route('periods.destroy', $period->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus sesi jam pelajaran ini? Ketersediaan guru yang menggunakan jam ini akan ikut disesuaikan.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted);">
                                    Belum ada data jam pelajaran/istirahat. Silakan tambahkan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal-backdrop" id="addModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="card-title">Tambah Jam / Sesi Pelajaran</h3>
                <button onclick="closeAddModal()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
            </div>
            <form action="{{ route('periods.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="period_number">Jam Ke- / Sesi Ke-</label>
                        <input type="number" class="form-control" id="period_number" name="period_number" placeholder="Contoh: 1, 2, 3" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="start_time">Waktu Mulai</label>
                        <input type="text" class="form-control" id="start_time" name="start_time" placeholder="Contoh: 07:15" required>
                        <span style="font-size:12px; color:var(--text-muted); display:block; margin-top:4px;">Format HH:MM (contoh: 07:15)</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="end_time">Waktu Selesai</label>
                        <input type="text" class="form-control" id="end_time" name="end_time" placeholder="Contoh: 07:55" required>
                    </div>
                    <div class="form-group" style="display:flex; align-items:center; gap:8px; margin-top:20px;">
                        <input type="checkbox" id="is_break" name="is_break" value="1" style="width:18px; height:18px; cursor:pointer; accent-color:var(--danger);">
                        <label for="is_break" style="font-weight:600; cursor:pointer; color:var(--danger);">Sesi Ini Adalah Jam Istirahat / Jam Khusus</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal-backdrop" id="editModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="card-title">Edit Jam / Sesi Pelajaran</h3>
                <button onclick="closeEditModal()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
            </div>
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="edit_period_number">Jam Ke- / Sesi Ke-</label>
                        <input type="number" class="form-control" id="edit_period_number" name="period_number" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_start_time">Waktu Mulai</label>
                        <input type="text" class="form-control" id="edit_start_time" name="start_time" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_end_time">Waktu Selesai</label>
                        <input type="text" class="form-control" id="edit_end_time" name="end_time" required>
                    </div>
                    <div class="form-group" style="display:flex; align-items:center; gap:8px; margin-top:20px;">
                        <input type="checkbox" id="edit_is_break" name="is_break" value="1" style="width:18px; height:18px; cursor:pointer; accent-color:var(--danger);">
                        <label for="edit_is_break" style="font-weight:600; cursor:pointer; color:var(--danger);">Sesi Ini Adalah Jam Istirahat / Jam Khusus</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('show');
        }
        function closeAddModal() {
            document.getElementById('addModal').classList.remove('show');
        }

        function openEditModal(period) {
            document.getElementById('edit_period_number').value = period.period_number;
            document.getElementById('edit_start_time').value = period.start_time;
            document.getElementById('edit_end_time').value = period.end_time;
            document.getElementById('edit_is_break').checked = period.is_break;
            
            // Set action URL
            document.getElementById('editForm').action = '/periods/' + period.id;
            
            document.getElementById('editModal').classList.add('show');
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('show');
        }
    </script>
@endsection
