<?php

return [
    // Mengizinkan endpoint seed dan reset. Biarkan false di server publik.
    'dev_tools' => (bool) env('TRACKER_DEV_TOOLS', false),

    // Zona waktu lokasi panel, dipakai untuk filter tanggal dan pengelompokan grafik.
    'timezone' => env('TRACKER_TIMEZONE', 'Asia/Makassar'),

    // Koordinat lokasi panel, dipakai untuk pencatatan cuaca.
    'site' => [
        'latitude' => (float) env('TRACKER_SITE_LATITUDE', -5.2150509),
        'longitude' => (float) env('TRACKER_SITE_LONGITUDE', 119.5793433),
    ],

    // Repo firmware yang dipakai perangkat untuk OTA. Setiap folder berisi version.txt
    // yang dibandingkan perangkat dengan versi terpasangnya.
    'firmware' => [
        'repo_url' => env('TRACKER_FIRMWARE_REPO', 'https://github.com/bengkelit2026-ops/solar-tracker-firmware'),
        'version_urls' => [
            'panel1' => env('TRACKER_FIRMWARE_VERSION_URL_PANEL1', 'https://raw.githubusercontent.com/bengkelit2026-ops/solar-tracker-firmware/refs/heads/main/solar-tracker-2-axis/version.txt'),
            'panel2' => env('TRACKER_FIRMWARE_VERSION_URL_PANEL2', 'https://raw.githubusercontent.com/bengkelit2026-ops/solar-tracker-firmware/refs/heads/main/solar-tracker-1-axis/version.txt'),
        ],
    ],
];
