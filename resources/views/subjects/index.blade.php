@extends('layouts.app')

@section('page_title', 'Master Mata Pelajaran')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Mata Pelajaran {{ Auth::user()->school->name ?? 'Yayasan Manggala' }}</h3>
            <button class="btn btn-primary" onclick="openAddModal()">+ Tambah Mapel</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kode Mapel</th>
                            <th>Nama Mata Pelajaran</th>
                            <th>Kategori / Tipe Ruangan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subjects as $subject)
                            <tr>
                                <td><span class="badge badge-primary">{{ $subject->code }}</span></td>
                                <td><strong>{{ $subject->name }}</strong></td>
                                <td>
                                    @if($subject->type === 'umum')
                                        <span class="badge badge-success" style="background-color: #dbeafe; color: #1e40af;">Umum (Kelas)</span>
                                    @elseif($subject->type === 'praktek')
                                        <span class="badge badge-warning" style="background-color: #fef3c7; color: #92400e;">Praktek (Laboratorium)</span>
                                    @elseif($subject->type === 'olahraga')
                                        <span class="badge badge-danger" style="background-color: #fee2e2; color: #991b1b;">Olahraga (Lapangan)</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;" 
                                            onclick="openEditModal({{ json_encode($subject) }})">
                                        Edit
                                    </button>
                                    <form action="{{ route('subjects.destroy', $subject->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus mata pelajaran ini? Seluruh alokasi mengajar yang terkait akan terhapus.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-muted);">Belum ada data Mata Pelajaran. Silakan tambahkan.</td>
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
                <h3 class="card-title">Tambah Mata Pelajaran Baru</h3>
                <button onclick="closeAddModal()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
            </div>
            <form action="{{ route('subjects.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="code">Kode Mapel</label>
                        <input type="text" class="form-control" id="code" name="code" placeholder="Contoh: MAT, IPA, ING" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="name">Nama Mata Pelajaran</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Contoh: Matematika" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="type">Tipe Mata Pelajaran</label>
                        <select class="form-control" id="type" name="type" required>
                            <option value="umum">Umum (Ditempatkan di Ruang Kelas Biasa)</option>
                            <option value="praktek">Praktek (Ditempatkan di Laboratorium)</option>
                            <option value="olahraga">Olahraga (Ditempatkan di Lapangan Olahraga)</option>
                        </select>
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
                <h3 class="card-title">Edit Mata Pelajaran</h3>
                <button onclick="closeEditModal()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
            </div>
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="edit_code">Kode Mapel</label>
                        <input type="text" class="form-control" id="edit_code" name="code" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_name">Nama Mata Pelajaran</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_type">Tipe Mata Pelajaran</label>
                        <select class="form-control" id="edit_type" name="type" required>
                            <option value="umum">Umum (Ditempatkan di Ruang Kelas Biasa)</option>
                            <option value="praktek">Praktek (Ditempatkan di Laboratorium)</option>
                            <option value="olahraga">Olahraga (Ditempatkan di Lapangan Olahraga)</option>
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

        function openEditModal(subject) {
            document.getElementById('edit_code').value = subject.code;
            document.getElementById('edit_name').value = subject.name;
            document.getElementById('edit_type').value = subject.type;
            
            // Set action URL
            document.getElementById('editForm').action = '/subjects/' + subject.id;
            
            document.getElementById('editModal').classList.add('show');
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('show');
        }
    </script>
@endsection
