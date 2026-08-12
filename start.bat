@echo off
echo =======================================================
echo  Menghidupkan Server Laravel dan Simulator ESP32...
echo =======================================================

echo Menjalankan Server Laravel (php artisan serve)...
start cmd /k "title Server Laravel & php artisan serve"

echo Menunggu server siap...
timeout /t 3 /nobreak >nul

echo Menjalankan Simulator ESP32...
start cmd /k "title Simulator ESP32 & php simulator.php"

echo Selesai! Dua terminal telah dibuka.

