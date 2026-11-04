<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solaris Tracker</title>
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.js"></script>
</head>
<body data-axis="dual">
    <nav id="sidebar" aria-label="Main">
        <div class="brand-logo">
            <span class="material-symbols-rounded" aria-hidden="true">solar_power</span>
            SOLARIS
        </div>
        <ul class="nav-menu">
            <li class="nav-item">
                <button type="button" class="nav-link" data-view="dashboard">
                    <span class="material-symbols-rounded" aria-hidden="true">dashboard</span>
                    Dashboard
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link" data-view="tracker">
                    <span class="material-symbols-rounded" aria-hidden="true">my_location</span>
                    Tracker
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link" data-view="charts">
                    <span class="material-symbols-rounded" aria-hidden="true">bar_chart</span>
                    Analytics
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link" data-view="logs">
                    <span class="material-symbols-rounded" aria-hidden="true">history</span>
                    Logs
                </button>
            </li>
            <li class="nav-item nav-item-settings">
                <button type="button" class="nav-link" data-view="settings">
                    <span class="material-symbols-rounded" aria-hidden="true">settings</span>
                    Settings
                </button>
            </li>
        </ul>
    </nav>

    <div id="main-wrapper">
        <header class="top-header">
            <h1 class="page-title" id="top-title">Dashboard</h1>
            <div class="header-actions">
                <div class="dropdown">
                    <button class="btn array-picker dropdown-toggle" type="button" id="panelDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Choose array">
                        <span class="material-symbols-rounded" aria-hidden="true">solar_power</span>
                        <span id="panelDropdownText">Array Alpha</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end array-menu" aria-labelledby="panelDropdownBtn">
                        <li>
                            <button type="button" class="dropdown-item" data-panel="panel1">
                                <span class="array-name">Array Alpha</span>
                                <span class="array-type">Dual axis, LDR sensors</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item" data-panel="panel2">
                                <span class="array-name">Array Beta</span>
                                <span class="array-type">Single axis, astronomical</span>
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="status-badge is-waiting" id="system-status-badge" role="status">
                    <span class="status-dot"></span>
                    <span id="system-status-text">Connecting</span>
                </div>
                <button type="button" class="icon-btn" id="theme-toggle" aria-label="Switch colour theme">
                    <span class="material-symbols-rounded" id="theme-toggle-icon" aria-hidden="true">dark_mode</span>
                </button>
            </div>
        </header>

        <main>
            <section id="view-dashboard" class="page-view" aria-labelledby="top-title">
                <div class="row g-3 g-xl-4 mb-3 mb-xl-4">
                    <div class="col-sm-6 col-xl-3">
                        <div class="card-custom h-100">
                            <div class="card-body-custom">
                                <div class="text-xs mb-2">Last reading (WITA)</div>
                                <div class="stat-line">
                                    <div>
                                        <div class="stat-value" id="dash-time">--:--:--</div>
                                        <div class="text-muted-custom mt-1" id="dash-date">No data yet</div>
                                    </div>
                                    <span class="material-symbols-rounded stat-icon" aria-hidden="true">schedule</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card-custom h-100">
                            <div class="card-body-custom">
                                <div class="text-xs mb-2">Weather at site</div>
                                <div class="stat-line">
                                    <div>
                                        <div class="stat-value stat-value-sm" id="weather-desc">Loading</div>
                                        <div class="text-muted-custom mt-1"><span id="weather-temp">--</span> °C, <span id="dash-irradiance">--</span> W/m²</div>
                                    </div>
                                    <span class="material-symbols-rounded stat-icon" id="weather-icon" aria-hidden="true">cloud</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card-custom h-100">
                            <div class="card-body-custom mode-card">
                                <div>
                                    <div class="text-xs mb-1">Tracking mode</div>
                                    <div class="stat-value stat-value-sm" data-mode-label>--</div>
                                    <div class="text-muted-custom mt-1" data-tracking-type>Dual-axis tracker</div>
                                </div>
                                <div class="mode-actions">
                                    @include('partials.mode-switch')
                                    <button type="button" class="btn btn-outline-secondary" data-command="Reset">Reset position</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 g-xl-4">
                    <div class="col-lg-6 col-xl-4 d-flex flex-column gap-3 gap-xl-4">
                        <div class="card-custom card-focal">
                            <div class="card-body-custom">
                                <div class="text-xs mb-1">Array output now</div>
                                <div class="power-reading">
                                    <span class="text-big" id="dash-power">--</span>
                                    <span class="val-unit">W</span>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-6">
                                        <div class="text-xs mb-1">Voltage</div>
                                        <div class="reading"><span id="dash-voltage">--</span><span class="reading-unit">V</span></div>
                                    </div>
                                    <div class="col-6 reading-divider">
                                        <div class="text-xs mb-1">Current</div>
                                        <div class="reading"><span id="dash-current">--</span><span class="reading-unit">A</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-custom dual-only">
                            <div class="card-header-custom"><h2 class="card-title-custom">LDR sensors</h2></div>
                            <div class="card-body-custom pt-0">
                                @include('partials.ldr-compass', ['prefix' => 'dash'])
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 col-xl-4">
                        <div class="card-custom h-100">
                            <div class="card-header-custom">
                                <h2 class="card-title-custom">Tracker position</h2>
                                <button type="button" class="btn btn-sm btn-link link-action" data-view="tracker">Open controls</button>
                            </div>
                            <div class="card-body-custom pt-0">
                                @include('partials.tracker-map', ['prefix' => 'dash'])
                                <div class="position-values">
                                    <div class="position-value dual-only">
                                        <div class="text-xs">Azimuth</div>
                                        <div class="reading"><span id="dash-azimuth">--</span>°</div>
                                    </div>
                                    <div class="position-value">
                                        <div class="text-xs">Tilt</div>
                                        <div class="reading"><span id="dash-elevation">--</span>°</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 d-flex flex-column gap-3 gap-xl-4">
                        <div class="card-custom">
                            <div class="card-header-custom">
                                <h2 class="card-title-custom">Power, last 24 hours</h2>
                                <span class="text-muted-custom">W</span>
                            </div>
                            <div class="card-body-custom pt-0">
                                @include('partials.chart', ['id' => 'chartDashPower', 'height' => 180, 'label' => 'Average power over the last 24 hours'])
                            </div>
                        </div>
                        <div class="card-custom">
                            <div class="card-header-custom">
                                <h2 class="card-title-custom">Voltage and current, last 24 hours</h2>
                            </div>
                            <div class="card-body-custom pt-0">
                                @include('partials.chart', ['id' => 'chartDashVA', 'height' => 180, 'label' => 'Average voltage and current over the last 24 hours'])
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="view-tracker" class="page-view" aria-labelledby="top-title">
                <div class="view-heading">
                    <h2 id="tracker-panel-title">Dual-axis tracker</h2>
                    <span class="lock-badge" id="trk-status-badge" role="status">No data</span>
                </div>
                <div class="row g-3 g-xl-4 mb-3 mb-xl-4">
                    <div class="col-xl-8">
                        <div class="card-custom h-100">
                            <div class="card-body-custom">
                                @include('partials.tracker-map', ['prefix' => 'trk'])
                                <div class="orientation-values">
                                    <div class="orientation-value dual-only">
                                        <div class="text-xs mb-2">Azimuth</div>
                                        <div class="orientation-number"><span id="trk-azimuth">--</span>°</div>
                                        <div class="text-muted-custom mt-1" id="trk-azimuth-lbl">No data</div>
                                    </div>
                                    <div class="orientation-value">
                                        <div class="text-xs mb-2">Tilt</div>
                                        <div class="orientation-number"><span id="trk-elevation">--</span>°</div>
                                        <div class="text-muted-custom mt-1" id="trk-elevation-lbl">No data</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 d-flex flex-column gap-3 gap-xl-4">
                        <div class="card-custom">
                            <div class="card-header-custom"><h3 class="card-title-custom">Tracking mode</h3></div>
                            <div class="card-body-custom pt-0">
                                @include('partials.mode-switch')
                                <dl class="fact-list">
                                    <div>
                                        <dt>Algorithm</dt>
                                        <dd id="algorithm-type">LDR sensors</dd>
                                    </div>
                                    <div>
                                        <dt>Last reading</dt>
                                        <dd id="trk-last-reading">No data yet</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                        <div class="card-custom flex-grow-1">
                            <div class="card-header-custom"><h3 class="card-title-custom">Manual control</h3></div>
                            <div class="card-body-custom pt-0">
                                <div class="d-pad-container" id="d-pad-container">
                                    <button type="button" class="d-btn d-up" data-command="Up" aria-label="Tilt up" disabled><span class="material-symbols-rounded" aria-hidden="true">expand_less</span></button>
                                    <button type="button" class="d-btn d-left dual-only" data-command="Left" aria-label="Rotate left" disabled><span class="material-symbols-rounded" aria-hidden="true">chevron_left</span></button>
                                    <button type="button" class="d-btn d-center" data-command="Reset" aria-label="Reset position" disabled><span class="material-symbols-rounded" aria-hidden="true">adjust</span></button>
                                    <button type="button" class="d-btn d-right dual-only" data-command="Right" aria-label="Rotate right" disabled><span class="material-symbols-rounded" aria-hidden="true">chevron_right</span></button>
                                    <button type="button" class="d-btn d-down" data-command="Down" aria-label="Tilt down" disabled><span class="material-symbols-rounded" aria-hidden="true">expand_more</span></button>
                                </div>
                                <p class="text-muted-custom text-center mt-3 mb-0" id="d-pad-note">Switch to Manual to move the panel.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row g-3 g-xl-4 dual-only">
                    <div class="col-xl-6">
                        <div class="card-custom">
                            <div class="card-header-custom">
                                <h3 class="card-title-custom">LDR sensors</h3>
                                <span class="text-muted-custom">Raw sensor values</span>
                            </div>
                            <div class="card-body-custom pt-0">
                                @include('partials.ldr-compass', ['prefix' => 'trk'])
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="view-charts" class="page-view" aria-labelledby="top-title">
                <div class="toolbar">
                    <div class="toolbar-field">
                        <label class="form-label text-xs" for="analytics-range">Period</label>
                        <select class="form-select" id="analytics-range">
                            <option value="24h">Last 24 hours</option>
                            <option value="7d">Last 7 days</option>
                            <option value="30d">Last 30 days</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-dark btn-icon" id="export-analytics">
                        <span class="material-symbols-rounded" aria-hidden="true">download</span> Download chart data
                    </button>
                </div>

                <div class="summary-strip card-custom mb-3 mb-xl-4">
                    <div class="summary-main">
                        <div class="text-xs mb-1">Estimated energy</div>
                        <div class="power-reading"><span class="text-big" id="ana-energy">--</span><span class="val-unit" id="ana-energy-unit">kWh</span></div>
                    </div>
                    <div>
                        <div class="text-xs mb-1">Peak power</div>
                        <div class="reading"><span id="ana-peak">--</span><span class="reading-unit">W</span></div>
                    </div>
                    <div>
                        <div class="text-xs mb-1">Average power</div>
                        <div class="reading"><span id="ana-avg">--</span><span class="reading-unit">W</span></div>
                    </div>
                    <div>
                        <div class="text-xs mb-1">Readings</div>
                        <div class="reading"><span id="ana-samples">--</span></div>
                    </div>
                </div>

                <div class="card-custom mb-3 mb-xl-4">
                    <div class="card-header-custom"><h2 class="card-title-custom">Voltage and current</h2></div>
                    <div class="card-body-custom pt-0">
                        @include('partials.chart', ['id' => 'chartAnaVA', 'height' => 340, 'label' => 'Average voltage and current for the selected period'])
                    </div>
                </div>
                <div class="card-custom">
                    <div class="card-header-custom">
                        <h2 class="card-title-custom">Estimated energy per period</h2>
                        <span class="text-muted-custom">Average power × time between readings, Wh</span>
                    </div>
                    <div class="card-body-custom pt-0">
                        @include('partials.chart', ['id' => 'chartAnaEnergy', 'height' => 250, 'label' => 'Estimated energy for each part of the selected period'])
                    </div>
                </div>
            </section>

            <section id="view-logs" class="page-view" aria-labelledby="top-title">
                <form class="card-custom mb-3 mb-xl-4" id="log-filter-form">
                    <div class="card-body-custom">
                        <div class="row g-3 align-items-end">
                            <div class="col-sm-6 col-xl-3">
                                <label class="form-label text-xs" for="filter-date-start">From (WITA)</label>
                                <input type="date" class="form-control" id="filter-date-start">
                            </div>
                            <div class="col-sm-6 col-xl-3">
                                <label class="form-label text-xs" for="filter-date-end">To (WITA)</label>
                                <input type="date" class="form-control" id="filter-date-end">
                            </div>
                            <div class="col-sm-6 col-xl-2">
                                <label class="form-label text-xs" for="filter-status">Status</label>
                                <select class="form-select" id="filter-status">
                                    <option value="">All statuses</option>
                                    <option>Sun Locked</option>
                                    <option>Tracking...</option>
                                    <option>Manual Override</option>
                                    <option>Stopped</option>
                                </select>
                            </div>
                            <div class="col-sm-6 col-xl-4 filter-actions">
                                <button type="submit" class="btn btn-warning">Apply</button>
                                <button type="button" class="btn btn-outline-secondary" id="filter-clear">Clear</button>
                                <button type="button" class="btn btn-dark btn-icon" id="export-logs">
                                    <span class="material-symbols-rounded" aria-hidden="true">file_download</span> CSV
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="card-custom">
                    <div class="card-header-custom log-header">
                        <h2 class="card-title-custom"><span id="log-array-name">Array Alpha</span> readings</h2>
                        <div class="text-muted-custom" id="log-updated" role="status">Loading</div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th scope="col" class="ps-4">Time (WITA)</th>
                                    <th scope="col">Log ID</th>
                                    <th scope="col" class="text-end">Voltage (V)</th>
                                    <th scope="col" class="text-end">Current (A)</th>
                                    <th scope="col" class="text-end">Power (W)</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="pe-4"><span class="visually-hidden">Details</span></th>
                                </tr>
                            </thead>
                            <tbody id="log-table-body"></tbody>
                        </table>
                    </div>
                    <div class="log-footer">
                        <div class="text-muted-custom" id="pagination-info">Showing 0 entries</div>
                        <nav aria-label="Log pages">
                            <ul class="pagination pagination-sm mb-0" id="pagination-controls"></ul>
                        </nav>
                    </div>
                </div>
            </section>

            <section id="view-settings" class="page-view" aria-labelledby="top-title">
                <div class="row g-3 g-xl-4">
                    <div class="col-lg-6">
                        <div class="card-custom">
                            <div class="card-header-custom"><h2 class="card-title-custom">This browser</h2></div>
                            <div class="card-body-custom pt-0">
                                <div class="mb-3">
                                    <label class="form-label text-sm fw-semibold" for="theme-select">Theme</label>
                                    <select class="form-select" id="theme-select">
                                        <option value="light">Light</option>
                                        <option value="dark">Dark</option>
                                        <option value="auto">Follow system</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label text-sm fw-semibold" for="poll-select">Refresh live values every</label>
                                    <select class="form-select" id="poll-select">
                                        <option value="2000">2 seconds</option>
                                        <option value="5000">5 seconds</option>
                                        <option value="30000">30 seconds</option>
                                        <option value="60000">1 minute</option>
                                    </select>
                                </div>
                                <p class="text-muted-custom mt-3 mb-0">Changes apply immediately and are saved in this browser.</p>
                            </div>
                        </div>
                    </div>
                    @if (config('tracker.dev_tools'))
                        <div class="col-lg-6">
                            <div class="card-custom">
                                <div class="card-header-custom"><h2 class="card-title-custom">Test data</h2></div>
                                <div class="card-body-custom pt-0">
                                    <label class="form-label text-sm fw-semibold" for="dev-tools-subsystem">Array</label>
                                    <select class="form-select mb-3" id="dev-tools-subsystem">
                                        <option value="all">Both arrays</option>
                                        <option value="panel1">Array Alpha</option>
                                        <option value="panel2">Array Beta</option>
                                    </select>
                                    <div class="border-top pt-3 mb-3">
                                        <p class="text-muted-custom mb-2">Adds 50 random readings per array, spread over the last 30 days.</p>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="seed-data">Add random readings</button>
                                    </div>
                                    <div class="border-top pt-3">
                                        <p class="text-muted-custom mb-2">Permanently deletes every log for the selected array.</p>
                                        <button type="button" class="btn btn-danger btn-sm" id="wipe-data">Delete logs</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        </main>
    </div>

    <div class="modal fade" id="log-detail-modal" tabindex="-1" aria-labelledby="log-detail-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="log-detail-title">Reading</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <dl class="fact-list fact-list-tight mt-0" id="log-detail-body"></dl>
                </div>
            </div>
        </div>
    </div>

    <div id="toast-container" aria-live="polite"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/script.js') }}?v={{ filemtime(public_path('js/script.js')) }}"></script>
</body>
</html>
