@extends('layouts.app')

@section('page_title', 'Master Rombongan Belajar')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Rombongan Belajar (Kelas) {{ Auth::user()->school->name ?? 'Yayasan Manggala' }}</h3>
            <button class="btn btn-primary" onclick="openAddModal()">+ Tambah Rombel</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tingkat / Grade</th>
                            <th>Nama Kelas (Rombel)</th>
                            <th>Ruangan Kelas (Home Room)</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rombels as $rombel)
                            <tr>
                                <td><span class="badge badge-primary">Kelas {{ $rombel->grade }}</span></td>
                                <td><strong>{{ $rombel->name }}</strong></td>
                                <td>
                                    @if($rombel->room)
                                        <span class="badge badge-success" style="background-color: var(--success-light); color:#065f46; font-size:12px;">
                                            📍 {{ $rombel->room->code }} ({{ $rombel->room->name }})
                                        </span>
                                    @else
                                        <span style="color:var(--text-muted); font-style:italic;">Belum disetel</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;" 
                                             onclick="openEditModal({{ json_encode($rombel) }})">
                                        Edit
                                    </button>
                                    <form action="{{ route('rombels.destroy', $rombel->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus rombel ini? Seluruh alokasi mengajar dan jadwal kelas terkait akan terhapus.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-muted);">Belum ada data Rombel. Silakan tambahkan.</td>
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
                <h3 class="card-title">Tambah Rombel Baru</h3>
                <button onclick="closeAddModal()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
            </div>
            <form action="{{ route('rombels.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="grade">Tingkat Kelas (Grade)</label>
                        <select class="form-control" id="grade" name="grade" required>
                            <option value="7">Kelas VII (7)</option>
                            <option value="8">Kelas VIII (8)</option>
                            <option value="9">Kelas IX (9)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="name">Nama Kelas (Rombel)</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Contoh: VII A, VIII B, IX C" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="room_id">Ruangan Kelas (Home Room)</label>
                        <select class="form-control" id="room_id" name="room_id">
                            <option value="">-- Pilih Ruangan Kelas --</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}">{{ $room->code }} - {{ $room->name }} ({{ $room->type }})</option>
                            @endforeach
                        </select>
                        <span style="font-size:11px; color:var(--text-muted); margin-top:4px; display:block;">
                            Menentukan ruangan kelas 1-to-1 yang selalu ditempati untuk kelas umum.
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
        <div class="modal">
            <div class="modal-header">
                <h3 class="card-title">Edit Rombel</h3>
                <button onclick="closeEditModal()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
            </div>
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="edit_grade">Tingkat Kelas (Grade)</label>
                        <select class="form-control" id="edit_grade" name="grade" required>
                            <option value="7">Kelas VII (7)</option>
                            <option value="8">Kelas VIII (8)</option>
                            <option value="9">Kelas IX (9)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_name">Nama Kelas (Rombel)</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_room_id">Ruangan Kelas (Home Room)</label>
                        <select class="form-control" id="edit_room_id" name="room_id">
                            <option value="">-- Pilih Ruangan Kelas --</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}">{{ $room->code }} - {{ $room->name }} ({{ $room->type }})</option>
                            @endforeach
                        </select>
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

        // Open Edit Modal and fill data
        function openEditModal(rombel) {
            document.getElementById('edit_grade').value = rombel.grade;
            document.getElementById('edit_name').value = rombel.name;
            document.getElementById('edit_room_id').value = rombel.room_id || '';
            
            // Set action URL
            document.getElementById('editForm').action = '/rombels/' + rombel.id;
            
            document.getElementById('editModal').classList.add('show');
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('show');
        }
    </script>
@endsection
