<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Helios Tracker Pro</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <script>
        (function () {
            var theme = localStorage.getItem('theme') || 'light';
            if (theme === 'auto') {
                theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
</head>
<body>
    <nav id="sidebar" aria-label="Main">
        <div class="brand-logo">
            <span class="material-symbols-rounded" aria-hidden="true">solar_power</span>
            SOLARIS
        </div>
        <ul class="nav-menu">
            <li class="nav-item">
                <a class="nav-link active" id="nav-dashboard" href="#dashboard" data-view="dashboard">
                    <span class="material-symbols-rounded" aria-hidden="true">dashboard</span>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="nav-tracker" href="#tracker" data-view="tracker">
                    <span class="material-symbols-rounded" aria-hidden="true">my_location</span>
                    <span>Tracker <span class="nav-label-long">Panel</span></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="nav-charts" href="#charts" data-view="charts">
                    <span class="material-symbols-rounded" aria-hidden="true">bar_chart</span>
                    <span>Analytics <span class="nav-label-long">Canvas</span></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="nav-logs" href="#logs" data-view="logs">
                    <span class="material-symbols-rounded" aria-hidden="true">history</span>
                    <span><span class="nav-label-long">Recent </span>Logs</span>
                </a>
            </li>
            <li class="nav-item mt-4 pt-4" style="border-top: 1px solid var(--border-color);">
                <a class="nav-link" href="#settings" data-view="settings">
                    <span class="material-symbols-rounded" aria-hidden="true">settings</span>
                    Settings
                </a>
            </li>
        </ul>
    </nav>
    <div id="main-wrapper">
        <header class="top-header">
            <h1 class="page-title" id="top-title">Main Dashboard</h1>
              <div class="header-actions">
                  <div class="dropdown">
                      <button class="btn dropdown-toggle fw-semibold d-flex align-items-center gap-2" type="button" id="panelDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius: var(--radius-md); padding: 0.5rem 1.25rem; background-color: var(--surface); color: var(--text-main); border: 1px solid var(--border-color); font-size: 0.95rem; min-width: 260px;">
                          <span class="material-symbols-rounded text-warning" style="font-size: 20px;" aria-hidden="true">solar_power</span>
                          <span><span id="panelDropdownText">Array Alpha</span><span class="d-none d-sm-inline" id="panelDropdownAxis"> (Dual Axis)</span></span>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end panel-menu shadow-sm mt-2 p-2 w-100" aria-labelledby="panelDropdownBtn">
                          <li>
                              <button type="button" class="dropdown-item panel-option mb-1" data-panel="panel1">
                                  <span class="panel-option-icon material-symbols-rounded" aria-hidden="true">grid_view</span>
                                  <span class="panel-option-text">
                                      <span class="panel-option-name">Array Alpha</span>
                                      <span class="panel-option-axis">Dual Axis Tracking</span>
                                  </span>
                                  <span class="panel-option-check material-symbols-rounded" aria-hidden="true">check</span>
                              </button>
                          </li>
                          <li>
                              <button type="button" class="dropdown-item panel-option" data-panel="panel2">
                                  <span class="panel-option-icon material-symbols-rounded" aria-hidden="true">crop_square</span>
                                  <span class="panel-option-text">
                                      <span class="panel-option-name">Array Beta</span>
                                      <span class="panel-option-axis">Single Axis Tracking</span>
                                  </span>
                                  <span class="panel-option-check material-symbols-rounded" aria-hidden="true">check</span>
                              </button>
                          </li>
                      </ul>
                  </div>
                  <div class="status-badge is-waiting" id="system-status-badge" role="status">
                    <div class="status-dot" id="system-status-dot"></div>
                    <span id="system-status-text">Connecting</span>
                </div>
                <button type="button" class="icon-btn" id="notice-btn" title="Current system status" aria-label="Show current system status"><span class="material-symbols-rounded" aria-hidden="true">notifications</span></button>
                <button type="button" class="icon-btn" id="theme-toggle" title="Theme" aria-label="Switch colour theme"><span class="material-symbols-rounded" id="theme-toggle-icon" aria-hidden="true">dark_mode</span></button>
            </div>
        </header>
        <main id="view-dashboard" class="page-view active">
            <div class="row g-4 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="card-custom h-100">
                        <div class="card-body-custom">
                            <div class="text-xs text-muted mb-2">LAST READING (WITA)</div>
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fs-3 fw-bold" id="dash-time">--:--:--</div>
                                    <div class="text-muted-custom mt-1" id="dash-date">No data yet</div>
                                </div>
                                <span class="material-symbols-rounded text-primary fs-2" style="opacity: 0.8" aria-hidden="true">schedule</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card-custom h-100">
                        <div class="card-body-custom">
                            <div class="text-xs text-muted mb-2">WEATHER</div>
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fs-4 fw-bold"><span id="weather-desc">Loading</span> <span id="weather-temp" class="fs-5 text-muted ms-1"></span></div>
                                    <div class="text-muted-custom mt-1"><span id="dash-irradiance">--</span> W/m² Irradiance</div>
                                </div>
                                <span id="weather-icon" class="material-symbols-rounded text-warning fs-1" aria-hidden="true">cloud</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="card-custom h-100">
                        <div class="card-body-custom d-flex justify-content-between align-items-center h-100 mode-card">
                            <div>
                                <div class="text-xs text-muted fw-bold mb-1">TRACKING MODE</div>
                                <div class="fs-4 fw-bold text-warning" id="dash-mode">--</div>
                                <div class="text-xs text-muted mt-1" id="dash-tracking-type">Dual-Axis Tracking Active</div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" id="btn-mode-auto" class="btn btn-outline-warning text-warning fw-semibold px-4" aria-pressed="false">Auto Tracking</button>
                                <button type="button" id="btn-mode-reset" class="btn btn-outline-secondary fw-semibold">Reset</button>
                                <button type="button" id="btn-mode-stop" class="btn btn-outline-danger fw-semibold" aria-pressed="false">Stop</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-4 mb-4">
                <div class="col-lg-6 col-xl-4 d-flex flex-column gap-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <div class="d-flex align-items-center gap-2 fw-bold">
                                <span class="material-symbols-rounded text-warning" aria-hidden="true">solar_power</span> Array Output
                            </div>
                        </div>
                        <div class="card-body-custom pt-0">
                            <div class="text-xs text-muted mb-1">CURRENT POWER</div>
                            <div class="d-flex align-items-baseline mb-4">
                                <span class="text-big animated-value" id="dash-power">--</span>
                                <span class="val-unit ms-2">W</span>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="text-xs text-muted mb-1">VOLTAGE</div>
                                    <div class="fs-4 fw-bold"><span id="dash-voltage" class="animated-value">--</span><span class="fs-6 text-muted ms-1">V</span></div>
                                </div>
                                <div class="col-6 border-start">
                                    <div class="text-xs text-muted mb-1">CURRENT</div>
                                    <div class="fs-4 fw-bold"><span id="dash-current" class="animated-value">--</span><span class="fs-6 text-muted ms-1">A</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-custom" id="dash-ldr-widget">
                         <div class="card-header-custom pb-2">
                            <div class="d-flex align-items-center gap-2 fw-bold text-sm">
                                <span class="material-symbols-rounded text-secondary" style="font-size:18px" aria-hidden="true">sensors</span> LDR Sensor Grid
                            </div>
                        </div>
                        <div class="card-body-custom pt-0">
                            <div class="row g-2 text-center">

                                <div class="col-12">
                                    <div class="p-3 bg-primary bg-opacity-10 border border-primary-subtle rounded-3" id="dash-box-ldr-nw">
                                        <div class="text-xs text-secondary fw-bold mb-1 ldr-label">LDR UTARA (NORTH)</div>
                                        <div class="fs-4 fw-bold text-dark d-flex justify-content-center align-items-center gap-2">
                                            <span class="material-symbols-rounded text-warning fs-5" aria-hidden="true">light_mode</span>
                                            <span id="dash-ldr-tl" class="animated-value">--</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="p-3 bg-primary bg-opacity-10 border border-primary-subtle rounded-3 h-100" id="dash-box-ldr-sw">
                                        <div class="text-xs text-secondary fw-bold mb-1 ldr-label">LDR BARAT (WEST)</div>
                                        <div class="fs-4 fw-bold text-dark d-flex justify-content-center align-items-center gap-2">
                                            <span class="material-symbols-rounded text-warning fs-5" aria-hidden="true">light_mode</span>
                                            <span id="dash-ldr-bl" class="animated-value">--</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3 bg-primary bg-opacity-10 border border-primary-subtle rounded-3 h-100" id="dash-box-ldr-ne">
                                        <div class="text-xs text-secondary fw-bold mb-1 ldr-label">LDR TIMUR (EAST)</div>
                                        <div class="fs-4 fw-bold text-dark d-flex justify-content-center align-items-center gap-2">
                                            <span class="material-symbols-rounded text-warning fs-5" aria-hidden="true">light_mode</span>
                                            <span id="dash-ldr-tr" class="animated-value">--</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="p-3 bg-primary bg-opacity-10 border border-primary-subtle rounded-3" id="dash-box-ldr-se">
                                        <div class="text-xs text-secondary fw-bold mb-1 ldr-label">LDR SELATAN (SOUTH)</div>
                                        <div class="fs-4 fw-bold text-dark d-flex justify-content-center align-items-center gap-2">
                                            <span class="material-symbols-rounded text-warning fs-5" aria-hidden="true">light_mode</span>
                                            <span id="dash-ldr-br" class="animated-value">--</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 col-xl-4 d-flex flex-column gap-4">

                    <div class="card-custom" id="dash-position-card" role="button" tabindex="0" aria-label="Open the tracker panel" style="cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                        <div class="card-header-custom pb-0">
                            <div class="d-flex align-items-center gap-2 fw-bold">
                                <span class="material-symbols-rounded text-warning" aria-hidden="true">explore</span> Tracker Position
                            </div>
                        </div>
                        <div class="card-body-custom pt-3">
                            <div class="tracker-map d-flex w-100 p-0 bg-white" style="border-radius: var(--radius-md); overflow: hidden;">

                                <div id="dash-azimuth-map-col" class="col-6 h-100 d-flex flex-column align-items-center justify-content-center position-relative border-end border-light transition-all">
                                    <div class="text-primary mb-2 position-absolute top-0 mt-2" style="font-size: 10px; font-weight: 700; letter-spacing: 1px;">AZIMUTH (TOP)</div>
                                    <div style="width: 140px; height: 140px; border-radius: 50%; border: 2px dashed rgba(100, 116, 139, 0.45); position: relative; display: flex; align-items: center; justify-content: center;">
                                        <div class="compass-mark" style="top: 2px;">N</div>
                                        <div class="compass-mark" style="bottom: 2px;">S</div>
                                        <div class="compass-mark" style="left: 4px;">W</div>
                                        <div class="compass-mark" style="right: 4px;">E</div>


                                        <div style="position: absolute; width: 4px; height: 140px; background: rgba(100, 116, 139, 0.25); border-radius: 2px; z-index: 1;"></div>


                                        <div id="dash-azimuth-ptr" style="position: absolute; width: 100%; height: 100%; transition: transform 0.5s ease; display: flex; align-items: center; justify-content: center; z-index: 2;">

                                            <div class="panel-diamond shadow-sm" style="transform: none !important;">
                                                <span class="material-symbols-rounded icon" aria-hidden="true">solar_power</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="position-absolute bottom-0 mb-2 px-3 py-1 bg-light border border-light rounded-pill text-dark shadow-sm" style="font-size: 10px; font-weight: 700;">
                                        <span id="map-az">--</span>°
                                    </div>
                                </div>


                                <div id="dash-elevation-map-col" class="col-6 h-100 d-flex flex-column align-items-center justify-content-center position-relative transition-all">
                                    <div class="text-warning mb-2 position-absolute top-0 mt-2" style="font-size: 10px; font-weight: 700; letter-spacing: 1px;">ELEVATION (SIDE)</div>
                                    <div style="width: 140px; height: 140px; position: relative; display: flex; align-items: center; justify-content: center;">

                                        <div style="position: absolute; bottom: 30px; width: 120px; height: 2px; background: rgba(100, 116, 139, 0.45);"></div>

                                        <div style="position: absolute; left: 50%; bottom: 30px; width: 1px; height: 80px; border-left: 2px dashed rgba(100, 116, 139, 0.45);"></div>


                                        <div style="position: absolute; bottom: 30px; left: calc(50% - 3px); width: 6px; height: 35px; background: #64748B; border-radius: 2px;"></div>

                                        <div style="position: absolute; bottom: 35px; left: calc(50% - 25px); width: 6px; height: 20px; background: #94A3B8; border-radius: 3px; transform: rotate(30deg);"></div>


                                        <div style="position: absolute; bottom: 65px; left: 50%; width: 2px; height: 0; overflow: visible;">

                                            <div id="dash-elevation-ptr" style="position: absolute; bottom: -4px; left: -45px; width: 90px; height: 8px; background: #F59E0B; border-radius: 4px; transform-origin: 50% 50%; transition: transform 0.5s ease; box-shadow: 0 4px 6px rgba(245, 158, 11, 0.2);">

                                                <div style="position: absolute; top: -3px; left: 5%; width: 90%; height: 3px; background: #FEF3C7; border-radius: 2px;"></div>

                                                <div style="position: absolute; bottom: -20px; left: 15px; width: 3px; height: 25px; background: #CBD5E1; border-radius: 1px;"></div>
                                            </div>
                                        </div>

                                        <div style="position: absolute; bottom: 61px; left: calc(50% - 5px); width: 10px; height: 10px; background: #64748B; border-radius: 50%; z-index: 2;"></div>

                                    </div>
                                    <div class="position-absolute bottom-0 mb-2 px-3 py-1 bg-light border border-light rounded-pill text-dark shadow-sm" style="font-size: 10px; font-weight: 700;">
                                        <span id="map-el">--</span>°
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3 flex-nowrap overflow-hidden">
                                <div id="dash-azimuth-val-col" class="col-6 transition-all">
                                    <div class="p-2 bg-light rounded text-center border">
                                        <div class="text-xs text-muted fw-bold">AZIMUTH</div>
                                        <div class="fw-bold fs-5 text-dark"><span id="dash-azimuth" class="animated-value">--</span>°</div>
                                    </div>
                                </div>
                                <div id="dash-elevation-val-col" class="col-6 transition-all">
                                    <div class="p-2 bg-light rounded text-center border">
                                        <div class="text-xs text-muted fw-bold">ELEVATION</div>
                                        <div class="fw-bold fs-5 text-dark"><span id="dash-elevation" class="animated-value">--</span>°</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 d-flex flex-column gap-4">
                    <div class="card-custom">
                        <div class="card-header-custom pb-0">
                            <div class="d-flex align-items-center gap-2 fw-bold">
                                <span class="material-symbols-rounded text-primary" aria-hidden="true">show_chart</span> Power Gen (24h)
                            </div>
                            <span class="badge bg-light text-secondary border">WATT</span>
                        </div>
                        <div class="card-body-custom pt-2">
                            <div style="height: 180px; position: relative;">
                                <canvas id="chartDashPower" role="img" aria-label="Average power over the last 24 hours"></canvas>
                                @include('partials.chart-state', ['id' => 'chartDashPower'])
                            </div>
                        </div>
                    </div>
                    <div class="card-custom">
                        <div class="card-header-custom pb-0">
                            <div class="d-flex align-items-center gap-2 fw-bold">
                                <span class="material-symbols-rounded text-primary" aria-hidden="true">ssid_chart</span> Voltage vs Current (24h)
                            </div>
                        </div>
                        <div class="card-body-custom pt-2">
                             <div style="height: 180px; position: relative;">
                                <canvas id="chartDashVA" role="img" aria-label="Average voltage and current over the last 24 hours"></canvas>
                                @include('partials.chart-state', ['id' => 'chartDashVA'])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
          <main id="view-tracker" class="page-view">
              <div class="text-xs text-muted mb-1 text-uppercase">Subsystem Diagnostics</div>
              <h2 class="mb-4 transition-all" id="tracker-panel-title">Dual-Axis Tracker Array</h2>
              <div class="row g-4 mb-4">
                <div class="col-xl-8">
                     <div class="card-custom">
                         <div class="card-header-custom">
                             <div class="text-xs text-muted fw-bold">REAL-TIME ORIENTATION</div>
                             <span class="status-pill is-none" id="trk-status-badge" role="status">No data</span>
                         </div>
                         <div class="card-body-custom pb-5">
                             <h4 class="mb-4">Tracker Position</h4>

                             <div class="tracker-map d-flex w-100 p-0 bg-white mb-4" style="border-radius: var(--radius-md); overflow: hidden;">

                                  <div id="azimuth-map-col" class="col-6 h-100 d-flex flex-column align-items-center justify-content-center position-relative border-end border-light transition-all">
                                     <div class="text-primary mb-2 position-absolute top-0 mt-2" style="font-size: 10px; font-weight: 700; letter-spacing: 1px;">AZIMUTH (TOP)</div>
                                     <div style="width: 140px; height: 140px; border-radius: 50%; border: 2px dashed rgba(100, 116, 139, 0.45); position: relative; display: flex; align-items: center; justify-content: center;">
                                         <div class="compass-mark" style="top: 2px;">N</div>
                                         <div class="compass-mark" style="bottom: 2px;">S</div>
                                         <div class="compass-mark" style="left: 4px;">W</div>
                                         <div class="compass-mark" style="right: 4px;">E</div>


                                         <div style="position: absolute; width: 4px; height: 140px; background: rgba(100, 116, 139, 0.25); border-radius: 2px; z-index: 1;"></div>


                                         <div id="trk-azimuth-ptr" style="position: absolute; width: 100%; height: 100%; transition: transform 0.5s ease; display: flex; align-items: center; justify-content: center; z-index: 2;">
                                             <div class="panel-diamond shadow-sm" style="transform: none !important;">
                                                 <span class="material-symbols-rounded icon" aria-hidden="true">solar_power</span>
                                             </div>
                                         </div>
                                     </div>
                                 </div>

                                 <div id="elevation-map-col" class="col-6 h-100 d-flex flex-column align-items-center justify-content-center position-relative transition-all">
                                     <div class="text-warning mb-2 position-absolute top-0 mt-2" style="font-size: 10px; font-weight: 700; letter-spacing: 1px;">ELEVATION (SIDE)</div>
                                     <div style="width: 140px; height: 140px; position: relative; display: flex; align-items: center; justify-content: center;">
                                         <div style="position: absolute; bottom: 30px; width: 120px; height: 2px; background: rgba(100, 116, 139, 0.45);"></div>
                                         <div style="position: absolute; left: 50%; bottom: 30px; width: 1px; height: 80px; border-left: 2px dashed rgba(100, 116, 139, 0.45);"></div>


                                         <div style="position: absolute; bottom: 30px; left: calc(50% - 3px); width: 6px; height: 35px; background: #64748B; border-radius: 2px;"></div>

                                         <div style="position: absolute; bottom: 35px; left: calc(50% - 25px); width: 6px; height: 20px; background: #94A3B8; border-radius: 3px; transform: rotate(30deg);"></div>

                                         <div style="position: absolute; bottom: 65px; left: 50%; width: 2px; height: 0; overflow: visible;">
                                             <div id="trk-elevation-ptr" style="position: absolute; bottom: -4px; left: -45px; width: 90px; height: 8px; background: #F59E0B; border-radius: 4px; transform-origin: 50% 50%; transition: transform 0.5s ease; box-shadow: 0 4px 6px rgba(245, 158, 11, 0.2);">
                                                 <div style="position: absolute; top: -3px; left: 5%; width: 90%; height: 3px; background: #FEF3C7; border-radius: 2px;"></div>

                                                 <div style="position: absolute; bottom: -20px; left: 15px; width: 3px; height: 25px; background: #CBD5E1; border-radius: 1px;"></div>
                                             </div>
                                         </div>
                                         <div style="position: absolute; bottom: 61px; left: calc(50% - 5px); width: 10px; height: 10px; background: #64748B; border-radius: 50%; z-index: 2;"></div>
                                     </div>
                                 </div>
                             </div>
                              <div class="row mt-4 flex-nowrap overflow-hidden">
                                  <div class="col-6 transition-all" id="azimuth-data-card">
                                      <div class="p-4 bg-light rounded-4 text-center border h-100 d-flex flex-column justify-content-center position-relative overflow-hidden">
                                           <span class="material-symbols-rounded position-absolute text-primary" style="font-size: 150px; opacity: 0.05; right: -20px; bottom: -20px; pointer-events: none;" aria-hidden="true">explore</span>
                                           <div class="text-muted fw-bold mb-3" style="letter-spacing: 2px;">AZIMUTH</div>
                                           <div class="display-3 fw-bolder text-dark mb-1"><span id="trk-azimuth" class="animated-value">--</span>°</div>
                                           <div class="text-primary fw-semibold" id="trk-azimuth-lbl">No data</div>
                                      </div>
                                  </div>
                                  <div class="col-6 transition-all" id="elevation-data-card">
                                      <div class="p-4 bg-light rounded-4 text-center border h-100 d-flex flex-column justify-content-center position-relative overflow-hidden">
                                           <span class="material-symbols-rounded position-absolute text-warning" style="font-size: 150px; opacity: 0.05; left: -20px; bottom: -20px; pointer-events: none;" aria-hidden="true">height</span>
                                           <div class="text-muted fw-bold mb-3" style="letter-spacing: 2px;">ELEVATION</div>
                                           <div class="display-3 fw-bolder text-dark mb-1"><span id="trk-elevation" class="animated-value">--</span>°</div>
                                           <div class="text-warning fw-semibold" id="trk-elevation-lbl">No data</div>
                                      </div>
                                  </div>
                              </div>
                         </div>
                     </div>
                 </div>
                 <div class="col-xl-4 d-flex flex-column gap-4">
                     <div class="card-custom">
                         <div class="card-header-custom pb-0"><div class="text-xs text-muted fw-bold">CONTROL LOGIC</div></div>
                         <div class="card-body-custom">
                             <h5>Tracking Mode</h5>
                              <div class="bg-light p-1 rounded-pill d-flex mb-4 mt-3 border">
                                  <button type="button" id="btn-trk-auto" class="btn text-muted rounded-pill flex-grow-1 fw-semibold transition-all" aria-pressed="false">AUTO</button>
                                  <button type="button" id="btn-trk-manual" class="btn text-muted rounded-pill flex-grow-1 fw-semibold transition-all" aria-pressed="false">MANUAL</button>
                              </div>
                             <div class="text-xs text-muted mb-1">ALGORITHM</div>
                             <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                                 <div id="algorithm-type" class="fw-semibold">LDR Sensors</div>
                                 <span class="material-symbols-rounded text-warning" aria-hidden="true">memory</span>
                             </div>
                             <div class="text-xs text-muted mb-1">LAST READING (WITA)</div>
                             <div class="d-flex justify-content-between align-items-center">
                                 <div class="fw-semibold" id="trk-last-reading">No data yet</div>
                                 <span class="material-symbols-rounded text-muted" aria-hidden="true">history</span>
                             </div>
                         </div>
                     </div>
                     <div class="card-custom flex-grow-1">
                          <div class="card-header-custom pb-0"><div class="text-xs text-muted fw-bold">MANUAL OVERRIDE</div></div>
                          <div class="card-body-custom d-flex flex-column justify-content-center">
                              <form id="manual-target-form" class="manual-target is-locked">
                                  <fieldset id="manual-target-fields" disabled>
                                      <div class="row g-3 mb-3">
                                          <div class="col-6" id="manual-azimuth-field">
                                              <label for="manual-azimuth" class="text-xs text-muted fw-bold mb-1">AZIMUTH</label>
                                              <div class="input-group">
                                                  <input type="number" class="form-control" id="manual-azimuth" min="0" max="360" step="any" inputmode="decimal" placeholder="0–360">
                                                  <span class="input-group-text">°</span>
                                              </div>
                                              <div class="manual-now">Now <span id="manual-azimuth-now">--</span>°</div>
                                          </div>
                                          <div class="col-6" id="manual-elevation-field">
                                              <label for="manual-elevation" class="text-xs text-muted fw-bold mb-1">ELEVATION</label>
                                              <div class="input-group">
                                                  <input type="number" class="form-control" id="manual-elevation" min="0" max="180" step="any" inputmode="decimal" placeholder="0–180">
                                                  <span class="input-group-text">°</span>
                                              </div>
                                              <div class="manual-now">Now <span id="manual-elevation-now">--</span>°</div>
                                          </div>
                                      </div>
                                      <button type="submit" class="btn btn-warning fw-semibold w-100" id="btn-manual-send">Move Panel</button>
                                  </fieldset>
                              </form>
                              <p class="manual-note" id="manual-note">Switch to Manual to move the panel.</p>
                          </div>
                     </div>
                 </div>
             </div>
             <div class="row g-4">
                 <div class="col-xl-6" id="trk-ldr-widget">
                     <div class="card-custom">
                         <div class="card-header-custom pb-0">
                             <div class="text-xs text-muted fw-bold">IRRADIANCE DETECTION</div>
                             <div class="text-end">
                                 <div class="text-xs text-muted">DIFF. MARGIN</div>
                                 <div class="fw-bold fs-5" id="dash-diff-margin">--</div>
                             </div>
                         </div>
                         <div class="card-body-custom">
                              <h4 class="mb-4">LDR Sensor Matrix</h4>
                              <div class="row g-2 text-center">

                                  <div class="col-12">
                                      <div class="p-3 bg-primary bg-opacity-10 border border-primary-subtle rounded-3" id="trk-box-ldr-nw">
                                          <div class="text-xs text-secondary fw-bold mb-1 ldr-label">LDR UTARA (NORTH)</div>
                                          <div class="fs-3 fw-bold text-dark lh-1 d-flex justify-content-center align-items-center gap-2">
                                              <span class="material-symbols-rounded text-warning fs-4" aria-hidden="true">light_mode</span>
                                              <span id="trk-ldr-nw" class="animated-value">--</span>
                                          </div>
                                          <div class="text-xs text-muted mt-1">RAW VALUE</div>
                                      </div>
                                  </div>

                                  <div class="col-6">
                                      <div class="p-3 bg-primary bg-opacity-10 border border-primary-subtle rounded-3 h-100" id="trk-box-ldr-sw">
                                          <div class="text-xs text-secondary fw-bold mb-1 ldr-label">LDR BARAT (WEST)</div>
                                          <div class="fs-3 fw-bold text-dark lh-1 d-flex justify-content-center align-items-center gap-2">
                                              <span class="material-symbols-rounded text-warning fs-4" aria-hidden="true">light_mode</span>
                                              <span id="trk-ldr-sw" class="animated-value">--</span>
                                          </div>
                                          <div class="text-xs text-muted mt-1">RAW VALUE</div>
                                      </div>
                                  </div>
                                  <div class="col-6">
                                      <div class="p-3 bg-primary bg-opacity-10 border border-primary-subtle rounded-3 h-100" id="trk-box-ldr-ne">
                                          <div class="text-xs text-secondary fw-bold mb-1 ldr-label">LDR TIMUR (EAST)</div>
                                          <div class="fs-3 fw-bold text-dark lh-1 d-flex justify-content-center align-items-center gap-2">
                                              <span class="material-symbols-rounded text-warning fs-4" aria-hidden="true">light_mode</span>
                                              <span id="trk-ldr-ne" class="animated-value">--</span>
                                          </div>
                                          <div class="text-xs text-muted mt-1">RAW VALUE</div>
                                      </div>
                                  </div>

                                  <div class="col-12">
                                      <div class="p-3 bg-primary bg-opacity-10 border border-primary-subtle rounded-3" id="trk-box-ldr-se">
                                          <div class="text-xs text-secondary fw-bold mb-1 ldr-label">LDR SELATAN (SOUTH)</div>
                                          <div class="fs-3 fw-bold text-dark lh-1 d-flex justify-content-center align-items-center gap-2">
                                              <span class="material-symbols-rounded text-warning fs-4" aria-hidden="true">light_mode</span>
                                              <span id="trk-ldr-se" class="animated-value">--</span>
                                          </div>
                                          <div class="text-xs text-muted mt-1">RAW VALUE</div>
                                      </div>
                                  </div>
                              </div>
                         </div>
                     </div>
                 </div>
             </div>
        </main>
        <main id="view-charts" class="page-view">
            <div class="d-flex justify-content-between align-items-end mb-4 analytics-head">
                <div>
                    <h1 class="mb-1" style="font-size: 2.5rem;">Analytics Canvas</h1>
                    <div class="text-muted fs-5">Historical diagnostics and yield performance.</div>
                </div>
                <div class="d-flex gap-2">
                    <select class="form-select border shadow-sm" id="analytics-range" aria-label="Period">
                        <option value="24h">Last 24 Hours</option>
                        <option value="7d">Last 7 Days</option>
                        <option value="30d">Last 30 Days</option>
                    </select>
                    <button type="button" class="btn btn-dark d-flex align-items-center gap-2 text-nowrap" id="export-analytics"><span class="material-symbols-rounded fs-5" aria-hidden="true">download</span> Export Data</button>
                </div>
            </div>
            <div class="card-custom mb-4">
                <div class="card-header-custom pb-0 pt-4 px-4 flex-wrap gap-2">
                    <div>
                        <div class="text-xs text-muted fw-bold mb-1">OUTPUT DIAGNOSTICS</div>
                        <h4 class="mb-0">Voltage (V) vs Current (A)</h4>
                    </div>
                    <div class="d-flex gap-3 text-sm bg-light p-2 rounded border chart-legend">
                        <div class="d-flex align-items-center gap-2"><span class="legend-dot legend-voltage"></span> Voltage Output</div>
                        <div class="border-start"></div>
                        <div class="d-flex align-items-center gap-2"><span class="legend-dot legend-current"></span> Current Flow</div>
                    </div>
                </div>
                <div class="card-body-custom px-4 pb-4">
                    <div style="height: 350px; position: relative;">
                        <canvas id="chartAnaVA" role="img" aria-label="Average voltage and current for the selected period"></canvas>
                        @include('partials.chart-state', ['id' => 'chartAnaVA'])
                    </div>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-12">
                    <div class="card-custom">
                        <div class="card-header-custom pb-0 pt-4 px-4">
                            <div>
                                <div class="text-xs text-muted fw-bold mb-1">ESTIMATED FROM AVERAGE POWER AND TIME</div>
                                <h4 class="mb-0">Energy Produced: <span id="ana-energy-total">--</span></h4>
                            </div>
                            <div class="bg-warning bg-opacity-25 p-2 rounded-circle text-warning border border-warning-subtle d-flex"><span class="material-symbols-rounded" aria-hidden="true">electric_bolt</span></div>
                        </div>
                        <div class="card-body-custom px-4 pb-4 mt-2">
                            <div style="height: 250px; position: relative;">
                                <canvas id="chartAnaConsumption" role="img" aria-label="Estimated energy for each part of the selected period"></canvas>
                                @include('partials.chart-state', ['id' => 'chartAnaConsumption'])
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card-custom">
                        <div class="card-header-custom pb-0 pt-4 px-4 flex-wrap gap-2">
                            <div>
                                <div class="text-xs text-muted fw-bold mb-1">RECORDED FROM OPEN-METEO EVERY 15 MINUTES</div>
                                <h4 class="mb-0">Weather History</h4>
                            </div>
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <div class="d-flex gap-3 text-sm bg-light p-2 rounded border chart-legend">
                                    <div class="d-flex align-items-center gap-2"><span class="legend-dot legend-temperature"></span> Temperature</div>
                                    <div class="border-start"></div>
                                    <div class="d-flex align-items-center gap-2"><span class="legend-dot legend-radiation"></span> Solar Radiation</div>
                                </div>
                                <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-2" id="export-weather"><span class="material-symbols-rounded fs-5" aria-hidden="true">download</span> Excel</button>
                            </div>
                        </div>
                        <div class="card-body-custom px-4 pb-4 mt-2">
                            <div style="height: 250px; position: relative;">
                                <canvas id="chartAnaWeather" role="img" aria-label="Recorded temperature and solar radiation for the selected period"></canvas>
                                @include('partials.chart-state', ['id' => 'chartAnaWeather'])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <main id="view-logs" class="page-view">
            <h2 class="mb-4">Recent Logs</h2>
            <form class="card-custom mb-4" id="log-filter-form">
                <div class="card-body-custom">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-4">
                            <label class="form-label text-xs fw-bold text-muted" for="filter-date-start">DATE RANGE (WITA)</label>
                            <div class="input-group">
                                <span class="input-group-text"><span class="material-symbols-rounded fs-5" aria-hidden="true">calendar_today</span></span>
                                 <input type="text" class="form-control" id="filter-date-start" placeholder="Start Date" aria-label="Start date">
                                 <span class="input-group-text border-start-0 border-end-0">to</span>
                                 <input type="text" class="form-control border-start-0" id="filter-date-end" placeholder="End Date" aria-label="End date">
                             </div>
                         </div>
                         <div class="col-sm-6 col-lg-2">
                              <label class="form-label text-xs fw-bold text-muted" for="filter-status">STATUS</label>
                              <select class="form-select" id="filter-status">
                                  <option value="All">All Statuses</option>
                                  <option value="Sun Locked">Sun Locked</option>
                                  <option value="Tracking...">Tracking...</option>
                                  <option value="Manual Override">Manual Override</option>
                                  <option value="Stopped">Stopped</option>
                              </select>
                         </div>
                         <div class="col-sm-6 col-lg-3">
                              <label class="form-label text-xs fw-bold text-muted" for="filter-subsystem">SUBSYSTEM</label>
                              <select class="form-select" id="filter-subsystem">
                                  <option value="panel1">Array Alpha</option>
                                  <option value="panel2">Array Beta</option>
                              </select>
                         </div>
                         <div class="col-lg-3 d-flex flex-wrap justify-content-end gap-2">
                              <button type="submit" class="btn btn-outline-secondary d-flex align-items-center gap-1"><span class="material-symbols-rounded fs-5" aria-hidden="true">filter_list</span> Apply</button>
                              <button type="button" class="btn btn-outline-secondary" id="filter-clear">Clear</button>
                              <button type="button" class="btn btn-dark d-flex align-items-center gap-1" id="export-logs"><span class="material-symbols-rounded fs-5" aria-hidden="true">file_download</span> Excel</button>
                         </div>
                     </div>
                 </div>
             </form>
             <div class="card-custom">
                  <div class="card-header-custom border-bottom pb-3 flex-wrap gap-2">
                      <div class="fw-bold d-flex align-items-center gap-2 fs-5">
                          <span style="width: 6px; height: 24px; background: #A16207; border-radius: 4px;"></span> Recent readings
                      </div>
                      <div class="text-sm text-muted d-flex align-items-center gap-2" id="log-updated" role="status">Loading</div>
                  </div>
                  <div class="card-body-custom p-0">
                      <div class="table-responsive">
                          <table class="table table-custom mb-0 text-center align-middle">
                              <thead>
                                  <tr>
                                      <th scope="col" class="text-start ps-4">TIMESTAMP (WITA)</th>
                                      <th scope="col">LOG ID</th>
                                      <th scope="col">VOLTAGE (V)</th>
                                      <th scope="col">CURRENT (A)</th>
                                      <th scope="col">POWER (W)</th>
                                      <th scope="col">WEATHER</th>
                                      <th scope="col">STATUS</th>
                                      <th scope="col" class="pe-4">ACTIONS</th>
                                  </tr>
                              </thead>
                              <tbody id="log-table-body">
                                  </tbody>
                          </table>
                      </div>
                      <div class="p-3 border-top d-flex justify-content-between align-items-center bg-light rounded-bottom log-footer">
                          <div class="text-sm text-muted" id="pagination-info">Showing 0 entries</div>
                          <nav aria-label="Log pages">
                              <ul class="pagination pagination-sm mb-0 flex-wrap" id="pagination-controls">
                              </ul>
                          </nav>
                      </div>
                  </div>
             </div>
        </main>
        <main id="view-settings" class="page-view">
            <h2 class="mb-4">System Settings</h2>
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card-custom mb-4">
                        <div class="card-header-custom pb-0"><h5 class="mb-0">Preferences</h5></div>
                        <div class="card-body-custom">
                            <div class="mb-3">
                                <label class="form-label text-sm fw-semibold" for="theme-select">Theme Mode</label>
                                  <select class="form-select" id="theme-select">
                                      <option value="light">Light (Default)</option>
                                      <option value="dark">Dark (Industrial)</option>
                                      <option value="auto">System Auto</option>
                                  </select>
                            </div>
                            <div>
                                <label class="form-label text-sm fw-semibold" for="poll-select">Data Polling Interval</label>
                                <select class="form-select" id="poll-select">
                                    <option value="2000">2 Seconds (Real-time)</option>
                                    <option value="5000">5 Seconds</option>
                                    <option value="30000">30 Seconds</option>
                                    <option value="60000">1 Minute</option>
                                </select>
                            </div>
                            <div class="text-xs text-muted mt-3">Applied immediately and saved in this browser.</div>
                        </div>
                    </div>
                </div>
                @if (config('tracker.dev_tools'))
                <div class="col-lg-6">
                    <div class="card-custom mb-4">
                          <div class="card-header-custom pb-0"><h5 class="mb-0 text-danger">Data Management</h5></div>
                          <div class="card-body-custom">
                              <div class="mb-3">
                                  <label class="form-label text-sm fw-semibold" for="dev-tools-subsystem">Target Subsystem</label>
                                  <select class="form-select mb-3" id="dev-tools-subsystem">
                                      <option value="all">All Arrays (Both Alpha & Beta)</option>
                                      <option value="panel1">Array Alpha (Dual-Axis)</option>
                                      <option value="panel2">Array Beta (Single-Axis)</option>
                                  </select>
                              </div>
                              <div class="mb-3 border-top pt-3">
                                  <div class="form-label text-sm fw-semibold">Generate Synthetic History</div>
                                  <div class="text-xs text-muted mb-2">Injects random data into the database for the selected subsystem for testing CSV exports and charts.</div>
                                  <button type="button" class="btn btn-outline-danger btn-sm" id="seed-data">Generate Random Data</button>
                              </div>
                              <div class="mb-3 border-top pt-3">
                                  <div class="form-label text-sm fw-semibold">Wipe System Data</div>
                                  <div class="text-xs text-muted mb-2">Permanently deletes all tracker logs for the selected subsystem. Recorded weather is kept.</div>
                                  <button type="button" class="btn btn-danger btn-sm d-flex align-items-center gap-1" id="wipe-data"><span class="material-symbols-rounded" style="font-size: 16px;" aria-hidden="true">delete_forever</span> Factory Reset Database</button>
                              </div>
                          </div>
                      </div>
                </div>
                @endif
            </div>
        </main>
    </div>

    <div class="modal fade" id="log-detail-modal" tabindex="-1" aria-labelledby="log-detail-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="log-detail-title">Reading</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="log-detail-body"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="toast-container" aria-live="polite"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/script.js') }}?v={{ filemtime(public_path('js/script.js')) }}"></script>
</body>
</html>
