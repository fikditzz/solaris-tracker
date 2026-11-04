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
];
