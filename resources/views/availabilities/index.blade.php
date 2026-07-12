@extends('layouts.app')

@section('page_title', 'Konstrain Ketersediaan Mengajar Guru')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filter Guru</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('availabilities.index') }}" method="GET" id="filterForm">
                <div class="form-group" style="display:flex; align-items:center; gap:16px; margin-bottom:0;">
                    <div style="flex-grow:1;">
                        <label class="form-label" for="teacher_id">Pilih Guru</label>
                        <select class="form-control" name="teacher_id" id="teacher_id" onchange="document.getElementById('filterForm').submit()">
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}" {{ $t->id == $selectedTeacherId ? 'selected' : '' }}>
                                    {{ $t->name }} {{ $t->nip ? '(NIP: '.$t->nip.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($selectedTeacher)
        <div class="card">
            <div class="card-header" style="background-color: #f8fafc;">
                <h3 class="card-title" style="color: var(--primary);">
                    Grid Ketersediaan: <strong>{{ $selectedTeacher->name }}</strong>
                </h3>
                <span style="font-size: 13px; color: var(--text-muted);">
                    Centang jam di mana guru bersedia mengajar. Hilangkan centang jika guru berhalangan/sibuk.
                </span>
            </div>
            <form action="{{ route('availabilities.update', $selectedTeacher->id) }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="schedule-header-actions" style="margin-bottom: 16px;">
                        <button type="button" class="btn btn-secondary" onclick="checkAll(true)">Centang Semua</button>
                        <button type="button" class="btn btn-secondary" onclick="checkAll(false)">Kosongkan Semua</button>
                    </div>
                    
                    <div class="avail-grid">
                        <!-- Grid Header Column (Time Slots) -->
                        <div class="avail-header">Hari / Jam Ke-</div>
                        @for($p = 1; $p <= 8; $p++)
                            <div class="avail-header">Jam {{ $p }}</div>
                        @endfor

                        <!-- Grid Rows per Day -->
                        @foreach($days as $dayNum => $dayName)
                            <div class="avail-day">{{ $dayName }}</div>
                            @for($p = 1; $p <= 8; $p++)
                                <div class="avail-cell">
                                    <input type="checkbox" class="avail-checkbox" 
                                           name="slots[{{ $dayNum }}][{{ $p }}]" 
                                           value="1" 
                                           {{ isset($availabilities[$dayNum][$p]) && $availabilities[$dayNum][$p] ? 'checked' : '' }}>
                                </div>
                            @endfor
                        @endforeach
                    </div>
                </div>
                <div class="card-footer" style="padding: 20px 24px; border-top: 1px solid var(--border-color); display:flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary">Simpan Konstrain Ketersediaan</button>
                </div>
            </form>
        </div>
    @endif
@endsection

@section('scripts')
    <script>
        function checkAll(checked) {
            const checkboxes = document.querySelectorAll('.avail-checkbox');
            checkboxes.forEach(cb => cb.checked = checked);
        }
    </script>
@endsection
