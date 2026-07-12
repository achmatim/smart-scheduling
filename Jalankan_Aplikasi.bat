@echo off
title Sistem Penjadwalan SMP Manggala
color 0B
cls

echo ====================================================================
echo             SISTEM PENJADWALAN OTOMATIS SMP MANGGALA
echo ====================================================================
echo.
echo  Sedang menyiapkan layanan aplikasi...
echo.

:: 1. Jalankan Laravel Web Server di background
echo  [1/3] Menjalankan server lokal (Port 8000)...
start /b php artisan serve --port=8000 >nul 2>&1

:: Berikan waktu 1.5 detik agar server siap
timeout /t 2 /nobreak >nul

:: 2. Buka browser secara otomatis ke http://127.0.0.1:8000
echo  [2/3] Membuka browser otomatis ke http://127.0.0.1:8000...
start http://127.0.0.1:8000

:: 3. Jalankan Queue Worker di jendela utama CMD ini
echo  [3/3] Mengaktifkan AI Scheduling Worker...
echo.
echo  ====================================================================
echo  STATUS: APLIKASI AKTIF DAN SIAP DIGUNAKAN!
echo  ====================================================================
echo.
echo  * PENTING: JANGAN menutup jendela hitam CMD ini selama aplikasi
echo    sedang digunakan.
echo  * Tutup jendela ini jika Anda selesai menggunakan aplikasi.
echo.
echo  Menunggu antrean tugas AI...
echo  --------------------------------------------------------------------
echo.

php artisan queue:work --tries=3

pause
