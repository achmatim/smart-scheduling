@extends('layouts.app')

@section('page_title', 'Alokasi Mengajar Guru (Pembagian Tugas)')

@section('content')
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Daftar Alokasi Mengajar</h3>
                <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
                    Mengatur daftar mata pelajaran dan guru pengampu untuk setiap Rombel pada semester ini.
                </p>
            </div>
            @if(!$isLocked)
                <button class="btn btn-primary" onclick="openAddModal()">+ Tambah Alokasi</button>
            @else
                <button class="btn btn-secondary" style="cursor:not-allowed;" disabled>🔒 Terkunci (Locked)</button>
            @endif
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kelas (Rombel)</th>
                            <th>Mata Pelajaran</th>
                            <th>Guru Pengampu</th>
                            <th>Total Jam (JP)</th>
                            <th>Pemecahan Jam (Split)</th>
                            @if(!$isLocked)
                                <th>Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lessons as $lesson)
                            <tr>
                                <td><span class="badge badge-primary">Kelas {{ $lesson->rombel->name }}</span></td>
                                <td><strong>{{ $lesson->subject->name }}</strong> ({{ $lesson->subject->code }})</td>
                                <td>{{ $lesson->teacher->name }}</td>
                                <td><span style="font-weight:600;">{{ $lesson->total_hours }} JP</span></td>
                                <td>
                                    @php $splits = explode(',', $lesson->split_hours); @endphp
                                    @foreach($splits as $s)
                                        <span class="badge" style="background-color: #f1f5f9; color: #475569; border: 1px solid var(--border-color);">
                                            {{ $s }} JP
                                        </span>
                                    @endforeach
                                </td>
                                @if(!$isLocked)
                                    <td>
                                        <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;" 
                                                onclick="openEditModal({{ json_encode($lesson) }})">
                                            Edit
                                        </button>
                                        <form action="{{ route('lessons.destroy', $lesson->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus alokasi mengajar ini? Seluruh jadwal yang terbentuk dari alokasi ini akan terhapus.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">Hapus</button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isLocked ? 5 : 6 }}" style="text-align: center; color: var(--text-muted);">
                                    Belum ada data alokasi mengajar. Silakan tambahkan.
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
        <div class="modal" style="width:600px;">
            <div class="modal-header">
                <h3 class="card-title">Tambah Alokasi Mengajar</h3>
                <button onclick="closeAddModal()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
            </div>
            <form action="{{ route('lessons.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="rombel_id">Rombongan Belajar (Kelas)</label>
                        <select class="form-control" id="rombel_id" name="rombel_id" required>
                            <option value="">-- Pilih Kelas --</option>
                            @foreach($rombels as $r)
                                <option value="{{ $r->id }}" {{ old('rombel_id') == $r->id ? 'selected' : '' }}>{{ $r->name }} (Grade {{ $r->grade }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="subject_id">Mata Pelajaran</label>
                        <select class="form-control" id="subject_id" name="subject_id" required>
                            <option value="">-- Pilih Mapel --</option>
                            @foreach($subjects as $s)
                                <option value="{{ $s->id }}" {{ old('subject_id') == $s->id ? 'selected' : '' }}>{{ $s->name }} ({{ $s->code }} - Tipe: {{ $s->type }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="teacher_id">Guru Pengampu</label>
                        <select class="form-control" id="teacher_id" name="teacher_id" required>
                            <option value="">-- Pilih Guru --</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}" {{ old('teacher_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="total_hours">Total Jam Pelajaran per Minggu (JP)</label>
                        <input type="number" class="form-control" id="total_hours" name="total_hours" min="1" max="10" placeholder="Contoh: 4" value="{{ old('total_hours', 4) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="split_hours">Pemecahan Jam Mengajar per Sesi (Split)</label>
                        <input type="text" class="form-control" id="split_hours" name="split_hours" placeholder="Contoh: 2,2" value="{{ old('split_hours', '2,2') }}" required>
                        <span style="font-size: 12px; color: var(--text-muted); display: block; margin-top: 4px;">
                            Pemisah koma tanpa spasi. Penjumlahan harus sama dengan Total JP.
                            <br>Contoh: <strong>4 JP</strong> dipecah menjadi 2 JP dan 2 JP ditulis <strong>2,2</strong>. 
                            <strong>3 JP</strong> ditulis <strong>3</strong> atau <strong>2,1</strong>.
                        </span>
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
        <div class="modal" style="width:600px;">
            <div class="modal-header">
                <h3 class="card-title">Edit Alokasi Mengajar</h3>
                <button onclick="closeEditModal()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
            </div>
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="edit_rombel_id">Rombongan Belajar (Kelas)</label>
                        <select class="form-control" id="edit_rombel_id" name="rombel_id" required>
                            @foreach($rombels as $r)
                                <option value="{{ $r->id }}">{{ $r->name }} (Grade {{ $r->grade }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_subject_id">Mata Pelajaran</label>
                        <select class="form-control" id="edit_subject_id" name="subject_id" required>
                            @foreach($subjects as $s)
                                <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_teacher_id">Guru Pengampu</label>
                        <select class="form-control" id="edit_teacher_id" name="teacher_id" required>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_total_hours">Total Jam Pelajaran per Minggu (JP)</label>
                        <input type="number" class="form-control" id="edit_total_hours" name="total_hours" min="1" max="10" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_split_hours">Pemecahan Jam Mengajar per Sesi (Split)</label>
                        <input type="text" class="form-control" id="edit_split_hours" name="split_hours" required>
                        <span style="font-size: 12px; color: var(--text-muted); display: block; margin-top: 4px;">
                            Pemisah koma tanpa spasi. Penjumlahan harus sama dengan Total JP.
                        </span>
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

        function openEditModal(lesson) {
            document.getElementById('edit_rombel_id').value = lesson.rombel_id;
            document.getElementById('edit_subject_id').value = lesson.subject_id;
            document.getElementById('edit_teacher_id').value = lesson.teacher_id;
            document.getElementById('edit_total_hours').value = lesson.total_hours;
            document.getElementById('edit_split_hours').value = lesson.split_hours;
            
            // Set action URL
            document.getElementById('editForm').action = '/lessons/' + lesson.id;
            
            document.getElementById('editModal').classList.add('show');
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('show');
        }
    </script>
@endsection
