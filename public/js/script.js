'use strict';

const TIMEZONE = 'Asia/Makassar';
const OFFLINE_AFTER_SECONDS = 180;
const LOGS_PER_PAGE = 7;
const LOG_REFRESH_MS = 10000;
const SERIES_REFRESH_MS = 60000;
const WEATHER_REFRESH_MS = 5 * 60 * 1000;
const FIRMWARE_REFRESH_MS = 60000;

const PAGE_TITLES = {
    dashboard: 'Main Dashboard',
    tracker: 'Panel Tracking',
    charts: 'Charts & Analytics',
    logs: 'Recent Logs',
    settings: 'Settings',
};

const PANELS = {
    panel1: { name: 'Array Alpha', label: 'Array Alpha (Dual Axis)', title: 'Dual-Axis Tracker Array', type: 'Dual-Axis Tracking', algorithm: 'LDR Sensors' },
    panel2: { name: 'Array Beta', label: 'Array Beta (Single Axis)', title: 'Single-Axis Tracker Array', type: 'Single-Axis Tracking', algorithm: 'Astronomical Algorithm' },
};

const COMPASS = ['North', 'North-East', 'East', 'South-East', 'South', 'South-West', 'West', 'North-West'];
const STATUS_CLASSES = { 'Sun Locked': 'is-locked', 'Tracking...': 'is-tracking', 'Manual Override': 'is-manual', Stopped: 'is-stopped' };
const ROW_CLASSES = { 'Tracking...': 'log-row-tracking', 'Manual Override': 'log-row-manual', Stopped: 'log-row-stopped' };
const WEATHER_ICONS = { 'Clear Sky': 'light_mode', 'Partly Cloudy': 'partly_cloudy_day', Overcast: 'cloud', Foggy: 'foggy', Rain: 'rainy', Showers: 'rainy', Thunderstorm: 'thunderstorm' };

const state = {
    panelId: PANELS[localStorage.getItem('panelId')] ? localStorage.getItem('panelId') : 'panel1',
    view: 'dashboard',
    pollMs: Number(localStorage.getItem('pollMs')) || 2000,
    pollTimer: null,
    latest: null,
    connection: 'waiting',
    range: '24h',
    charts: {},
    logPage: 1,
    logRows: [],
    logFilters: { start: '', end: '', status: '' },
    firmware: null,
    pickers: {},
    selects: {},
};

const byId = (id) => document.getElementById(id);

function setText(id, value) {
    const el = byId(id);
    if (el) el.textContent = value;
}

function formatNumber(value, decimals = 1) {
    if (value === null || value === undefined || Number.isNaN(Number(value))) return '--';
    return Number(value).toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function formatDateTime(iso) {
    return new Date(iso).toLocaleString('sv-SE', { timeZone: TIMEZONE });
}

async function requestJson(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: { Accept: 'application/json', ...(options.body ? { 'Content-Type': 'application/json' } : {}) },
    });
    let body = null;
    try {
        body = await response.json();
    } catch (error) {
        body = null;
    }
    if (!response.ok) throw new Error(body?.message || `Request failed (${response.status})`);
    return body;
}

const apiGet = (path, params = {}) => {
    const query = new URLSearchParams(Object.entries(params).filter(([, value]) => value !== '' && value != null));
    return requestJson(`/api/tracker/${path}?${query}`);
};

const apiPost = (path, payload) => requestJson(`/api/tracker/${path}`, { method: 'POST', body: JSON.stringify(payload) });

function showToast(type, message) {
    const icons = { success: ['check_circle', '#22C55E'], warning: ['warning', '#F59E0B'], error: ['error', '#EF4444'], info: ['info', '#3B82F6'] };
    const [iconName, color] = icons[type] || icons.info;
    const toast = document.createElement('div');
    toast.className = 'toast-custom';
    const icon = document.createElement('span');
    icon.className = 'material-symbols-rounded';
    icon.style.color = color;
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = iconName;
    const text = document.createElement('span');
    text.textContent = message;
    toast.append(icon, text);
    byId('toast-container').appendChild(toast);
    setTimeout(() => {
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/* Navigation */

function refreshView(view) {
    if (view === 'dashboard') loadDashboardSeries();
    if (view === 'charts') {
        loadAnalyticsSeries();
        loadWeatherHistory();
    }
    if (view === 'logs') loadLogs();
    if (view === 'settings') loadFirmware();
}

function navigateTo(view, { updateHash = true } = {}) {
    if (!PAGE_TITLES[view]) view = 'dashboard';
    state.view = view;

    document.querySelectorAll('.page-view').forEach((el) => {
        el.classList.remove('active', 'panel-switch-anim');
    });
    document.querySelectorAll('.nav-link[data-view]').forEach((el) => {
        const current = el.dataset.view === view;
        el.classList.toggle('active', current);
        if (current) el.setAttribute('aria-current', 'page');
        else el.removeAttribute('aria-current');
    });
    const target = byId(`view-${view}`);
    target.classList.add('active');
    void target.offsetWidth;
    target.classList.add('panel-switch-anim');
    setText('top-title', PAGE_TITLES[view]);
    if (updateHash && location.hash !== `#${view}`) history.replaceState(null, '', `#${view}`);

    Object.values(state.charts).forEach((chart) => chart.resize());
    refreshView(view);
}

function setDisplay(id, visible) {
    const el = byId(id);
    if (!el) return;
    if (visible) el.style.display = '';
    else el.style.setProperty('display', 'none', 'important');
}

function setColumn(id, single, doubleClass, singleClass) {
    const el = byId(id);
    if (!el) return;
    el.classList.toggle(doubleClass, !single);
    el.classList.toggle(singleClass, single);
}

function switchPanel(panelId, { silent = false } = {}) {
    if (!PANELS[panelId]) return;
    state.panelId = panelId;
    localStorage.setItem('panelId', panelId);

    const panel = PANELS[panelId];
    const single = panelId === 'panel2';

    setText('tracker-panel-title', panel.title);
    setText('algorithm-type', panel.algorithm);
    setText('panelDropdownText', panel.name);
    setText('panelDropdownAxis', ` ${panel.label.slice(panel.name.length + 1)}`);
    state.selects.subsystem?.setValue(panelId, true);

    ['azimuth-map-col', 'dash-azimuth-map-col', 'dash-azimuth-val-col', 'dash-ldr-widget', 'trk-ldr-widget', 'manual-azimuth-field']
        .forEach((id) => setDisplay(id, !single));
    ['elevation-map-col', 'dash-elevation-map-col', 'dash-elevation-val-col', 'manual-elevation-field']
        .forEach((id) => setColumn(id, single, 'col-6', 'col-12'));
    setColumn('trk-accuracy-col', single, 'col-xl-6', 'col-12');
    ['dash-energy-widget', 'dash-weather-widget'].forEach((id) => setDisplay(id, single));
    byId('manual-target-form').reset();

    document.querySelectorAll('[data-panel]').forEach((el) => {
        const selected = el.dataset.panel === panelId;
        el.classList.toggle('active', selected);
        if (selected) el.setAttribute('aria-current', 'true');
        else el.removeAttribute('aria-current');
    });
    document.querySelectorAll('.page-view.active').forEach((el) => {
        el.classList.remove('panel-switch-anim');
        void el.offsetWidth;
        el.classList.add('panel-switch-anim');
    });

    state.latest = null;
    state.logPage = 1;
    renderLatest(null);
    renderFirmware();
    fetchLatest();
    refreshView(state.view);
    if (!silent) showToast('info', `Switched to ${panel.label}`);
}

/* Live readings */

function animateValueChange(id, newValue, decimals = 1) {
    const el = byId(id);
    if (!el) return;
    if (newValue === null || newValue === undefined) {
        el.textContent = '--';
        return;
    }
    const value = Number(newValue);
    const previous = parseFloat(el.textContent.replace(/,/g, ''));
    el.textContent = formatNumber(value, decimals);
    el.classList.remove('val-up', 'val-down');
    if (Number.isNaN(previous)) return;
    void el.offsetWidth;
    if (value > previous) el.classList.add('val-up');
    else if (value < previous) el.classList.add('val-down');
    setTimeout(() => el.classList.remove('val-up', 'val-down'), 400);
}

function renderEnergySummary(summary) {
    const samples = summary ? summary.samples : 0;
    setText('dash-energy', samples ? formatNumber(summary.energy_wh, 1) : '--');
    setText('dash-peak-power', samples ? formatNumber(summary.peak_power, 1) : '--');
    setText('dash-avg-power', samples ? formatNumber(summary.avg_power, 1) : '--');
    setText('dash-energy-note', summary
        ? (samples ? `From ${samples.toLocaleString()} readings in the last 24 hours` : 'No readings in the last 24 hours')
        : 'Could not load the last 24 hours');
}

function renderWeatherDetails(data, note) {
    setText('dash-humidity', data ? formatNumber(data.humidity, 0) : '--');
    setText('dash-cloud', data ? formatNumber(data.cloud_cover, 0) : '--');
    setText('dash-wind', data ? formatNumber(data.wind_speed, 1) : '--');
    setText('dash-rain', data ? formatNumber(data.precipitation, 1) : '--');
    setText('dash-weather-note', note);
}

function timeAgo(iso) {
    const minutes = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 60000));
    if (minutes < 1) return 'just now';
    if (minutes < 60) return `${minutes} min ago`;
    const hours = Math.round(minutes / 60);
    if (hours < 48) return `${hours} h ago`;
    return `${Math.round(hours / 24)} days ago`;
}

// Smallest gap between two compass headings, so 359° and 1° are 2° apart.
const angleGap = (a, b) => {
    const gap = Math.abs(Number(a) - Number(b)) % 360;
    return gap > 180 ? 360 - gap : gap;
};

function renderDeviceStatus() {
    const data = state.latest;
    const pill = byId('dash-device-pill');
    const connection = {
        online: ['is-locked', 'Online'],
        offline: ['is-stopped', 'Offline'],
        waiting: ['is-none', 'Waiting'],
    }[state.connection] || ['is-none', 'Waiting'];
    pill.className = `status-pill ${connection[0]}`;
    pill.textContent = connection[1];

    setText('dash-device-seen', data ? `${formatDateTime(data.created_at)} WITA (${timeAgo(data.created_at)})` : 'No data yet');

    const firmware = state.firmware?.data?.[state.panelId];
    setText('dash-device-firmware', formatVersion(firmware?.installed));
    setText('dash-device-firmware-note', !state.firmware ? 'Unavailable' : {
        up_to_date: 'Up to date',
        update_available: `Update to ${formatVersion(firmware.latest)} pending`,
    }[firmware?.state] || (firmware?.installed ? 'Latest version unavailable' : 'Not reported by the device yet'));

    // Array Beta keeps its target angle in azimuth and its current angle in elevation.
    let error = '--';
    let note = 'No reading yet';
    if (data && state.panelId === 'panel2') {
        error = `${formatNumber(Math.abs(data.azimuth - data.elevation), 1)}°`;
        note = `Target ${formatNumber(data.azimuth, 1)}°, current ${formatNumber(data.elevation, 1)}°`;
    } else if (data) {
        const hasTarget = data.target_azimuth != null && data.target_elevation != null;
        error = hasTarget
            ? `Az ${formatNumber(angleGap(data.target_azimuth, data.azimuth), 1)}°, El ${formatNumber(Math.abs(data.target_elevation - data.elevation), 1)}°`
            : '--';
        note = hasTarget ? 'Gap between target and current position' : 'The device has not reported a target yet';
    }
    setText('dash-device-error', error);
    setText('dash-device-error-note', note);
}

function renderConnection(kind, text) {
    state.connection = kind;
    const badge = byId('system-status-badge');
    badge.classList.remove('is-online', 'is-offline', 'is-waiting');
    badge.classList.add(`is-${kind}`);
    setText('system-status-text', text);
    renderDeviceStatus();
}

function renderModeControls(mode) {
    const label = mode === 'Stop' ? 'Stopped' : mode;
    setText('dash-mode', label || '--');

    const auto = mode === 'Automatic';
    const manual = mode === 'Manual';
    const stopped = mode === 'Stop';

    const dashAuto = byId('btn-mode-auto');
    dashAuto.className = `btn fw-semibold px-4 ${auto ? 'btn-warning' : 'btn-outline-warning text-warning'}`;
    dashAuto.setAttribute('aria-pressed', String(auto));
    const dashStop = byId('btn-mode-stop');
    dashStop.className = `btn fw-semibold ${stopped ? 'btn-danger' : 'btn-outline-danger'}`;
    dashStop.setAttribute('aria-pressed', String(stopped));

    const pill = (active) => `btn rounded-pill flex-grow-1 fw-semibold transition-all ${active ? 'btn-warning' : 'text-muted'}`;
    const trkAuto = byId('btn-trk-auto');
    trkAuto.className = pill(auto);
    trkAuto.setAttribute('aria-pressed', String(auto));
    const trkManual = byId('btn-trk-manual');
    trkManual.className = pill(manual);
    trkManual.setAttribute('aria-pressed', String(manual));

    byId('manual-target-form').classList.toggle('is-locked', !manual);
    byId('manual-target-fields').disabled = !manual;
    setText('manual-note', manual ? 'Sent the next time the device reports in. A value left empty keeps that axis where it is.' : 'Switch to Manual to move the panel.');
}

function compassName(degrees) {
    return COMPASS[Math.round((((degrees % 360) + 360) % 360) / 45) % 8];
}

function renderPosition(data) {
    const azimuth = data ? data.azimuth : null;
    const elevation = data ? data.elevation : null;

    setText('manual-azimuth-now', formatNumber(azimuth, 1));
    setText('manual-elevation-now', formatNumber(elevation, 1));
    animateValueChange('dash-azimuth', azimuth, 1);
    animateValueChange('dash-elevation', elevation, 1);
    animateValueChange('trk-azimuth', azimuth, 0);
    animateValueChange('trk-elevation', elevation, 0);
    setText('map-az', formatNumber(azimuth, 0));
    setText('map-el', formatNumber(elevation, 0));

    ['dash', 'trk'].forEach((prefix) => {
        byId(`${prefix}-azimuth-ptr`).style.transform = `rotate(${azimuth ?? 0}deg)`;
        byId(`${prefix}-elevation-ptr`).style.transform = `rotate(${(elevation ?? 90) - 90}deg)`;
    });

    // Array Alpha reports both targets; Array Beta keeps its single target angle in the azimuth column.
    const dual = state.panelId === 'panel1';
    const targets = trackerTargets(data);
    setText('trk-azimuth-title', dual ? 'AZIMUTH' : 'TARGET ANGLE');
    setText('trk-elevation-title', dual ? 'ELEVATION' : 'CURRENT ANGLE');
    setText('trk-azimuth-lbl', !data ? 'No data' : dual ? `Facing ${compassName(azimuth)}` : 'Astronomical target');
    setText('trk-elevation-lbl', data ? 'Reported panel tilt' : 'No data');

    if (dual) {
        showTarget('trk-azimuth-target', targets.azimuth, (value) => `Target ${formatNumber(value, 1)}°`);
        showTarget('trk-elevation-target', targets.elevation, (value) => `Target ${formatNumber(value, 1)}°`);
    } else {
        showTarget('trk-azimuth-target', null);
        showTarget('trk-elevation-target', targets.elevation, (value) => `${formatNumber(Math.abs(value - elevation), 1)}° from target`);
    }

    const azGhost = byId('trk-azimuth-target-ptr');
    azGhost.hidden = !dual || targets.azimuth === null;
    if (!azGhost.hidden) azGhost.style.transform = `rotate(${targets.azimuth}deg)`;
    const elGhost = byId('trk-elevation-target-ptr');
    elGhost.hidden = targets.elevation === null;
    if (!elGhost.hidden) elGhost.style.transform = `rotate(${targets.elevation - 90}deg)`;
    byId('trk-map-legend').hidden = targets.azimuth === null && targets.elevation === null;

    renderAccuracy(data, targets);
}

const isAngle = (value) => value !== null && value !== undefined && value !== '' && !Number.isNaN(Number(value));

/** Target angles shown on the tracker maps; null where the device has not reported one. */
function trackerTargets(data) {
    if (!data) return { azimuth: null, elevation: null };
    if (state.panelId === 'panel1') {
        return {
            azimuth: isAngle(data.target_azimuth) ? Number(data.target_azimuth) : null,
            elevation: isAngle(data.target_elevation) ? Number(data.target_elevation) : null,
        };
    }
    return { azimuth: null, elevation: isAngle(data.azimuth) ? Number(data.azimuth) : null };
}

function showTarget(id, value, format) {
    const el = byId(id);
    const known = isAngle(value);
    el.hidden = !known;
    el.textContent = known ? format(Number(value)) : '';
}

// Mirrors TrackerLog: Array Alpha locks on the light sensors, Array Beta within 2° of its target.
const LOCK_LDR_MARGIN_PERCENT = 15;
const LOCK_SINGLE_AXIS_DEGREES = 2;

function accuracyLevel(gap) {
    if (gap <= LOCK_SINGLE_AXIS_DEGREES) return 'is-good';
    if (gap <= 10) return 'is-near';
    return 'is-far';
}

function accuracyRow(name, current, target, gap) {
    if (gap === null) {
        return `
            <div class="accuracy-row">
                <div class="d-flex justify-content-between align-items-baseline">
                    <div class="fw-semibold">${escapeHtml(name)}</div>
                    <div class="accuracy-gap">--</div>
                </div>
                <div class="accuracy-bar"><span style="width: 0"></span></div>
                <div class="text-xs text-muted">Current ${escapeHtml(formatNumber(current, 1))}°, no target reported yet</div>
            </div>`;
    }
    const width = Math.min(gap / 45, 1) * 100;
    return `
        <div class="accuracy-row">
            <div class="d-flex justify-content-between align-items-baseline">
                <div class="fw-semibold">${escapeHtml(name)}</div>
                <div class="accuracy-gap ${accuracyLevel(gap)}">${escapeHtml(formatNumber(gap, 1))}° off</div>
            </div>
            <div class="accuracy-bar ${accuracyLevel(gap)}"><span style="width: ${width.toFixed(1)}%"></span></div>
            <div class="d-flex justify-content-between text-xs text-muted">
                <span>Current ${escapeHtml(formatNumber(current, 1))}°</span>
                <span>Target ${escapeHtml(formatNumber(target, 1))}°</span>
            </div>
        </div>`;
}

function renderAccuracy(data, targets) {
    const pill = byId('trk-accuracy-pill');
    pill.className = `status-pill ${data ? STATUS_CLASSES[data.status] || 'is-none' : 'is-none'}`;
    pill.textContent = data ? data.status : 'No data';

    const rows = byId('trk-accuracy-rows');
    const rule = byId('trk-accuracy-rule');
    const dual = state.panelId === 'panel1';
    setText('trk-acc-signal-label', dual ? 'LDR DIFFERENCE' : 'TARGET GAP');
    setText('trk-acc-threshold', dual ? `≤ ${LOCK_LDR_MARGIN_PERCENT}%` : `≤ ${LOCK_SINGLE_AXIS_DEGREES}°`);
    setText('trk-acc-mode', data ? (data.tracking_mode === 'Stop' ? 'Stopped' : data.tracking_mode || '--') : '--');
    if (!data) {
        rows.innerHTML = '<div class="text-muted small">No reading yet</div>';
        rule.textContent = '';
        setText('trk-acc-signal', '--');
        return;
    }

    if (state.panelId === 'panel1') {
        const azGap = targets.azimuth === null ? null : angleGap(targets.azimuth, data.azimuth);
        const elGap = targets.elevation === null ? null : Math.abs(targets.elevation - Number(data.elevation));
        rows.innerHTML = accuracyRow('Azimuth', data.azimuth, targets.azimuth, azGap)
            + accuracyRow('Elevation', data.elevation, targets.elevation, elGap);
        const margin = ldrMargin(data);
        setText('trk-acc-signal', margin === null ? '--' : `${margin.toFixed(1)}%`);
        rule.textContent = margin === null
            ? `Sun Locked is decided by the light sensors (largest difference ${LOCK_LDR_MARGIN_PERCENT}% or less).`
            : `Sun Locked is decided by the light sensors: their largest difference is ${margin.toFixed(1)}% (locked at ${LOCK_LDR_MARGIN_PERCENT}% or less).`;
    } else {
        const gap = targets.elevation === null ? null : Math.abs(targets.elevation - Number(data.elevation));
        rows.innerHTML = accuracyRow('Panel angle', data.elevation, targets.elevation, gap);
        setText('trk-acc-signal', gap === null ? '--' : `${formatNumber(gap, 1)}°`);
        rule.textContent = `Sun Locked when the panel is within ${LOCK_SINGLE_AXIS_DEGREES}° of the astronomical target.`;
    }
}

function ldrMargin(data) {
    if (!data) return null;
    const [nw, ne, sw, se] = [data.ldr_nw, data.ldr_ne, data.ldr_sw, data.ldr_se].map(Number);
    const average = (nw + ne + sw + se) / 4;
    if (!(average > 0)) return null;
    const maxDiff = Math.max(Math.abs(nw - ne), Math.abs(sw - se), Math.abs(nw - sw), Math.abs(ne - se));
    return Math.min((maxDiff / average) * 100, 100);
}

function renderLdr(data) {
    const values = {
        nw: data ? data.ldr_nw : null,
        ne: data ? data.ldr_ne : null,
        sw: data ? data.ldr_sw : null,
        se: data ? data.ldr_se : null,
    };

    animateValueChange('dash-ldr-tl', values.nw, 0);
    animateValueChange('dash-ldr-tr', values.ne, 0);
    animateValueChange('dash-ldr-bl', values.sw, 0);
    animateValueChange('dash-ldr-br', values.se, 0);
    Object.entries(values).forEach(([key, value]) => animateValueChange(`trk-ldr-${key}`, value, 0));

    const readings = data ? Object.values(values).map(Number) : [];
    const max = readings.length ? Math.max(...readings) : null;
    Object.entries(values).forEach(([key, value]) => {
        ['dash-box-ldr', 'trk-box-ldr'].forEach((prefix) => {
            const box = byId(`${prefix}-${key}`);
            const highlighted = max !== null && max > 0 && Number(value) === max;
            box.classList.toggle('bg-warning', highlighted);
            box.classList.toggle('bg-opacity-25', highlighted);
            box.classList.toggle('border-warning-subtle', highlighted);
            box.classList.toggle('bg-primary', !highlighted);
            box.classList.toggle('bg-opacity-10', !highlighted);
            box.classList.toggle('border-primary-subtle', !highlighted);
        });
    });

    const margin = ldrMargin(data);
    const marginEl = byId('dash-diff-margin');
    marginEl.textContent = margin === null ? '--' : `${margin.toFixed(1)}%`;
    marginEl.classList.toggle('text-success', margin !== null && margin < 10);
    marginEl.classList.toggle('text-warning', margin !== null && margin >= 10 && margin < 30);
    marginEl.classList.toggle('text-danger', margin !== null && margin >= 30);
}

function renderLatest(data) {
    animateValueChange('dash-power', data ? data.power_output : null, 0);
    animateValueChange('dash-voltage', data ? data.voltage : null, 1);
    animateValueChange('dash-current', data ? data.current : null, 1);

    if (data) {
        const date = new Date(data.created_at);
        setText('dash-time', date.toLocaleTimeString('en-GB', { timeZone: TIMEZONE }));
        setText('dash-date', date.toLocaleDateString('en-US', { timeZone: TIMEZONE, weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' }));
        setText('trk-last-reading', formatDateTime(data.created_at));
    } else {
        setText('dash-time', '--:--:--');
        setText('dash-date', 'No data yet');
        setText('trk-last-reading', 'No data yet');
    }

    const type = PANELS[state.panelId].type;
    setText('dash-tracking-type', data && data.tracking_mode === 'Stop' ? `${type} Stopped` : `${type} Active`);

    renderModeControls(data ? data.tracking_mode : null);
    renderPosition(data);
    renderLdr(data);

    const badge = byId('trk-status-badge');
    badge.className = `status-pill ${data ? STATUS_CLASSES[data.status] || 'is-none' : 'is-none'}`;
    badge.textContent = data ? data.status : 'No data';

    renderDeviceStatus();
}

async function fetchLatest() {
    const panelId = state.panelId;
    try {
        const json = await apiGet('latest', { panel_id: panelId });
        if (panelId !== state.panelId) return;

        state.latest = json.data;
        renderLatest(json.data);

        if (!json.data) {
            renderConnection('waiting', 'No Data Yet');
            return;
        }
        const ageSeconds = (Date.now() - new Date(json.data.created_at).getTime()) / 1000;
        if (ageSeconds <= OFFLINE_AFTER_SECONDS) renderConnection('online', 'System Active');
        else renderConnection('offline', 'System Offline');
    } catch (error) {
        if (panelId === state.panelId) renderConnection('offline', 'Server Unreachable');
        console.error('Could not load the latest reading:', error);
    }
}

function restartPolling() {
    clearInterval(state.pollTimer);
    state.pollTimer = setInterval(() => {
        if (!document.hidden) fetchLatest();
    }, state.pollMs);
}

function showSystemNotice() {
    const name = PANELS[state.panelId].name;
    if (state.connection === 'online') {
        showToast('success', `${name} is sending data.`);
    } else if (state.latest) {
        showToast('warning', `${name} last reported at ${formatDateTime(state.latest.created_at)} WITA.`);
    } else if (byId('system-status-text').textContent === 'Server Unreachable') {
        showToast('error', 'The server cannot be reached.');
    } else {
        showToast('info', `${name} has not reported any data yet.`);
    }
}

/* Controls */

async function setTrackerMode(mode) {
    const panel = PANELS[state.panelId];
    const buttons = ['btn-mode-auto', 'btn-mode-stop', 'btn-trk-auto', 'btn-trk-manual'].map(byId);
    buttons.forEach((button) => { button.disabled = true; });
    try {
        await apiPost('mode', { mode, panel_id: state.panelId });
        await fetchLatest();
        const label = mode === 'Stop' ? 'Stopped' : mode;
        if (state.latest) showToast('success', `${panel.name} set to ${label}`);
        else showToast('info', `${panel.name} will use ${label} when it first reports in`);
    } catch (error) {
        showToast('error', `Could not change the mode: ${error.message}`);
    } finally {
        buttons.forEach((button) => { button.disabled = false; });
    }
}

async function sendResetCommand() {
    const panel = PANELS[state.panelId];
    try {
        await apiPost('command', { command: 'Reset', panel_id: state.panelId });
        showToast('success', `Reset position queued for ${panel.name}`);
    } catch (error) {
        showToast('error', `Could not send the command: ${error.message}`);
    }
}

async function sendManualTarget(event) {
    event.preventDefault();
    const panel = PANELS[state.panelId];
    const azimuthInput = byId('manual-azimuth');
    const elevationInput = byId('manual-elevation');

    const target = {};
    if (azimuthInput.value !== '') target.azimuth = Number(azimuthInput.value);
    if (elevationInput.value !== '') target.elevation = Number(elevationInput.value);

    if (Object.keys(target).length === 0) {
        showToast('error', 'Enter a value first');
        (state.panelId === 'panel2' ? elevationInput : azimuthInput).focus();
        return;
    }

    const sendButton = byId('btn-manual-send');
    sendButton.disabled = true;
    try {
        await apiPost('target', { ...target, panel_id: state.panelId });
        const summary = Object.entries(target).map(([axis, value]) => `${axis} ${value}°`).join(', ');
        if (state.connection === 'online') {
            showToast('success', `${panel.name}: ${summary} queued`);
        } else {
            showToast('info', `${summary} queued, but ${panel.name} is not reporting. It expires after 1 minute.`);
        }
        byId('manual-target-form').reset();
    } catch (error) {
        showToast('error', `Could not send the position: ${error.message}`);
    } finally {
        sendButton.disabled = false;
    }
}

/* Charts */

function chartColors() {
    const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    return dark
        ? { text: '#A7B4C6', grid: '#3B4A60', power: '#FACC15', dashVoltage: '#60A5FA', dashCurrent: '#4ADE80', voltage: '#FACC15', current: '#94A3B8', bar: '#475569', barLast: '#FACC15', temperature: '#F87171', radiation: '#CA8A04' }
        : { text: '#64748B', grid: '#E2E8F0', power: '#EAB308', dashVoltage: '#3B82F6', dashCurrent: '#22C55E', voltage: '#854D0E', current: '#475569', bar: '#E2E8F0', barLast: '#A16207', temperature: '#DC2626', radiation: '#CA8A04' };
}

function axis(colors, overrides = {}) {
    return { ticks: { color: colors.text, maxTicksLimit: 6 }, grid: { color: colors.grid }, border: { display: false }, ...overrides };
}

function timeAxis(colors) {
    return axis(colors, { ticks: { color: colors.text, maxTicksLimit: 7, maxRotation: 0, autoSkip: true }, grid: { display: false } });
}

function twoAxes(colors, leftTitle, leftColor, rightTitle, rightColor, leftZero = false) {
    return {
        x: timeAxis(colors),
        y: axis(colors, { position: 'left', beginAtZero: leftZero, title: { display: true, text: leftTitle, color: leftColor } }),
        y1: axis(colors, { position: 'right', beginAtZero: true, grid: { display: false }, title: { display: true, text: rightTitle, color: rightColor } }),
    };
}

function initCharts() {
    Chart.defaults.font.family = "'Inter', sans-serif";
    const colors = chartColors();
    const base = { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false } };
    const line = (label, color, axisId = 'y', width = 2) => ({ label, data: [], yAxisID: axisId, borderColor: color, backgroundColor: color, borderWidth: width, tension: 0.4, pointRadius: 0, pointHoverRadius: 4 });
    const legend = { position: 'bottom', labels: { color: colors.text, boxWidth: 10, usePointStyle: true } };

    state.charts.dashPower = new Chart(byId('chartDashPower'), {
        type: 'line',
        data: { labels: [], datasets: [{ ...line('Power (W)', colors.power, 'y', 3), fill: true, backgroundColor: 'rgba(234, 179, 8, 0.1)' }] },
        options: { ...base, plugins: { legend: { display: false } }, scales: { x: timeAxis(colors), y: axis(colors, { beginAtZero: true }) } },
    });
    state.charts.dashVA = new Chart(byId('chartDashVA'), {
        type: 'line',
        data: { labels: [], datasets: [line('Voltage (V)', colors.dashVoltage), line('Current (A)', colors.dashCurrent, 'y1')] },
        options: { ...base, plugins: { legend }, scales: twoAxes(colors, 'V', colors.dashVoltage, 'A', colors.dashCurrent) },
    });
    state.charts.anaVA = new Chart(byId('chartAnaVA'), {
        type: 'line',
        data: { labels: [], datasets: [line('Voltage (V)', colors.voltage, 'y', 3), line('Current (A)', colors.current, 'y1')] },
        options: { ...base, plugins: { legend: { display: false } }, scales: twoAxes(colors, 'Voltage (V)', colors.voltage, 'Current (A)', colors.current) },
    });
    state.charts.anaEnergy = new Chart(byId('chartAnaConsumption'), {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'Estimated energy (Wh)', data: [], backgroundColor: colors.bar, borderRadius: 4 }] },
        options: { ...base, plugins: { legend: { display: false } }, scales: { x: timeAxis(colors), y: axis(colors, { beginAtZero: true }) } },
    });
    state.charts.anaWeather = new Chart(byId('chartAnaWeather'), {
        type: 'line',
        data: { labels: [], datasets: [line('Temperature (°C)', colors.temperature), line('Solar radiation (W/m²)', colors.radiation, 'y1')] },
        options: { ...base, plugins: { legend: { display: false } }, scales: twoAxes(colors, '°C', colors.temperature, 'W/m²', colors.radiation) },
    });
}

function colorEnergyBars(chart, colors) {
    const count = chart.data.labels.length;
    chart.data.datasets[0].backgroundColor = Array.from({ length: count }, (_, index) => (index === count - 1 ? colors.barLast : colors.bar));
}

function applyChartTheme() {
    const { dashPower, dashVA, anaVA, anaEnergy, anaWeather } = state.charts;
    if (!dashPower) return;
    const colors = chartColors();
    const paint = (chart, index, color) => {
        chart.data.datasets[index].borderColor = color;
        chart.data.datasets[index].backgroundColor = color;
    };

    dashPower.data.datasets[0].borderColor = colors.power;
    dashPower.options.scales = { x: timeAxis(colors), y: axis(colors, { beginAtZero: true }) };

    paint(dashVA, 0, colors.dashVoltage);
    paint(dashVA, 1, colors.dashCurrent);
    dashVA.options.plugins.legend.labels.color = colors.text;
    dashVA.options.scales = twoAxes(colors, 'V', colors.dashVoltage, 'A', colors.dashCurrent);

    paint(anaVA, 0, colors.voltage);
    paint(anaVA, 1, colors.current);
    anaVA.options.scales = twoAxes(colors, 'Voltage (V)', colors.voltage, 'Current (A)', colors.current);

    anaEnergy.options.scales = { x: timeAxis(colors), y: axis(colors, { beginAtZero: true }) };
    colorEnergyBars(anaEnergy, colors);

    paint(anaWeather, 0, colors.temperature);
    paint(anaWeather, 1, colors.radiation);
    anaWeather.options.scales = twoAxes(colors, '°C', colors.temperature, 'W/m²', colors.radiation);

    Object.values(state.charts).forEach((chart) => chart.update());
}

function setChartState(canvasId, message) {
    const overlay = document.querySelector(`[data-chart-state="${canvasId}"]`);
    if (!overlay) return;
    overlay.textContent = message || '';
    overlay.classList.toggle('visible', Boolean(message));
}

function seriesLabel(iso, range) {
    const date = new Date(iso);
    if (range === '24h') return date.toLocaleTimeString('en-GB', { timeZone: TIMEZONE, hour: '2-digit', minute: '2-digit' });
    if (range === '7d') return date.toLocaleString('en-GB', { timeZone: TIMEZONE, day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
    return date.toLocaleDateString('en-GB', { timeZone: TIMEZONE, day: 'numeric', month: 'short' });
}

function fillVoltageCurrent(chart, labels, points) {
    chart.data.labels = labels;
    chart.data.datasets[0].data = points.map((point) => point.voltage);
    chart.data.datasets[1].data = points.map((point) => point.current);
    chart.update();
}

async function loadDashboardSeries() {
    const panelId = state.panelId;
    const ids = ['chartDashPower', 'chartDashVA'];
    if (!state.charts.dashPower.data.labels.length) ids.forEach((id) => setChartState(id, 'Loading chart'));
    try {
        const { data, summary } = await apiGet('series', { panel_id: panelId, range: '24h' });
        if (panelId !== state.panelId) return;

        renderEnergySummary(summary);
        const labels = data.map((point) => seriesLabel(point.t, '24h'));
        state.charts.dashPower.data.labels = labels;
        state.charts.dashPower.data.datasets[0].data = data.map((point) => point.power);
        state.charts.dashPower.update();
        fillVoltageCurrent(state.charts.dashVA, labels, data);

        ids.forEach((id) => setChartState(id, data.length ? '' : 'No readings in the last 24 hours'));
    } catch (error) {
        if (panelId === state.panelId) {
            ids.forEach((id) => setChartState(id, 'Could not load this chart. It will retry shortly.'));
            renderEnergySummary(null);
        }
        console.error('Could not load the dashboard charts:', error);
    }
}

async function loadAnalyticsSeries() {
    const panelId = state.panelId;
    const range = state.range;
    const ids = ['chartAnaVA', 'chartAnaConsumption'];
    if (!state.charts.anaVA.data.labels.length) ids.forEach((id) => setChartState(id, 'Loading chart'));
    try {
        const { data, summary } = await apiGet('series', { panel_id: panelId, range });
        if (panelId !== state.panelId || range !== state.range) return;

        const labels = data.map((point) => seriesLabel(point.t, range));
        fillVoltageCurrent(state.charts.anaVA, labels, data);

        const energy = state.charts.anaEnergy;
        energy.data.labels = labels;
        energy.data.datasets[0].data = data.map((point) => point.energy_wh);
        colorEnergyBars(energy, chartColors());
        energy.update();

        // Energy needs two readings in the same period, or there is no duration to multiply by.
        const canEstimate = data.some((point) => point.samples > 1);
        if (canEstimate) {
            const wh = summary.energy_wh;
            setText('ana-energy-total', wh >= 1000 ? `${formatNumber(wh / 1000, 2)} kWh` : `${formatNumber(wh, 0)} Wh`);
        } else {
            setText('ana-energy-total', '--');
        }

        setChartState('chartAnaVA', data.length ? '' : 'No readings in this period');
        let energyMessage = '';
        if (!data.length) energyMessage = 'No readings in this period';
        else if (!canEstimate) energyMessage = 'Readings are too far apart to estimate energy for this period';
        setChartState('chartAnaConsumption', energyMessage);
    } catch (error) {
        if (panelId === state.panelId) {
            setText('ana-energy-total', '--');
            ids.forEach((id) => setChartState(id, 'Could not load this chart. It will retry shortly.'));
        }
        console.error('Could not load the analytics charts:', error);
    }
}

/* Weather */

const FIRMWARE_STATES = {
    up_to_date: { label: 'Up to date', pill: 'is-locked' },
    update_available: { label: 'Update pending', pill: 'is-tracking' },
    unknown: { label: 'Not reported', pill: 'is-none' },
};

const formatVersion = (version) => (version ? `v${version}` : '--');

function renderFirmware() {
    const info = state.firmware;
    const list = byId('firmware-list');
    if (!info) {
        list.innerHTML = '<div class="text-muted small">Firmware versions are unavailable right now.</div>';
        setText('trk-firmware', '--');
        setText('trk-firmware-note', 'Unavailable');
        renderDeviceStatus();
        return;
    }

    list.innerHTML = Object.entries(PANELS).map(([panelId, panel]) => {
        const row = info.data[panelId] || { state: 'unknown' };
        const status = FIRMWARE_STATES[row.state] || FIRMWARE_STATES.unknown;
        const seen = row.last_seen ? `Reported ${formatDateTime(row.last_seen)} WITA` : 'No firmware reported yet';
        return `
            <div class="firmware-row">
                <div class="firmware-array">
                    <div class="fw-semibold">${escapeHtml(panel.name)}</div>
                    <div class="text-xs text-muted">${escapeHtml(panel.type)}</div>
                </div>
                <dl class="firmware-versions">
                    <div><dt>Installed</dt><dd>${escapeHtml(formatVersion(row.installed))}</dd></div>
                    <div><dt>Latest</dt><dd>${escapeHtml(formatVersion(row.latest))}</dd></div>
                </dl>
                <span class="status-pill ${status.pill}"><span style="display:inline-block; width:6px; height:6px; background:currentColor; border-radius:50%;"></span>${escapeHtml(status.label)}</span>
                <div class="firmware-seen text-xs text-muted">${escapeHtml(seen)}</div>
            </div>`;
    }).join('');

    if (info.repo_url) byId('firmware-repo-link').href = info.repo_url;

    renderDeviceStatus();

    const current = info.data[state.panelId] || { state: 'unknown' };
    setText('trk-firmware', formatVersion(current.installed));
    setText('trk-firmware-note', {
        up_to_date: 'Up to date',
        update_available: `Update to ${formatVersion(current.latest)} pending`,
        unknown: current.installed ? 'Latest version unavailable' : 'Not reported by the device yet',
    }[current.state] || '');
}

async function loadFirmware() {
    try {
        state.firmware = await apiGet('firmware');
    } catch (error) {
        console.error('Could not load firmware versions:', error);
        state.firmware = null;
    }
    renderFirmware();
}

async function loadWeather() {
    try {
        const { data, stale } = await apiGet('weather/latest');
        const icon = byId('weather-icon');
        if (!data || stale) {
            setText('weather-desc', 'Unavailable');
            setText('weather-temp', '');
            setText('dash-irradiance', '--');
            icon.textContent = 'cloud';
            byId('weather-desc').title = data ? `Last recorded ${formatDateTime(data.observed_at)} WITA` : 'No weather recorded yet';
            renderWeatherDetails(null, data ? `Last recorded ${formatDateTime(data.observed_at)} WITA` : 'No weather recorded yet');
            return;
        }
        renderWeatherDetails(data, `Recorded ${formatDateTime(data.observed_at)} WITA`);
        setText('weather-desc', data.description);
        setText('weather-temp', data.temperature === null ? '' : `${data.temperature}°C`);
        setText('dash-irradiance', formatNumber(data.solar_radiation, 0));
        byId('weather-desc').title = `Recorded ${formatDateTime(data.observed_at)} WITA`;
        icon.textContent = WEATHER_ICONS[data.description] || 'cloud';
        const wet = ['rainy', 'thunderstorm'].includes(icon.textContent);
        icon.classList.toggle('text-info', wet);
        icon.classList.toggle('text-warning', !wet);
    } catch (error) {
        setText('weather-desc', 'Unavailable');
        setText('weather-temp', '');
        setText('dash-irradiance', '--');
        renderWeatherDetails(null, 'Weather is unavailable right now');
        console.error('Could not load the weather:', error);
    }
}

async function loadWeatherHistory() {
    const range = state.range;
    const chart = state.charts.anaWeather;
    if (!chart.data.labels.length) setChartState('chartAnaWeather', 'Loading chart');
    try {
        const { data } = await apiGet('weather', { range });
        if (range !== state.range) return;
        chart.data.labels = data.map((row) => seriesLabel(row.observed_at, range === '30d' ? '7d' : range));
        chart.data.datasets[0].data = data.map((row) => row.temperature);
        chart.data.datasets[1].data = data.map((row) => row.solar_radiation);
        chart.update();
        setChartState('chartAnaWeather', data.length ? '' : 'No weather recorded in this period');
    } catch (error) {
        setChartState('chartAnaWeather', 'Could not load this chart. It will retry shortly.');
        console.error('Could not load the weather history:', error);
    }
}

/* Logs */

function renderLogMessage(message, withRetry = false) {
    const body = byId('log-table-body');
    body.innerHTML = '';
    const row = document.createElement('tr');
    const cell = document.createElement('td');
    cell.colSpan = 8;
    cell.className = 'log-message';
    cell.textContent = message;
    if (withRetry) {
        const retry = document.createElement('button');
        retry.type = 'button';
        retry.className = 'btn btn-sm btn-outline-secondary ms-2';
        retry.textContent = 'Try again';
        retry.addEventListener('click', () => loadLogs());
        cell.appendChild(retry);
    }
    row.appendChild(cell);
    body.appendChild(row);
}

function statusPill(status) {
    return `<span class="status-pill ${STATUS_CLASSES[status] || 'is-none'}"><span style="display:inline-block; width:6px; height:6px; background:currentColor; border-radius:50%;"></span>${escapeHtml(status)}</span>`;
}

function renderLogRows(rows, animate) {
    state.logRows = rows;
    byId('log-table-body').innerHTML = rows.map((log, index) => {
        const valueClass = log.status === 'Tracking...' ? 'text-warning fw-bold' : log.status === 'Manual Override' ? 'text-secondary fw-bold' : '';
        return `
            <tr class="${ROW_CLASSES[log.status] || ''} ${animate ? 'log-row-enter' : ''}" style="${animate ? `animation-delay: ${index * 0.05}s` : ''}">
                <td class="text-start ps-4 text-nowrap">${escapeHtml(formatDateTime(log.created_at))}</td>
                <td class="fw-semibold">#${escapeHtml(String(log.id))}</td>
                <td class="${valueClass}">${formatNumber(log.voltage, 2)}</td>
                <td class="${valueClass}">${formatNumber(log.current, 2)}</td>
                <td>${formatNumber(log.power_output, 1)}</td>
                <td class="text-nowrap">${escapeHtml(log.weather_status || 'Unknown')}</td>
                <td>${statusPill(log.status)}</td>
                <td class="pe-4"><button type="button" class="log-detail-btn" data-log-index="${index}" aria-label="Show details for log ${escapeHtml(String(log.id))}"><span class="material-symbols-rounded" aria-hidden="true">visibility</span>Details</button></td>
            </tr>`;
    }).join('');
}

function renderPagination(meta) {
    const controls = byId('pagination-controls');
    const { current_page: current, last_page: last, total, per_page: perPage } = meta;

    if (!total) {
        setText('pagination-info', 'Showing 0 entries');
        controls.innerHTML = '';
        return;
    }

    const first = (current - 1) * perPage + 1;
    setText('pagination-info', `Showing ${first.toLocaleString()} to ${Math.min(current * perPage, total).toLocaleString()} of ${total.toLocaleString()} entries`);

    const item = (label, page, { disabled = false, active = false, ariaLabel = '' } = {}) => {
        const linkClass = active ? 'page-link bg-warning border-warning text-dark' : `page-link ${disabled ? '' : 'text-dark'}`;
        return `<li class="page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}">
            <button type="button" class="${linkClass}" ${page && !disabled && !active ? `data-page="${page}"` : ''} ${disabled ? 'disabled' : ''} ${active ? 'aria-current="page"' : ''} ${ariaLabel ? `aria-label="${ariaLabel}"` : ''}>${label}</button>
        </li>`;
    };

    const start = Math.max(1, current - 2);
    const end = Math.min(last, current + 2);
    let html = item('&laquo;', current - 1, { disabled: current === 1, ariaLabel: 'Previous page' });
    if (start > 1) html += item('1', 1);
    if (start > 2) html += item('...', null, { disabled: true });
    for (let page = start; page <= end; page++) html += item(String(page), page, { active: page === current });
    if (end < last - 1) html += item('...', null, { disabled: true });
    if (end < last) html += item(last.toLocaleString(), last);
    html += item('&raquo;', current + 1, { disabled: current === last, ariaLabel: 'Next page' });
    controls.innerHTML = html;
}

function logQuery() {
    const { start, end, status } = state.logFilters;
    return { panel_id: state.panelId, start_date: start, end_date: end, status };
}

function hasLogFilters() {
    const { start, end, status } = state.logFilters;
    return Boolean(start || end || status);
}

async function loadLogs({ animate = true, background = false, announce = false } = {}) {
    const panelId = state.panelId;
    const query = JSON.stringify(logQuery());
    if (!background) renderLogMessage('Loading readings');
    try {
        const { data, meta } = await apiGet('logs', { ...logQuery(), per_page: LOGS_PER_PAGE, page: state.logPage });
        if (panelId !== state.panelId || query !== JSON.stringify(logQuery())) return;

        // Rows can disappear (wiped or filtered), so step back to the last page that still exists.
        if (data.length === 0 && meta.total > 0 && state.logPage > meta.last_page) {
            state.logPage = meta.last_page;
            await loadLogs({ animate, background });
            return;
        }

        state.logPage = meta.current_page;
        if (data.length === 0) {
            state.logRows = [];
            renderLogMessage(hasLogFilters() ? 'No readings match this filter.' : 'No readings yet. They appear here once the device reports in.');
        } else {
            renderLogRows(data, animate);
        }
        renderPagination(meta);
        setText('log-updated', `Updated ${new Date().toLocaleTimeString('en-GB')}, refreshes every ${LOG_REFRESH_MS / 1000} s`);
        if (announce) showToast('success', `Found ${meta.total.toLocaleString()} records.`);
    } catch (error) {
        if (panelId !== state.panelId) return;
        if (background && state.logRows.length) {
            setText('log-updated', 'Could not refresh. Showing the last loaded readings.');
        } else {
            state.logRows = [];
            renderLogMessage('Could not load readings.', true);
            setText('log-updated', 'Could not refresh');
            renderPagination({ total: 0 });
        }
        console.error('Could not load logs:', error);
    }
}

function applyLogFilter() {
    const start = byId('filter-date-start').value;
    const end = byId('filter-date-end').value;
    if (start && end && start > end) {
        showToast('error', 'The start date must be on or before the end date.');
        return;
    }
    const status = byId('filter-status').value;
    state.logFilters = { start, end, status: status === 'All' ? '' : status };
    state.logPage = 1;
    loadLogs({ announce: true });
}

function clearLogFilter() {
    state.pickers.start?.clear();
    state.pickers.end?.clear();
    byId('filter-date-start').value = '';
    byId('filter-date-end').value = '';
    if (state.selects.status) state.selects.status.setValue('All', true);
    else byId('filter-status').value = 'All';
    state.logFilters = { start: '', end: '', status: '' };
    state.logPage = 1;
    loadLogs();
}

function showLogDetail(log) {
    const dual = log.panel_id === 'panel1';
    const sections = [
        ['Condition', [
            ['Mode', log.tracking_mode],
            ['Weather', log.weather_status || 'Unknown'],
            ...(log.firmware_version ? [['Firmware', formatVersion(log.firmware_version)]] : []),
        ]],
        ['Electrical', [
            ['Voltage', `${formatNumber(log.voltage, 2)} V`],
            ['Current', `${formatNumber(log.current, 2)} A`],
            ['Power', `${formatNumber(log.power_output, 1)} W`],
        ]],
        ['Position', [
            [dual ? 'Azimuth' : 'Target angle', `${formatNumber(log.azimuth)}°`],
            [dual ? 'Elevation' : 'Current angle', `${formatNumber(log.elevation)}°`],
            ...(dual && log.target_azimuth != null ? [['Target azimuth', `${formatNumber(log.target_azimuth)}°`]] : []),
            ...(dual && log.target_elevation != null ? [['Target elevation', `${formatNumber(log.target_elevation)}°`]] : []),
        ]],
    ];
    if (dual) {
        sections.push(['Light sensors', [
            ['North', formatNumber(log.ldr_nw, 0)],
            ['East', formatNumber(log.ldr_ne, 0)],
            ['West', formatNumber(log.ldr_sw, 0)],
            ['South', formatNumber(log.ldr_se, 0)],
        ]]);
    }

    setText('log-detail-title', `Log #${log.id}`);
    byId('log-detail-body').innerHTML = `
        <div class="detail-summary">
            <div>
                <div class="detail-summary-time">${escapeHtml(formatDateTime(log.created_at))} WITA</div>
                <div class="detail-summary-array">${escapeHtml(PANELS[log.panel_id]?.name || log.panel_id)}</div>
            </div>
            ${statusPill(log.status)}
        </div>
        ${sections.map(([title, facts]) => `
            <h3 class="detail-section-title">${escapeHtml(title)}</h3>
            <dl class="detail-grid detail-grid-${facts.length}">
                ${facts.map(([term, value]) => `<div><dt>${escapeHtml(term)}</dt><dd>${escapeHtml(value)}</dd></div>`).join('')}
            </dl>`).join('')}`;
    bootstrap.Modal.getOrCreateInstance(byId('log-detail-modal')).show();
}

/* Export */

// A real .xlsx keeps columns and decimals intact in every Excel language, unlike CSV,
// which Indonesian Excel reads as a single column. Loaded on demand because it is large.
const XLSX_LIBRARY_URL = 'https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js';
let xlsxLibrary = null;

function loadXlsxLibrary() {
    if (window.XLSX) return Promise.resolve(window.XLSX);
    if (!xlsxLibrary) {
        xlsxLibrary = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = XLSX_LIBRARY_URL;
            script.onload = () => resolve(window.XLSX);
            script.onerror = () => {
                xlsxLibrary = null;
                script.remove();
                reject(new Error('could not load the Excel library'));
            };
            document.head.appendChild(script);
        });
    }
    return xlsxLibrary;
}

// Excel stores dates as days since 1899-12-30. Built by hand so the cell shows WITA
// on every computer, whatever timezone it is set to.
function excelDate(iso) {
    const witaMs = new Date(iso).getTime() + 8 * 60 * 60 * 1000;
    return { t: 'n', v: witaMs / 86400000 + 25569, z: 'yyyy-mm-dd hh:mm:ss' };
}

function buildWorkbook(XLSX, sheetName, header, rows) {
    const sheet = XLSX.utils.aoa_to_sheet([header, ...rows]);
    sheet['!cols'] = header.map((title, index) => ({ wch: Math.max(title.length + 2, index === 0 ? 20 : 10) }));
    sheet['!autofilter'] = { ref: XLSX.utils.encode_range({ s: { r: 0, c: 0 }, e: { r: rows.length, c: header.length - 1 } }) };
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, sheet, sheetName);
    return workbook;
}

async function downloadWorkbook(filename, sheetName, header, rows) {
    const XLSX = await loadXlsxLibrary();
    XLSX.writeFile(buildWorkbook(XLSX, sheetName, header, rows), filename);
}

async function runExport(button, task) {
    button.disabled = true;
    try {
        await task();
    } catch (error) {
        showToast('error', `Export failed: ${error.message}`);
    } finally {
        button.disabled = false;
    }
}

async function exportLogs() {
    const panelId = state.panelId;
    const { data } = await apiGet('logs', logQuery());
    if (!data.length) {
        showToast('error', 'No data available to export.');
        return;
    }
    const dual = panelId === 'panel1';
    // Array Beta stores its target angle in azimuth and its current angle in elevation.
    const position = dual ? ['Azimuth', 'Elevation', 'Target azimuth', 'Target elevation'] : ['Target angle', 'Current angle'];
    const header = ['Timestamp (WITA)', ...position, 'Voltage (V)', 'Current (A)', 'Power (W)',
        ...(dual ? ['LDR North', 'LDR East', 'LDR West', 'LDR South'] : []), 'Mode', 'Status', 'Weather'];
    const rows = data.map((row) => [
        excelDate(row.created_at), row.azimuth, row.elevation,
        ...(dual ? [row.target_azimuth ?? null, row.target_elevation ?? null] : []),
        row.voltage, row.current, row.power_output,
        ...(dual ? [row.ldr_nw, row.ldr_ne, row.ldr_sw, row.ldr_se] : []), row.tracking_mode, row.status, row.weather_status,
    ]);
    await downloadWorkbook(`solaris_${panelId}_logs_${Date.now()}.xlsx`, 'Logs', header, rows);
    showToast('success', `Exported ${data.length.toLocaleString()} records.`);
}

async function exportAnalytics() {
    const { data } = await apiGet('series', { panel_id: state.panelId, range: state.range });
    if (!data.length) {
        showToast('error', 'No data available to export.');
        return;
    }
    const header = ['Period start (WITA)', 'Avg voltage (V)', 'Avg current (A)', 'Avg power (W)', 'Peak power (W)', 'Estimated energy (Wh)', 'Readings'];
    const rows = data.map((row) => [excelDate(row.t), row.voltage, row.current, row.power, row.peak_power, row.energy_wh, row.samples]);
    await downloadWorkbook(`solaris_${state.panelId}_${state.range}_${Date.now()}.xlsx`, 'Analytics', header, rows);
    showToast('success', 'Data exported successfully!');
}

async function exportWeather() {
    const { data } = await apiGet('weather', { range: state.range });
    if (!data.length) {
        showToast('error', 'No weather recorded in this period.');
        return;
    }
    const header = ['Observed (WITA)', 'Condition', 'Temperature (C)', 'Humidity (%)', 'Cloud cover (%)', 'Precipitation (mm)', 'Wind (km/h)', 'Solar radiation (W/m2)', 'WMO code'];
    const rows = data.map((row) => [excelDate(row.observed_at), row.description, row.temperature, row.humidity, row.cloud_cover, row.precipitation, row.wind_speed, row.solar_radiation, row.weather_code]);
    await downloadWorkbook(`solaris_weather_${state.range}_${Date.now()}.xlsx`, 'Weather', header, rows);
    showToast('success', `Exported ${data.length.toLocaleString()} weather records.`);
}

/* Test data tools (only rendered when TRACKER_DEV_TOOLS is on) */

function refreshAfterDataChange() {
    state.logPage = 1;
    fetchLatest();
    refreshView(state.view);
}

async function seedDatabase() {
    showToast('info', 'Generating random historical records...');
    try {
        const json = await apiGet('seed', { panel_id: byId('dev-tools-subsystem').value });
        showToast('success', json.message || 'Data generated successfully!');
        refreshAfterDataChange();
    } catch (error) {
        showToast('error', `Failed to generate data: ${error.message}`);
    }
}

async function resetDatabase() {
    const select = byId('dev-tools-subsystem');
    const label = select.options[select.selectedIndex].text;
    if (!confirm(`Are you sure you want to completely wipe the logs for: ${label}? This cannot be undone!`)) return;
    try {
        await apiPost('reset', { panel_id: select.value });
        showToast('success', 'Database wiped successfully!');
        refreshAfterDataChange();
    } catch (error) {
        showToast('error', `Failed to wipe database: ${error.message}`);
    }
}

/* Theme */

function activeTheme(theme) {
    if (theme !== 'auto') return theme;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function applyTheme(theme) {
    const active = activeTheme(theme);
    document.documentElement.setAttribute('data-bs-theme', active);
    setText('theme-toggle-icon', active === 'dark' ? 'light_mode' : 'dark_mode');
    applyChartTheme();
}

function setTheme(theme) {
    localStorage.setItem('theme', theme);
    byId('theme-select').value = theme;
    applyTheme(theme);
}

/* Wiring */

function bindEvents() {
    document.addEventListener('click', (event) => {
        const view = event.target.closest('.nav-link[data-view]');
        if (view) {
            event.preventDefault();
            navigateTo(view.dataset.view);
            return;
        }
        const panel = event.target.closest('[data-panel]');
        if (panel) {
            switchPanel(panel.dataset.panel);
            return;
        }
        const page = event.target.closest('[data-page]');
        if (page) {
            state.logPage = Number(page.dataset.page);
            loadLogs();
            return;
        }
        const detail = event.target.closest('[data-log-index]');
        if (detail) showLogDetail(state.logRows[Number(detail.dataset.logIndex)]);
    });

    byId('btn-mode-auto').addEventListener('click', () => setTrackerMode('Automatic'));
    byId('btn-mode-stop').addEventListener('click', () => setTrackerMode('Stop'));
    byId('btn-mode-reset').addEventListener('click', sendResetCommand);
    byId('manual-target-form').addEventListener('submit', sendManualTarget);
    byId('btn-trk-auto').addEventListener('click', () => setTrackerMode('Automatic'));
    byId('btn-trk-manual').addEventListener('click', () => setTrackerMode('Manual'));

    const positionCard = byId('dash-position-card');
    positionCard.addEventListener('click', () => navigateTo('tracker'));
    positionCard.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            navigateTo('tracker');
        }
    });

    byId('notice-btn').addEventListener('click', showSystemNotice);

    byId('log-filter-form').addEventListener('submit', (event) => {
        event.preventDefault();
        applyLogFilter();
    });
    byId('filter-clear').addEventListener('click', clearLogFilter);
    byId('filter-subsystem').addEventListener('change', (event) => {
        if (event.target.value !== state.panelId) switchPanel(event.target.value);
    });

    byId('export-logs').addEventListener('click', (event) => runExport(event.currentTarget, exportLogs));
    byId('export-analytics').addEventListener('click', (event) => runExport(event.currentTarget, exportAnalytics));
    byId('export-weather').addEventListener('click', (event) => runExport(event.currentTarget, exportWeather));

    byId('analytics-range').addEventListener('change', (event) => {
        state.range = event.target.value;
        loadAnalyticsSeries();
        loadWeatherHistory();
    });

    byId('theme-toggle').addEventListener('click', () => {
        const stored = localStorage.getItem('theme') || 'light';
        setTheme(activeTheme(stored) === 'dark' ? 'light' : 'dark');
    });
    byId('theme-select').addEventListener('change', (event) => setTheme(event.target.value));
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (localStorage.getItem('theme') === 'auto') applyTheme('auto');
    });

    byId('poll-select').addEventListener('change', (event) => {
        state.pollMs = Number(event.target.value);
        localStorage.setItem('pollMs', state.pollMs);
        restartPolling();
        showToast('success', 'Polling interval updated');
    });

    byId('seed-data')?.addEventListener('click', seedDatabase);
    byId('wipe-data')?.addEventListener('click', resetDatabase);

    window.addEventListener('hashchange', () => navigateTo(location.hash.slice(1), { updateHash: false }));
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            fetchLatest();
            refreshView(state.view);
        }
    });
}

function initFilters() {
    if (typeof flatpickr !== 'undefined') {
        state.pickers.start = flatpickr('#filter-date-start', { dateFormat: 'Y-m-d' });
        state.pickers.end = flatpickr('#filter-date-end', { dateFormat: 'Y-m-d' });
    }
    if (typeof TomSelect !== 'undefined') {
        state.selects.status = new TomSelect('#filter-status', { create: false, controlInput: null });
        state.selects.subsystem = new TomSelect('#filter-subsystem', { create: false, controlInput: null });
    }
    if (state.selects.subsystem) state.selects.subsystem.setValue(state.panelId, true);
    else byId('filter-subsystem').value = state.panelId;
}

function init() {
    initCharts();
    initFilters();
    bindEvents();

    const theme = localStorage.getItem('theme') || 'light';
    byId('theme-select').value = theme;
    applyTheme(theme);

    const pollOption = byId('poll-select').querySelector(`option[value="${state.pollMs}"]`);
    if (pollOption) pollOption.selected = true;
    else state.pollMs = 2000;

    navigateTo(location.hash.slice(1) || 'dashboard', { updateHash: false });
    switchPanel(state.panelId, { silent: true });

    restartPolling();
    loadWeather();
    setInterval(loadWeather, WEATHER_REFRESH_MS);
    loadFirmware();
    setInterval(() => {
        if (!document.hidden) loadFirmware();
    }, FIRMWARE_REFRESH_MS);
    setInterval(() => {
        if (document.hidden) return;
        if (state.view === 'dashboard') loadDashboardSeries();
        if (state.view === 'charts') {
            loadAnalyticsSeries();
            loadWeatherHistory();
        }
    }, SERIES_REFRESH_MS);
    setInterval(() => {
        if (!document.hidden && state.view === 'logs') loadLogs({ animate: false, background: true });
    }, LOG_REFRESH_MS);
}

window.addEventListener('DOMContentLoaded', init);
