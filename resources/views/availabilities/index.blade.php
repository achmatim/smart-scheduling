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
                    
                    <div class="avail-grid" style="grid-template-columns: 160px repeat({{ $periods->count() }}, 1fr);">
                        <!-- Grid Header Column (Time Slots) -->
                        <div class="avail-header">Hari / Jam Ke-</div>
                        @foreach($periods as $pModel)
                            <div class="avail-header" style="{{ $pModel->is_break ? 'background-color: #fee2e2; color: #b91c1c;' : '' }}">
                                Jam {{ $pModel->period_number }}
                                @if($pModel->is_break)
                                    <span style="font-size: 10px; display:block; font-weight: normal;">☕ Istirahat</span>
                                @else
                                    <span style="font-size: 10px; display:block; font-weight: normal; color:var(--text-muted);">{{ $pModel->start_time }}</span>
                                @endif
                            </div>
                        @endforeach

                        <!-- Grid Rows per Day -->
                        @foreach($days as $dayNum => $dayName)
                            <div class="avail-day">{{ $dayName }}</div>
                            @foreach($periods as $pModel)
                                @php
                                    $p = $pModel->period_number;
                                @endphp
                                @if($pModel->is_break)
                                    <div class="avail-cell" style="background-color: #fef2f2; color: #b91c1c; font-weight: bold; font-size: 12px;">
                                        ☕
                                    </div>
                                @else
                                    <div class="avail-cell">
                                        <input type="checkbox" class="avail-checkbox" 
                                               name="slots[{{ $dayNum }}][{{ $p }}]" 
                                               value="1" 
                                               {{ (!isset($availabilities[$dayNum][$p]) || $availabilities[$dayNum][$p]) ? 'checked' : '' }}>
                                    </div>
                                @endif
                            @endforeach
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
