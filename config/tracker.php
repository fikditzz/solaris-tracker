<?php

return [
    // Mengizinkan endpoint seed dan reset. Biarkan false di server publik.
    'dev_tools' => (bool) env('TRACKER_DEV_TOOLS', false),
];
