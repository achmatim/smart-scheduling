<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sistem Penjadwalan SMP Manggala</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @yield('styles')
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <span>SMP</span> Manggala
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item {{ Request::routeIs('dashboard') ? 'active' : '' }}">
                <a href="{{ route('dashboard') }}">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"></path></svg>
                    Dashboard
                </a>
            </li>
            <li class="sidebar-item {{ Request::routeIs('teachers.*') ? 'active' : '' }}">
                <a href="{{ route('teachers.index') }}">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Master Guru
                </a>
            </li>
            <li class="sidebar-item {{ Request::routeIs('subjects.*') ? 'active' : '' }}">
                <a href="{{ route('subjects.index') }}">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.168.477 4 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4 1.253"></path></svg>
                    Master Mapel
                </a>
            </li>
            <li class="sidebar-item {{ Request::routeIs('rooms.*') ? 'active' : '' }}">
                <a href="{{ route('rooms.index') }}">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    Master Ruangan
                </a>
            </li>
            <li class="sidebar-item {{ Request::routeIs('rombels.*') ? 'active' : '' }}">
                <a href="{{ route('rombels.index') }}">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-3c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-3c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Master Rombel
                </a>
            </li>
            <li class="sidebar-item {{ Request::routeIs('periods.*') ? 'active' : '' }}">
                <a href="{{ route('periods.index') }}">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 6V12h6"></path></svg>
                    Master Jam (Sesi)
                </a>
            </li>
            <li class="sidebar-item {{ Request::routeIs('availabilities.*') ? 'active' : '' }}">
                <a href="{{ route('availabilities.index') }}">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Ketersediaan Guru
                </a>
            </li>
            <li class="sidebar-item {{ Request::routeIs('lessons.*') ? 'active' : '' }}">
                <a href="{{ route('lessons.index') }}">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    Alokasi Mengajar
                </a>
            </li>
            <li class="sidebar-item {{ Request::routeIs('schedules.*') ? 'active' : '' }}">
                <a href="{{ route('schedules.index') }}">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Jadwal Pelajaran
                </a>
            </li>
            <li class="sidebar-item {{ Request::routeIs('reports.*') ? 'active' : '' }}">
                <a href="{{ route('reports.index') }}">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Laporan Jadwal
                </a>
            </li>
        </ul>
        <div class="sidebar-footer">
            SMP Manggala &copy; 2026
        </div>
    </aside>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
        
        <!-- Topbar -->
        <header class="topbar">
            <div class="page-title">
                @yield('page_title', 'Sistem Penjadwalan Sekolah')
            </div>
            <div class="topbar-right">
                @isset($activeYear)
                    <div class="academic-badge">
                        Semester {{ $activeYear->semester }} TA {{ $activeYear->year }}
                        @if($activeYear->is_locked)
                            <span style="color: var(--danger); margin-left: 5px;">🔒</span>
                        @endif
                    </div>
                @else
                    <div class="academic-badge" style="background-color: var(--danger-light); color: var(--danger);">
                        Belum Ada Tahun Akademik Aktif
                    </div>
                @endisset
                <div class="user-profile" style="gap:16px;">
                    <div style="display:flex; align-items:center; gap:6px;">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        <span>Admin</span>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" style="margin:0; display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size:12px; border-radius: 6px; font-weight: 600;">Keluar</button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="content-container">
            <!-- Alert Notifications -->
            @if(session('success'))
                <div class="alert alert-success">
                    <span>{{ session('success') }}</span>
                    <button onclick="this.parentElement.remove()" style="background:none; border:none; color:inherit; font-size:16px; cursor:pointer;">&times;</button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-error">
                    <span>{{ session('error') }}</span>
                    <button onclick="this.parentElement.remove()" style="background:none; border:none; color:inherit; font-size:16px; cursor:pointer;">&times;</button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-error">
                    <div>
                        <ul style="padding-left: 20px; text-align: left;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <button onclick="this.parentElement.remove()" style="background:none; border:none; color:inherit; font-size:16px; cursor:pointer;">&times;</button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @yield('scripts')
</body>
</html>
