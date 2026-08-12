<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Helios Tracker Pro</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
</head>
<body>
    <nav id="sidebar">
        <div class="brand-logo">
            <span class="material-symbols-rounded">solar_power</span>
            SOLARIS
        </div>
        <ul class="nav-menu">
            <li class="nav-item">
                <a class="nav-link active" id="nav-dashboard" onclick="navigateTo('dashboard', this)">
                    <span class="material-symbols-rounded">dashboard</span>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="nav-tracker" onclick="navigateTo('tracker', this)">
                    <span class="material-symbols-rounded">my_location</span>
                    Tracker Panel
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="nav-charts" onclick="navigateTo('charts', this)">
                    <span class="material-symbols-rounded">bar_chart</span>
                    Analytics Canvas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="nav-logs" onclick="navigateTo('logs', this)">
                    <span class="material-symbols-rounded">history</span>
                    Recent Logs
                </a>
            </li>
            <li class="nav-item mt-4 pt-4" style="border-top: 1px solid var(--border-color);">
                <a class="nav-link" onclick="navigateTo('settings', this)">
                    <span class="material-symbols-rounded">settings</span>
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
                          <span class="material-symbols-rounded text-primary" style="font-size: 20px;">solar_power</span>
                          <span id="panelDropdownText">Array Alpha (Dual Axis)</span>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2 p-2 w-100" aria-labelledby="panelDropdownBtn" style="border-radius: var(--radius-md); border: 1px solid var(--border-light) !important;">
                          <li>
                              <a class="dropdown-item rounded py-2 mb-1 fw-semibold d-flex align-items-center gap-3" href="#" onclick="switchPanel('panel1'); return false;">
                                  <div class="p-2 bg-primary bg-opacity-10 rounded d-flex align-items-center justify-content-center">
                                      <span class="material-symbols-rounded text-primary" style="font-size: 18px;">grid_view</span>
                                  </div>
                                  <div>
                                      <div class="text-dark">Array Alpha</div>
                                      <div class="text-muted" style="font-size: 0.75rem;">Dual Axis Tracking</div>
                                  </div>
                              </a>
                          </li>
                          <li>
                              <a class="dropdown-item rounded py-2 fw-semibold d-flex align-items-center gap-3" href="#" onclick="switchPanel('panel2'); return false;">
                                  <div class="p-2 bg-success bg-opacity-10 rounded d-flex align-items-center justify-content-center">
                                      <span class="material-symbols-rounded text-success" style="font-size: 18px;">crop_square</span>
                                  </div>
                                  <div>
                                      <div class="text-dark">Array Beta</div>
                                      <div class="text-muted" style="font-size: 0.75rem;">Single Axis Tracking</div>
                                  </div>
                              </a>
                          </li>
                      </ul>
                  </div>
                  <input type="hidden" id="panelSelector" value="panel1">
                  <div class="status-badge">
                    <div class="status-dot"></div>
                    System Active
                </div>
                <button class="icon-btn" title="Notifications" onclick="showToast('info', 'No new notifications')"><span class="material-symbols-rounded">notifications</span></button>
                <button class="icon-btn" title="Theme" onclick="toggleTheme()"><span class="material-symbols-rounded">dark_mode</span></button>
            </div>
        </header>
        <main id="view-dashboard" class="page-view active">
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card-custom">
                        <div class="card-body-custom">
                            <div class="text-xs text-muted mb-2">WAKTU LOKAL ESP32 (WITA)</div>
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fs-3 fw-bold" id="dash-time">08:00:43</div>
                                    <div class="text-muted-custom mt-1" id="dash-date">Oct 24, 2023</div>
                                </div>
                                <span class="material-symbols-rounded text-primary fs-2" style="opacity: 0.8">schedule</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card-custom">
                        <div class="card-body-custom">
                            <div class="text-xs text-muted mb-2">WEATHER</div>
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fs-4 fw-bold" id="weather-desc">Clear Sky <span id="weather-temp" class="fs-5 text-muted ms-1"></span></div>
                                    <div class="text-muted-custom mt-1"><span id="dash-irradiance">850</span> W/m² Irradiance</div>
                                </div>
                                <span id="weather-icon" class="material-symbols-rounded text-warning fs-1">light_mode</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card-custom">
                        <div class="card-body-custom d-flex justify-content-between align-items-center h-100">
                            <div>
                                <div class="text-xs text-muted fw-bold mb-1">TRACKING MODE</div>
                                <div class="fs-4 fw-bold text-warning" id="dash-mode">Automatic</div>
                                <div class="text-xs text-muted mt-1" id="dash-tracking-type">Dual-Axis Tracking Active</div>
                            </div>
                            <div class="d-flex gap-2">
                                <button id="btn-mode-auto" class="btn btn-warning fw-semibold px-4">Auto Tracking</button>
                                <button id="btn-mode-reset" class="btn btn-outline-secondary fw-semibold">Reset</button>
                                <button id="btn-mode-stop" class="btn btn-outline-danger fw-semibold">Stop</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-4 mb-4">
                <div class="col-md-4 d-flex flex-column gap-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <div class="d-flex align-items-center gap-2 fw-bold">
                                <span class="material-symbols-rounded text-warning">solar_power</span> Array Output
                            </div>
                        </div>
                        <div class="card-body-custom pt-0">
                            <div class="text-xs text-muted mb-1">CURRENT POWER</div>
                            <div class="d-flex align-items-baseline mb-4">
                                <span class="text-big animated-value" id="dash-power">4,250</span>
                                <span class="val-unit ms-2">W</span>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="text-xs text-muted mb-1">VOLTAGE</div>
                                    <div class="fs-4 fw-bold"><span id="dash-voltage" class="animated-value">345.2</span><span class="fs-6 text-muted ms-1">V</span></div>
                                </div>
                                <div class="col-6 border-start">
                                    <div class="text-xs text-muted mb-1">CURRENT</div>
                                    <div class="fs-4 fw-bold"><span id="dash-current" class="animated-value">12.3</span><span class="fs-6 text-muted ms-1">A</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-custom">
                         <div class="card-header-custom pb-2">
                            <div class="d-flex align-items-center gap-2 fw-bold text-sm">
                                <span class="material-symbols-rounded text-secondary" style="font-size:18px">sensors</span> LDR Sensor Grid
                            </div>
                        </div>
                        <div class="card-body-custom pt-0">
                            <div class="row g-2 text-center">
                                
                                <div class="col-12">
                                    <div class="p-3 bg-primary bg-opacity-10 border border-primary-subtle rounded-3" id="dash-box-ldr-nw">
                                        <div class="text-xs text-secondary fw-bold mb-1 ldr-label">LDR UTARA (NORTH)</div>
                                        <div class="fs-4 fw-bold text-dark d-flex justify-content-center align-items-center gap-2">
                                            <span class="material-symbols-rounded text-warning fs-5">light_mode</span>
                                            <span id="dash-ldr-tl" class="animated-value">880</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-6">
                                    <div class="p-3 bg-primary bg-opacity-10 border border-primary-subtle rounded-3 h-100" id="dash-box-ldr-sw">
                                        <div class="text-xs text-secondary fw-bold mb-1 ldr-label">LDR BARAT (WEST)</div>
                                        <div class="fs-4 fw-bold text-dark d-flex justify-content-center align-items-center gap-2">
                                            <span class="material-symbols-rounded text-warning fs-5">light_mode</span>
                                            <span id="dash-ldr-bl" class="animated-value">850</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3 bg-primary bg-opacity-10 border border-primary-subtle rounded-3 h-100" id="dash-box-ldr-ne">
                                        <div class="text-xs text-secondary fw-bold mb-1 ldr-label">LDR TIMUR (EAST)</div>
                                        <div class="fs-4 fw-bold text-dark d-flex justify-content-center align-items-center gap-2">
                                            <span class="material-symbols-rounded text-warning fs-5">light_mode</span>
                                            <span id="dash-ldr-tr" class="animated-value">820</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="p-3 bg-primary bg-opacity-10 border border-primary-subtle rounded-3" id="dash-box-ldr-se">
                                        <div class="text-xs text-secondary fw-bold mb-1 ldr-label">LDR SELATAN (SOUTH)</div>
                                        <div class="fs-4 fw-bold text-dark d-flex justify-content-center align-items-center gap-2">
                                            <span class="material-symbols-rounded text-warning fs-5">light_mode</span>
                                            <span id="dash-ldr-br" class="animated-value">790</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 d-flex flex-column gap-4">
                    
                    <div class="card-custom" style="cursor: pointer; transition: transform 0.2s;" onclick="navigateTo('tracker', document.getElementById('nav-tracker'))" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                        <div class="card-header-custom pb-0">
                            <div class="d-flex align-items-center gap-2 fw-bold">
                                <span class="material-symbols-rounded text-warning">explore</span> Tracker Position
                            </div>
                        </div>
                        <div class="card-body-custom pt-3">
                            <div class="tracker-map d-flex w-100 p-0 bg-white" style="border-radius: var(--radius-md); overflow: hidden;">
                                
                                <div id="dash-azimuth-map-col" class="col-6 h-100 d-flex flex-column align-items-center justify-content-center position-relative border-end border-light transition-all">
                                    <div class="text-primary mb-2 position-absolute top-0 mt-2" style="font-size: 10px; font-weight: 700; letter-spacing: 1px;">AZIMUTH (TOP)</div>
                                    <div style="width: 140px; height: 140px; border-radius: 50%; border: 2px dashed rgba(0, 0, 0, 0.1); position: relative; display: flex; align-items: center; justify-content: center;">
                                        <div style="position: absolute; top: -20px; color: #64748B; font-size: 12px; font-weight: bold;">N</div>
                                        <div style="position: absolute; bottom: -20px; color: #64748B; font-size: 12px; font-weight: bold;">S</div>
                                        <div style="position: absolute; left: -20px; color: #64748B; font-size: 12px; font-weight: bold;">W</div>
                                        <div style="position: absolute; right: -20px; color: #64748B; font-size: 12px; font-weight: bold;">E</div>
                                        
                                        
                                        <div style="position: absolute; width: 4px; height: 140px; background: rgba(0,0,0,0.1); border-radius: 2px; z-index: 1;"></div>
                                        
                                        
                                        <div id="dash-azimuth-ptr" style="position: absolute; width: 100%; height: 100%; transition: transform 0.5s ease; display: flex; align-items: center; justify-content: center; z-index: 2;">
                                            
                                            <div class="panel-diamond shadow-sm" style="transform: none !important;">
                                                <span class="material-symbols-rounded icon">solar_power</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="position-absolute bottom-0 mb-2 px-3 py-1 bg-light border border-light rounded-pill text-dark shadow-sm" style="font-size: 10px; font-weight: 700;">
                                        <span id="map-az">180</span>°
                                    </div>
                                </div>
                                
                                
                                <div id="dash-elevation-map-col" class="col-6 h-100 d-flex flex-column align-items-center justify-content-center position-relative transition-all">
                                    <div class="text-warning mb-2 position-absolute top-0 mt-2" style="font-size: 10px; font-weight: 700; letter-spacing: 1px;">ELEVATION (SIDE)</div>
                                    <div style="width: 140px; height: 140px; position: relative; display: flex; align-items: center; justify-content: center;">
                                        
                                        <div style="position: absolute; bottom: 30px; width: 120px; height: 2px; background: rgba(0,0,0,0.1);"></div>
                                        
                                        <div style="position: absolute; left: 50%; bottom: 30px; width: 1px; height: 80px; border-left: 2px dashed rgba(0,0,0,0.1);"></div>
                                        
                                        
                                        <div style="position: absolute; bottom: 30px; left: calc(50% - 3px); width: 6px; height: 35px; background: #64748B; border-radius: 2px;"></div>
                                        
                                        <div style="position: absolute; bottom: 35px; left: calc(50% - 25px); width: 6px; height: 20px; background: #94A3B8; border-radius: 3px; transform: rotate(30deg);"></div>
                                        
                                        
                                        <div style="position: absolute; bottom: 65px; left: 50%; width: 2px; height: 0; overflow: visible;">
                                            
                                            <div id="dash-elevation-ptr" style="position: absolute; bottom: -4px; left: -45px; width: 90px; height: 8px; background: #F59E0B; border-radius: 4px; transform-origin: 50% 50%; transition: transform 0.5s ease; box-shadow: 0 4px 6px rgba(245, 158, 11, 0.2);">
                                                
                                                <div style="position: absolute; top: -3px; left: 5%; width: 90%; height: 3px; background: #FEF3C7; border-radius: 2px;"></div>
                                                
                                                <div style="position: absolute; bottom: -20px; left: 15px; width: 3px; height: 25px; background: #CBD5E1; border-radius: 1px;"></div>
                                            </div>
                                        </div>
                                        
                                        <div style="position: absolute; bottom: 61px; left: calc(50% - 5px); width: 10px; height: 10px; background: #1E293B; border-radius: 50%; z-index: 2;"></div>
                                        
                                    </div>
                                    <div class="position-absolute bottom-0 mb-2 px-3 py-1 bg-light border border-light rounded-pill text-dark shadow-sm" style="font-size: 10px; font-weight: 700;">
                                        <span id="map-el">45</span>°
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3 flex-nowrap overflow-hidden">
                                <div id="dash-azimuth-val-col" class="col-6 transition-all">
                                    <div class="p-2 bg-light rounded text-center border">
                                        <div class="text-xs text-muted fw-bold">AZIMUTH</div>
                                        <div class="fw-bold fs-5 text-dark"><span id="dash-azimuth" class="animated-value">180.2</span>°</div>
                                    </div>
                                </div>
                                <div id="dash-elevation-val-col" class="col-6 transition-all">
                                    <div class="p-2 bg-light rounded text-center border">
                                        <div class="text-xs text-muted fw-bold">ELEVATION</div>
                                        <div class="fw-bold fs-5 text-dark"><span id="dash-elevation" class="animated-value">45.2</span>°</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 d-flex flex-column gap-4">
                    <div class="card-custom">
                        <div class="card-header-custom pb-0">
                            <div class="d-flex align-items-center gap-2 fw-bold">
                                <span class="material-symbols-rounded text-primary">show_chart</span> Power Gen (12h)
                            </div>
                            <span class="badge bg-light text-secondary border">WATT</span>
                        </div>
                        <div class="card-body-custom pt-2">
                            <div style="height: 180px; position: relative;">
                                <canvas id="chartDashPower"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="card-custom">
                        <div class="card-header-custom pb-0">
                            <div class="d-flex align-items-center gap-2 fw-bold">
                                <span class="material-symbols-rounded text-primary">ssid_chart</span> Voltage vs Current
                            </div>
                        </div>
                        <div class="card-body-custom pt-2">
                             <div style="height: 180px; position: relative;">
                                <canvas id="chartDashVA"></canvas>
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
                <div class="col-md-8">
                     <div class="card-custom">
                         <div class="card-header-custom">
                             <div class="text-xs text-muted fw-bold">REAL-TIME ORIENTATION</div>
                             <span class="badge bg-success bg-opacity-25 text-success border border-success-subtle"><span class="material-symbols-rounded" style="font-size: 14px;">lock</span> Tracking Lock</span>
                         </div>
                         <div class="card-body-custom pb-5">
                             <h4 class="mb-4">Tracker Position</h4>
                             
                             <div class="tracker-map d-flex w-100 p-0 bg-white mb-4" style="border-radius: var(--radius-md); overflow: hidden;">
                                 
                                  <div id="azimuth-map-col" class="col-6 h-100 d-flex flex-column align-items-center justify-content-center position-relative border-end border-light transition-all">
                                     <div class="text-primary mb-2 position-absolute top-0 mt-2" style="font-size: 10px; font-weight: 700; letter-spacing: 1px;">AZIMUTH (TOP)</div>
                                     <div style="width: 140px; height: 140px; border-radius: 50%; border: 2px dashed rgba(0, 0, 0, 0.1); position: relative; display: flex; align-items: center; justify-content: center;">
                                         <div style="position: absolute; top: -20px; color: #64748B; font-size: 12px; font-weight: bold;">N</div>
                                         <div style="position: absolute; bottom: -20px; color: #64748B; font-size: 12px; font-weight: bold;">S</div>
                                         <div style="position: absolute; left: -20px; color: #64748B; font-size: 12px; font-weight: bold;">W</div>
                                         <div style="position: absolute; right: -20px; color: #64748B; font-size: 12px; font-weight: bold;">E</div>
                                         
                                         
                                         <div style="position: absolute; width: 4px; height: 140px; background: rgba(0,0,0,0.1); border-radius: 2px; z-index: 1;"></div>
                                         
                                         
                                         <div id="trk-azimuth-ptr" style="position: absolute; width: 100%; height: 100%; transition: transform 0.5s ease; display: flex; align-items: center; justify-content: center; z-index: 2;">
                                             <div class="panel-diamond shadow-sm" style="transform: none !important;">
                                                 <span class="material-symbols-rounded icon">solar_power</span>
                                             </div>
                                         </div>
                                     </div>
                                 </div>
                                 
                                 <div id="elevation-map-col" class="col-6 h-100 d-flex flex-column align-items-center justify-content-center position-relative transition-all">
                                     <div class="text-warning mb-2 position-absolute top-0 mt-2" style="font-size: 10px; font-weight: 700; letter-spacing: 1px;">ELEVATION (SIDE)</div>
                                     <div style="width: 140px; height: 140px; position: relative; display: flex; align-items: center; justify-content: center;">
                                         <div style="position: absolute; bottom: 30px; width: 120px; height: 2px; background: rgba(0,0,0,0.1);"></div>
                                         <div style="position: absolute; left: 50%; bottom: 30px; width: 1px; height: 80px; border-left: 2px dashed rgba(0,0,0,0.1);"></div>
                                         
                                         
                                         <div style="position: absolute; bottom: 30px; left: calc(50% - 3px); width: 6px; height: 35px; background: #64748B; border-radius: 2px;"></div>
                                         
                                         <div style="position: absolute; bottom: 35px; left: calc(50% - 25px); width: 6px; height: 20px; background: #94A3B8; border-radius: 3px; transform: rotate(30deg);"></div>
                                         
                                         <div style="position: absolute; bottom: 65px; left: 50%; width: 2px; height: 0; overflow: visible;">
                                             <div id="trk-elevation-ptr" style="position: absolute; bottom: -4px; left: -45px; width: 90px; height: 8px; background: #F59E0B; border-radius: 4px; transform-origin: 50% 50%; transition: transform 0.5s ease; box-shadow: 0 4px 6px rgba(245, 158, 11, 0.2);">
                                                 <div style="position: absolute; top: -3px; left: 5%; width: 90%; height: 3px; background: #FEF3C7; border-radius: 2px;"></div>
                                                 
                                                 <div style="position: absolute; bottom: -20px; left: 15px; width: 3px; height: 25px; background: #CBD5E1; border-radius: 1px;"></div>
                                             </div>
                                         </div>
                                         <div style="position: absolute; bottom: 61px; left: calc(50% - 5px); width: 10px; height: 10px; background: #1E293B; border-radius: 50%; z-index: 2;"></div>
                                     </div>
                                 </div>
                             </div>
                              <div class="row mt-4 flex-nowrap overflow-hidden">
                                  <div class="col-md-6 transition-all" id="azimuth-data-card">
                                      <div class="p-4 bg-light rounded-4 text-center border h-100 d-flex flex-column justify-content-center position-relative overflow-hidden">
                                           <span class="material-symbols-rounded position-absolute text-primary" style="font-size: 150px; opacity: 0.05; right: -20px; bottom: -20px; pointer-events: none;">explore</span>
                                           <div class="text-muted fw-bold mb-3" style="letter-spacing: 2px;">AZIMUTH</div>
                                           <div class="display-3 fw-bolder text-dark mb-1"><span id="trk-azimuth" class="animated-value">145.0</span>°</div>
                                           <div class="text-primary fw-semibold" id="trk-azimuth-lbl">South-East Alignment</div>
                                      </div>
                                  </div>
                                  <div class="col-md-6 transition-all" id="elevation-data-card">
                                      <div class="p-4 bg-light rounded-4 text-center border h-100 d-flex flex-column justify-content-center position-relative overflow-hidden">
                                           <span class="material-symbols-rounded position-absolute text-warning" style="font-size: 150px; opacity: 0.05; left: -20px; bottom: -20px; pointer-events: none;">height</span>
                                           <div class="text-muted fw-bold mb-3" style="letter-spacing: 2px;">ELEVATION</div>
                                           <div class="display-3 fw-bolder text-dark mb-1"><span id="trk-elevation" class="animated-value">42.0</span>°</div>
                                           <div class="text-warning fw-semibold" id="trk-elevation-lbl">Optimal Tilt</div>
                                      </div>
                                  </div>
                              </div>
                         </div>
                     </div>
                 </div>
                 <div class="col-md-4 d-flex flex-column gap-4">
                     <div class="card-custom">
                         <div class="card-header-custom pb-0"><div class="text-xs text-muted fw-bold">CONTROL LOGIC</div></div>
                         <div class="card-body-custom">
                             <h5>Tracking Mode</h5>
                              <div class="bg-light p-1 rounded-pill d-flex mb-4 mt-3 border">
                                  <button id="btn-trk-auto" onclick="setTrackerMode('Automatic')" class="btn btn-warning rounded-pill flex-grow-1 fw-semibold transition-all">AUTO</button>
                                  <button id="btn-trk-manual" onclick="setTrackerMode('Manual')" class="btn text-muted rounded-pill flex-grow-1 fw-semibold transition-all">MANUAL</button>
                              </div>
                             <div class="text-xs text-muted mb-1">ALGORITHM</div>
                             <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                                 <div class="fw-semibold">Astronomical + LDR</div>
                                 <span class="material-symbols-rounded text-warning">memory</span>
                             </div>
                             <div class="text-xs text-muted mb-1">LAST CALIBRATION</div>
                             <div class="d-flex justify-content-between align-items-center">
                                 <div class="fw-semibold">04:30 AM</div>
                                 <span class="material-symbols-rounded text-muted">history</span>
                             </div>
                         </div>
                     </div>
                     <div class="card-custom flex-grow-1">
                          <div class="card-header-custom pb-0"><div class="text-xs text-muted fw-bold">MANUAL OVERRIDE</div></div>
                          <div class="card-body-custom d-flex flex-column justify-content-center">
                              <div class="d-pad-container" id="d-pad-container">
                                  <div></div>
                                  <button class="d-btn" onclick="sendManualCommand('Up')"><span class="material-symbols-rounded">expand_less</span></button>
                                  <div></div>
                                  <button class="d-btn transition-all" id="btn-manual-left" onclick="sendManualCommand('Left')"><span class="material-symbols-rounded">chevron_left</span></button>
                                  <button class="d-btn center transition-all" onclick="sendManualCommand('Reset')"><span class="material-symbols-rounded">adjust</span></button>
                                  <button class="d-btn transition-all" id="btn-manual-right" onclick="sendManualCommand('Right')"><span class="material-symbols-rounded">chevron_right</span></button>
                                  <div></div>
                                  <button class="d-btn" onclick="sendManualCommand('Down')"><span class="material-symbols-rounded">expand_more</span></button>
                                  <div></div>
                              </div>
                          </div>
                     </div>
                 </div>
             </div>
             <div class="row g-4">
                 <div class="col-md-6">
                     <div class="card-custom">
                         <div class="card-header-custom pb-0">
                             <div class="text-xs text-muted fw-bold">ACTUATOR HEALTH</div>
                             <button class="icon-btn border" onclick="showToast('info', 'Health diagnostics running...')"><span class="material-symbols-rounded">more_vert</span></button>
                         </div>
                         <div class="card-body-custom pt-4">
                             <div class="motor-stat-box mb-4 transition-all" id="azimuth-motor-health">
                                 <div class="d-flex justify-content-between align-items-center mb-3">
                                     <div class="d-flex align-items-center gap-2 fw-semibold">
                                         <span class="material-symbols-rounded text-muted" id="m1-icon">swap_horiz</span> <span id="m1-label">Azimuth Drive (M1)</span>
                                     </div>
                                     <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle">NOMINAL</span>
                                 </div>
                                 <div class="row text-sm">
                                     <div class="col-6 border-end">
                                         <div class="d-flex justify-content-between mb-1"><span class="text-muted">Current Load</span> <span class="fw-bold"><span id="trk-mot1-load">2.4</span>A</span></div>
                                         <div class="progress" style="height: 6px;"><div class="progress-bar bg-warning" style="width: 40%"></div></div>
                                     </div>
                                     <div class="col-6 ps-3">
                                         <div class="d-flex justify-content-between mb-1"><span class="text-muted">Temperature</span> <span class="fw-bold"><span id="trk-mot1-temp">38</span>°C</span></div>
                                         <div class="progress" style="height: 6px;"><div class="progress-bar bg-warning" style="width: 38%"></div></div>
                                     </div>
                                 </div>
                             </div>
                             <div class="motor-stat-box">
                                 <div class="d-flex justify-content-between align-items-center mb-3">
                                     <div class="d-flex align-items-center gap-2 fw-semibold">
                                         <span class="material-symbols-rounded text-muted" id="m2-icon">swap_vert</span> <span id="m2-label">Elevation Drive (M2)</span>
                                     </div>
                                     <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle">NOMINAL</span>
                                 </div>
                                 <div class="row text-sm">
                                     <div class="col-6 border-end">
                                         <div class="d-flex justify-content-between mb-1"><span class="text-muted">Current Load</span> <span class="fw-bold"><span id="trk-mot2-load">3.1</span>A</span></div>
                                         <div class="progress" style="height: 6px;"><div class="progress-bar bg-warning" id="trk-mot2-load-bar" style="width: 55%"></div></div>
                                     </div>
                                     <div class="col-6 ps-3">
                                         <div class="d-flex justify-content-between mb-1"><span class="text-muted">Temperature</span> <span class="fw-bold"><span id="trk-mot2-temp">42</span>°C</span></div>
                                         <div class="progress" style="height: 6px;"><div class="progress-bar bg-warning" style="width: 42%"></div></div>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>
                 <div class="col-md-6">
                     <div class="card-custom">
                         <div class="card-header-custom pb-0">
                             <div class="text-xs text-muted fw-bold">IRRADIANCE DETECTION</div>
                             <div class="text-end">
                                 <div class="text-xs text-muted">DIFF. MARGIN</div>
                                 <div class="fw-bold text-warning fs-5">2.4%</div>
                             </div>
                         </div>
                         <div class="card-body-custom">
                              <h4 class="mb-4">LDR Sensor Matrix</h4>
                              <div class="row g-2 text-center">
                                  
                                  <div class="col-12">
                                      <div class="p-3 bg-primary bg-opacity-10 border border-primary-subtle rounded-3" id="trk-box-ldr-nw">
                                          <div class="text-xs text-secondary fw-bold mb-1 ldr-label">LDR UTARA (NORTH)</div>
                                          <div class="fs-3 fw-bold text-dark lh-1 d-flex justify-content-center align-items-center gap-2">
                                              <span class="material-symbols-rounded text-warning fs-4">light_mode</span>
                                              <span id="trk-ldr-nw" class="animated-value">885</span>
                                          </div>
                                          <div class="text-xs text-muted mt-1">W/m²</div>
                                      </div>
                                  </div>
                                  
                                  <div class="col-6">
                                      <div class="p-3 bg-primary bg-opacity-10 border border-primary-subtle rounded-3 h-100" id="trk-box-ldr-sw">
                                          <div class="text-xs text-secondary fw-bold mb-1 ldr-label">LDR BARAT (WEST)</div>
                                          <div class="fs-3 fw-bold text-dark lh-1 d-flex justify-content-center align-items-center gap-2">
                                              <span class="material-symbols-rounded text-warning fs-4">light_mode</span>
                                              <span id="trk-ldr-sw" class="animated-value">860</span>
                                          </div>
                                          <div class="text-xs text-muted mt-1">W/m²</div>
                                      </div>
                                  </div>
                                  <div class="col-6">
                                      <div class="p-3 bg-primary bg-opacity-10 border border-primary-subtle rounded-3 h-100" id="trk-box-ldr-ne">
                                          <div class="text-xs text-secondary fw-bold mb-1 ldr-label">LDR TIMUR (EAST)</div>
                                          <div class="fs-3 fw-bold text-dark lh-1 d-flex justify-content-center align-items-center gap-2">
                                              <span class="material-symbols-rounded text-warning fs-4">light_mode</span>
                                              <span id="trk-ldr-ne" class="animated-value">912</span>
                                          </div>
                                          <div class="text-xs text-muted mt-1">W/m²</div>
                                      </div>
                                  </div>
                                  
                                  <div class="col-12">
                                      <div class="p-3 bg-primary bg-opacity-10 border border-primary-subtle rounded-3" id="trk-box-ldr-se">
                                          <div class="text-xs text-secondary fw-bold mb-1 ldr-label">LDR SELATAN (SOUTH)</div>
                                          <div class="fs-3 fw-bold text-dark lh-1 d-flex justify-content-center align-items-center gap-2">
                                              <span class="material-symbols-rounded text-warning fs-4">light_mode</span>
                                              <span id="trk-ldr-se" class="animated-value">874</span>
                                          </div>
                                          <div class="text-xs text-muted mt-1">W/m²</div>
                                      </div>
                                  </div>
                              </div>
                         </div>
                     </div>
                 </div>
             </div>
        </main>
        <main id="view-charts" class="page-view">
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <h1 class="mb-1" style="font-size: 2.5rem;">Analytics Canvas</h1>
                    <div class="text-muted fs-5">Deep-dive historical diagnostics and yield performance.</div>
                </div>
                <div class="d-flex gap-2">
                    <select class="form-select border shadow-sm">
                        <option>Last 7 Days</option>
                        <option>Last 30 Days</option>
                        <option>Custom Range</option>
                    </select>
                    <button class="btn btn-dark d-flex align-items-center gap-2" onclick="exportCSV('analytics')"><span class="material-symbols-rounded fs-5">download</span> Export Data</button>
                </div>
            </div>
            <div class="card-custom mb-4">
                <div class="card-header-custom pb-0 pt-4 px-4">
                    <div>
                        <div class="text-xs text-muted fw-bold mb-1">INVERTER OUTPUT DIAGNOSTICS</div>
                        <h4 class="mb-0">Voltage (V) vs Current (A)</h4>
                    </div>
                    <div class="d-flex gap-3 text-sm bg-light p-2 rounded border">
                        <div class="d-flex align-items-center gap-2"><span style="width: 12px; height: 12px; border-radius: 50%; background: #854D0E;"></span> Voltage Output</div>
                        <div class="border-start"></div>
                        <div class="d-flex align-items-center gap-2"><span style="width: 12px; height: 12px; border-radius: 50%; background: #475569;"></span> Current Flow</div>
                    </div>
                </div>
                <div class="card-body-custom px-4 pb-4">
                    <div style="height: 350px; position: relative;">
                        <canvas id="chartAnaVA"></canvas>
                    </div>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-12">
                    <div class="card-custom">
                        <div class="card-header-custom pb-0 pt-4 px-4">
                            <div>
                                <div class="text-xs text-muted fw-bold mb-1">LOAD PROFILE</div>
                                <h4 class="mb-0">Energy Consumption</h4>
                            </div>
                            <div class="bg-warning bg-opacity-25 p-2 rounded-circle text-warning border border-warning-subtle d-flex"><span class="material-symbols-rounded">electric_bolt</span></div>
                        </div>
                        <div class="card-body-custom px-4 pb-4 mt-2">
                            <div style="height: 250px; position: relative;">
                                <canvas id="chartAnaConsumption"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <main id="view-logs" class="page-view">
            <h2 class="mb-4">Recent Logs</h2>
            <div class="card-custom mb-4">
                <div class="card-body-custom">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label text-xs fw-bold text-muted">DATE RANGE</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><span class="material-symbols-rounded fs-5">calendar_today</span></span>
                                 <input type="text" class="form-control" id="filter-date-start" placeholder="Start Date">
                                 <span class="input-group-text bg-white border-start-0 border-end-0">to</span>
                                 <input type="text" class="form-control border-start-0" id="filter-date-end" placeholder="End Date">
                             </div>
                         </div>
                         <div class="col-md-3">
                              <label class="form-label text-xs fw-bold text-muted">STATUS</label>
                              <select class="form-select" id="filter-status">
                                  <option value="All">All Statuses</option>
                                  <option value="Optimal">Optimal</option>
                                  <option value="Warning">Warning</option>
                                  <option value="Offline">Offline</option>
                                  <option value="High Load">High Load</option>
                              </select>
                         </div>
                         <div class="col-md-3">
                              <label class="form-label text-xs fw-bold text-muted">SUBSYSTEM</label>
                              <select class="form-select" id="filter-subsystem">
                                  <option>All Arrays</option>
                                  <option>Array Alpha</option>
                                  <option>Array Beta</option>
                              </select>
                         </div>
                         <div class="col-md-2 d-flex justify-content-end gap-2">
                              <button class="btn btn-outline-secondary d-flex align-items-center gap-1" onclick="filterLogs()"><span class="material-symbols-rounded fs-5">filter_list</span> Apply</button>
                              <button class="btn btn-dark d-flex align-items-center gap-1" onclick="exportCSV('logs')"><span class="material-symbols-rounded fs-5">file_download</span> CSV</button>
                         </div>
                     </div>
                 </div>
             </div>
             <div class="card-custom">
                  <div class="card-header-custom border-bottom pb-3">
                      <div class="fw-bold d-flex align-items-center gap-2 fs-5">
                          <span style="width: 6px; height: 24px; background: #A16207; border-radius: 4px;"></span> Recent energy received
                      </div>
                      <div class="text-sm text-muted d-flex align-items-center gap-2">
                          <div style="width:8px; height:8px; background:var(--success); border-radius:50%;"></div> Live Updates Active
                      </div>
                  </div>
                  <div class="card-body-custom p-0">
                      <div class="table-responsive">
                          <table class="table table-custom mb-0 text-center align-middle">
                              <thead>
                                  <tr>
                                      <th class="text-start ps-4">TIMESTAMP (UTC)</th>
                                      <th>NODE ID</th>
                                      <th>VOLTAGE (V)</th>
                                      <th>CURRENT (A)</th>
                                      <th>ENERGY (KWH)</th>
                                      <th>STATUS</th>
                                      <th class="pe-4">ACTIONS</th>
                                  </tr>
                              </thead>
                              <tbody id="log-table-body">
                                  </tbody>
                          </table>
                      </div>
                      <div class="p-3 border-top d-flex justify-content-between align-items-center bg-light rounded-bottom">
                          <div class="text-sm text-muted">Showing 1 to 7 of 1,024 entries</div>
                          <ul class="pagination pagination-sm mb-0">
                              <li class="page-item disabled"><a class="page-link" href="#">&laquo;</a></li>
                              <li class="page-item active"><a class="page-link bg-warning border-warning text-dark" href="#">1</a></li>
                              <li class="page-item"><a class="page-link text-dark" href="#">2</a></li>
                              <li class="page-item"><a class="page-link text-dark" href="#">3</a></li>
                              <li class="page-item disabled"><a class="page-link text-dark" href="#">...</a></li>
                              <li class="page-item"><a class="page-link text-dark" href="#">146</a></li>
                              <li class="page-item"><a class="page-link text-dark" href="#">&raquo;</a></li>
                          </ul>
                      </div>
                  </div>
             </div>
        </main>
        <main id="view-settings" class="page-view">
            <h2 class="mb-4">System Settings</h2>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card-custom mb-4">
                        <div class="card-header-custom pb-0"><h5 class="mb-0">Preferences</h5></div>
                        <div class="card-body-custom">
                            <div class="mb-3">
                                <label class="form-label text-sm fw-semibold">Theme Mode</label>
                                  <select class="form-select" id="theme-select" onchange="changeTheme(this.value)">
                                      <option value="light">Light (Default)</option>
                                      <option value="dark">Dark (Industrial)</option>
                                      <option value="auto">System Auto</option>
                                  </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-sm fw-semibold">Language</label>
                                <select class="form-select">
                                    <option>English (US)</option>
                                    <option>Bahasa Indonesia</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card-custom mb-4">
                        <div class="card-header-custom pb-0"><h5 class="mb-0">IoT Configuration</h5></div>
                        <div class="card-body-custom">
                            <div class="mb-3">
                                <label class="form-label text-sm fw-semibold">Data Polling Interval</label>
                                <select class="form-select">
                                    <option>2 Seconds (Real-time)</option>
                                    <option>5 Seconds</option>
                                    <option>30 Seconds</option>
                                    <option>1 Minute</option>
                                </select>
                            </div>
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" role="switch" id="simMode" checked>
                                <label class="form-check-label fw-semibold" for="simMode">Enable Dummy Data Simulation</label>
                                <div class="text-xs text-muted">Generates synthetic data for preview without hardware.</div>
                            </div>
                            <div class="mt-4 pt-3 border-top d-flex gap-2">
                                <button class="btn btn-warning fw-semibold px-4" onclick="showToast('success', 'Configuration saved successfully!')">Save Configuration</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-custom mb-4">
                          <div class="card-header-custom pb-0"><h5 class="mb-0 text-danger">Data Management</h5></div>
                          <div class="card-body-custom">
                              <div class="mb-3">
                                  <label class="form-label text-sm fw-semibold">Target Subsystem</label>
                                  <select class="form-select mb-3" id="dev-tools-subsystem">
                                      <option value="all">All Arrays (Both Alpha & Beta)</option>
                                      <option value="panel1">Array Alpha (Dual-Axis)</option>
                                      <option value="panel2">Array Beta (Single-Axis)</option>
                                  </select>
                              </div>
                              <div class="mb-3 border-top pt-3">
                                  <label class="form-label text-sm fw-semibold">Generate Synthetic History</label>
                                  <div class="text-xs text-muted mb-2">Injects random data into the database for the selected subsystem for testing CSV exports and charts.</div>
                                  <button class="btn btn-outline-danger btn-sm" onclick="seedDatabase()">Generate Random Data</button>
                              </div>
                              <div class="mb-3 border-top pt-3">
                                  <label class="form-label text-sm fw-semibold">Wipe System Data</label>
                                  <div class="text-xs text-muted mb-2">Permanently deletes all tracker logs, charts, and history for the selected subsystem.</div>
                                  <button class="btn btn-danger btn-sm d-flex align-items-center gap-1" onclick="resetDatabase()"><span class="material-symbols-rounded" style="font-size: 16px;">delete_forever</span> Factory Reset Database</button>
                              </div>
                          </div>
                      </div>       </div>
                </div>
            </div>
        </main>
    </div>
    
    <div id="toast-container"></div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/script.js') }}"></script>
</body>
</html>