@extends('layouts.app')

@section('page_title', 'Master Ruangan')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Ruangan {{ Auth::user()->school->name ?? 'Yayasan Manggala' }}</h3>
            <button class="btn btn-primary" onclick="openAddModal()">+ Tambah Ruangan</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kode Ruangan</th>
                            <th>Nama Ruangan</th>
                            <th>Tipe Ruangan</th>
                            <th>Kapasitas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rooms as $room)
                            <tr>
                                <td><span class="badge badge-primary">{{ $room->code }}</span></td>
                                <td><strong>{{ $room->name }}</strong></td>
                                <td>
                                    @if($room->type === 'umum')
                                        <span class="badge badge-success" style="background-color: #d1fae5; color: #065f46;">Kelas Umum</span>
                                    @elseif($room->type === 'lab')
                                        <span class="badge badge-warning" style="background-color: #fef3c7; color: #92400e;">Laboratorium</span>
                                    @elseif($room->type === 'lapangan')
                                        <span class="badge badge-danger" style="background-color: #fee2e2; color: #991b1b;">Lapangan Olahraga</span>
                                    @endif
                                </td>
                                <td>{{ $room->capacity ?? '-' }} Siswa</td>
                                <td>
                                    <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;" 
                                            onclick="openEditModal({{ json_encode($room) }})">
                                        Edit
                                    </button>
                                    <form action="{{ route('rooms.destroy', $room->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus ruangan ini? Seluruh jadwal yang menggunakan ruangan ini akan disetel ulang.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted);">Belum ada data Ruangan. Silakan tambahkan.</td>
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
                <h3 class="card-title">Tambah Ruangan Baru</h3>
                <button onclick="closeAddModal()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
            </div>
            <form action="{{ route('rooms.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="code">Kode Ruangan</label>
                        <input type="text" class="form-control" id="code" name="code" placeholder="Contoh: R-7A, LAB-IPA, LAP-OLA" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="name">Nama Ruangan</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Contoh: Kelas VII A, Lab Fisika, Lapangan Utama" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="type">Tipe Ruangan</label>
                        <select class="form-control" id="type" name="type" required>
                            <option value="umum">Kelas Umum</option>
                            <option value="lab">Laboratorium</option>
                            <option value="lapangan">Lapangan Olahraga</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="capacity">Kapasitas (Siswa)</label>
                        <input type="number" class="form-control" id="capacity" name="capacity" placeholder="Contoh: 32 (Opsional)">
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
                <h3 class="card-title">Edit Data Ruangan</h3>
                <button onclick="closeEditModal()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
            </div>
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="edit_code">Kode Ruangan</label>
                        <input type="text" class="form-control" id="edit_code" name="code" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_name">Nama Ruangan</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_type">Tipe Ruangan</label>
                        <select class="form-control" id="edit_type" name="type" required>
                            <option value="umum">Kelas Umum</option>
                            <option value="lab">Laboratorium</option>
                            <option value="lapangan">Lapangan Olahraga</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_capacity">Kapasitas (Siswa)</label>
                        <input type="number" class="form-control" id="edit_capacity" name="capacity">
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

        function openEditModal(room) {
            document.getElementById('edit_code').value = room.code;
            document.getElementById('edit_name').value = room.name;
            document.getElementById('edit_type').value = room.type;
            document.getElementById('edit_capacity').value = room.capacity || '';
            
            // Set action URL
            document.getElementById('editForm').action = '/rooms/' + room.id;
            
            document.getElementById('editModal').classList.add('show');
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('show');
        }
    </script>
@endsection
