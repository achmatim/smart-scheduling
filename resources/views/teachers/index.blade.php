@extends('layouts.app')

@section('page_title', 'Master Guru')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Guru SMP Manggala</h3>
            <button class="btn btn-primary" onclick="openAddModal()">+ Tambah Guru</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>NIP</th>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>No. Telepon</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teachers as $teacher)
                            <tr>
                                <td>{{ $teacher->nip ?? '-' }}</td>
                                <td><strong>{{ $teacher->name }}</strong></td>
                                <td>{{ $teacher->email ?? '-' }}</td>
                                <td>{{ $teacher->phone ?? '-' }}</td>
                                <td>
                                    <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;" 
                                            onclick="openEditModal({{ json_encode($teacher) }})">
                                        Edit
                                    </button>
                                    <form action="{{ route('teachers.destroy', $teacher->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus guru ini? Seluruh data ketersediaan waktu dan alokasi mengajar yang terkait akan terhapus.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted);">Belum ada data Guru. Silakan tambahkan.</td>
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
                <h3 class="card-title">Tambah Guru Baru</h3>
                <button onclick="closeAddModal()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
            </div>
            <form action="{{ route('teachers.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="nip">NIP</label>
                        <input type="text" class="form-control" id="nip" name="nip" placeholder="Masukkan NIP (Opsional)">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="name">Nama Lengkap</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Contoh: Drs. Budi Santoso" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="email">Alamat Email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Contoh: budi@manggala.sch.id">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="phone">No. Telepon</label>
                        <input type="text" class="form-control" id="phone" name="phone" placeholder="Contoh: 081234567890">
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
                <h3 class="card-title">Edit Data Guru</h3>
                <button onclick="closeEditModal()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
            </div>
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="edit_nip">NIP</label>
                        <input type="text" class="form-control" id="edit_nip" name="nip" placeholder="Masukkan NIP">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_name">Nama Lengkap</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_email">Alamat Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_phone">No. Telepon</label>
                        <input type="text" class="form-control" id="edit_phone" name="phone">
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

        function openEditModal(teacher) {
            document.getElementById('edit_nip').value = teacher.nip || '';
            document.getElementById('edit_name').value = teacher.name;
            document.getElementById('edit_email').value = teacher.email || '';
            document.getElementById('edit_phone').value = teacher.phone || '';
            
            // Set action URL
            document.getElementById('editForm').action = '/teachers/' + teacher.id;
            
            document.getElementById('editModal').classList.add('show');
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('show');
        }
    </script>
@endsection
