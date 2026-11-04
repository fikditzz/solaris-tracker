'use strict';

const TIMEZONE = 'Asia/Makassar';
const OFFLINE_AFTER_SECONDS = 180;
const LOGS_PER_PAGE = 10;
const LOG_REFRESH_MS = 10000;
const SERIES_REFRESH_MS = 60000;
const WEATHER_REFRESH_MS = 30 * 60 * 1000;
const SITE = { lat: -5.2150509, lon: 119.5793433 };

const VIEWS = {
    dashboard: 'Dashboard',
    tracker: 'Tracker',
    charts: 'Analytics',
    logs: 'Logs',
    settings: 'Settings',
};

const PANELS = {
    panel1: { name: 'Array Alpha', axis: 'dual', type: 'Dual-axis tracker', algorithm: 'LDR sensors' },
    panel2: { name: 'Array Beta', axis: 'single', type: 'Single-axis tracker', algorithm: 'Astronomical' },
};

const MODE_LABELS = { Automatic: 'Automatic', Manual: 'Manual', Stop: 'Stopped' };

const COMMAND_LABELS = {
    Up: 'Tilt up',
    Down: 'Tilt down',
    Left: 'Rotate left',
    Right: 'Rotate right',
    Reset: 'Reset position',
};

const COMPASS = ['North', 'North-East', 'East', 'South-East', 'South', 'South-West', 'West', 'North-West'];

const STATUS_CLASSES = {
    'Sun Locked': 'is-locked',
    'Tracking...': 'is-tracking',
    'Manual Override': 'is-manual',
    'Stopped': 'is-stopped',
};

const state = {
    panelId: PANELS[localStorage.getItem('panelId')] ? localStorage.getItem('panelId') : 'panel1',
    view: 'dashboard',
    pollMs: Number(localStorage.getItem('pollMs')) || 2000,
    pollTimer: null,
    latest: null,
    range: '24h',
    charts: {},
    logPage: 1,
    logRows: [],
    logFilters: { start: '', end: '', status: '' },
    logsLoadedOnce: false,
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
    if (!response.ok) {
        throw new Error(body?.message || `Request failed (${response.status})`);
    }
    return body;
}

const apiGet = (path, params = {}) => {
    const query = new URLSearchParams(Object.entries(params).filter(([, value]) => value !== '' && value != null));
    return requestJson(`/api/tracker/${path}?${query}`);
};

const apiPost = (path, payload) => requestJson(`/api/tracker/${path}`, { method: 'POST', body: JSON.stringify(payload) });

function showToast(type, message) {
    const icons = { success: 'check_circle', warning: 'warning', error: 'error', info: 'info' };
    const toast = document.createElement('div');
    toast.className = `toast-custom toast-${icons[type] ? type : 'info'}`;
    const icon = document.createElement('span');
    icon.className = 'material-symbols-rounded';
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = icons[type] || icons.info;
    const text = document.createElement('span');
    text.textContent = message;
    toast.append(icon, text);
    byId('toast-container').appendChild(toast);
    setTimeout(() => {
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

/* Navigation */

function refreshView(view) {
    if (view === 'dashboard') loadDashboardSeries();
    if (view === 'charts') loadAnalyticsSeries();
    if (view === 'logs') loadLogs();
}

function navigateTo(view, { updateHash = true } = {}) {
    if (!VIEWS[view]) view = 'dashboard';
    state.view = view;

    document.querySelectorAll('.page-view').forEach((el) => el.classList.toggle('active', el.id === `view-${view}`));
    document.querySelectorAll('.nav-link[data-view]').forEach((el) => {
        const isCurrent = el.dataset.view === view;
        el.classList.toggle('active', isCurrent);
        if (isCurrent) el.setAttribute('aria-current', 'page');
        else el.removeAttribute('aria-current');
    });
    setText('top-title', VIEWS[view]);
    document.title = `${VIEWS[view]} | Solaris Tracker`;
    if (updateHash && location.hash !== `#${view}`) history.replaceState(null, '', `#${view}`);

    Object.values(state.charts).forEach((chart) => chart.resize());
    refreshView(view);
}

function switchPanel(panelId, { silent = false } = {}) {
    if (!PANELS[panelId]) return;
    state.panelId = panelId;
    localStorage.setItem('panelId', panelId);

    const panel = PANELS[panelId];
    document.body.dataset.axis = panel.axis;
    setText('panelDropdownText', panel.name);
    setText('tracker-panel-title', panel.type);
    setText('algorithm-type', panel.algorithm);
    setText('log-array-name', panel.name);
    document.querySelectorAll('[data-tracking-type]').forEach((el) => { el.textContent = panel.type; });
    document.querySelectorAll('[data-panel]').forEach((el) => {
        el.classList.toggle('active', el.dataset.panel === panelId);
    });

    state.latest = null;
    state.logPage = 1;
    renderLatest(null);
    fetchLatest();
    refreshView(state.view);
    if (!silent) showToast('info', `Showing ${panel.name}`);
}

/* Live readings */

function renderConnection(kind, text) {
    const badge = byId('system-status-badge');
    badge.classList.remove('is-online', 'is-offline', 'is-waiting');
    badge.classList.add(`is-${kind}`);
    setText('system-status-text', text);
}

function renderModeControls(mode) {
    document.querySelectorAll('[data-mode]').forEach((button) => {
        const active = button.dataset.mode === mode;
        button.classList.toggle('active', active);
        button.setAttribute('aria-pressed', String(active));
    });
    document.querySelectorAll('[data-mode-label]').forEach((el) => {
        el.textContent = MODE_LABELS[mode] || '--';
    });

    const manual = mode === 'Manual';
    document.querySelectorAll('#d-pad-container .d-btn').forEach((button) => { button.disabled = !manual; });
    setText('d-pad-note', manual
        ? 'Each press is sent the next time the device reports in.'
        : 'Switch to Manual to move the panel.');
}

function compassName(degrees) {
    return COMPASS[Math.round((((degrees % 360) + 360) % 360) / 45) % 8];
}

function renderPosition(data) {
    const azimuth = data ? data.azimuth : null;
    const elevation = data ? data.elevation : null;

    setText('dash-azimuth', formatNumber(azimuth));
    setText('dash-elevation', formatNumber(elevation));
    setText('trk-azimuth', formatNumber(azimuth, 0));
    setText('trk-elevation', formatNumber(elevation, 0));

    ['dash', 'trk'].forEach((prefix) => {
        setText(`${prefix}-map-az`, formatNumber(azimuth, 0));
        setText(`${prefix}-map-el`, formatNumber(elevation, 0));
        byId(`${prefix}-azimuth-ptr`).style.transform = `rotate(${azimuth ?? 0}deg)`;
        byId(`${prefix}-elevation-ptr`).style.transform = `rotate(${(elevation ?? 90) - 90}deg)`;
    });

    setText('trk-azimuth-lbl', data ? `Facing ${compassName(azimuth)}` : 'No data');
    setText('trk-elevation-lbl', data ? 'Reported panel tilt' : 'No data');
}

function renderLdr(data) {
    const values = {
        NW: data ? data.ldr_nw : null,
        NE: data ? data.ldr_ne : null,
        SW: data ? data.ldr_sw : null,
        SE: data ? data.ldr_se : null,
    };
    ['dash', 'trk'].forEach((prefix) => {
        Object.entries(values).forEach(([key, value]) => {
            setText(`${prefix}-ldr-${key.toLowerCase()}`, formatNumber(value, 0));
        });
    });

    const readings = data ? Object.values(values).map(Number) : [];
    const max = readings.length ? Math.max(...readings) : null;
    document.querySelectorAll('[data-ldr-box]').forEach((box) => {
        const value = values[box.dataset.ldrBox];
        box.classList.toggle('is-max', max !== null && max > 0 && Number(value) === max);
    });

    let margin = null;
    if (data) {
        const [n, e, w, s] = [Number(values.NW), Number(values.NE), Number(values.SW), Number(values.SE)];
        const average = (n + e + w + s) / 4;
        if (average > 0) {
            const maxDiff = Math.max(Math.abs(n - e), Math.abs(w - s), Math.abs(n - w), Math.abs(e - s));
            margin = Math.min((maxDiff / average) * 100, 100);
        }
    }
    document.querySelectorAll('[data-diff-margin]').forEach((el) => {
        el.textContent = margin === null ? '--' : `${margin.toFixed(1)}%`;
        el.classList.toggle('is-good', margin !== null && margin < 10);
        el.classList.toggle('is-warn', margin !== null && margin >= 10 && margin < 30);
        el.classList.toggle('is-bad', margin !== null && margin >= 30);
    });
}

function renderLatest(data) {
    setText('dash-power', formatNumber(data ? data.power_output : null, 0));
    setText('dash-voltage', formatNumber(data ? data.voltage : null));
    setText('dash-current', formatNumber(data ? data.current : null));
    setText('dash-irradiance', data && Number(data.irradiance) > 0 ? formatNumber(data.irradiance, 0) : '--');

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

    renderModeControls(data ? data.tracking_mode : null);
    renderPosition(data);
    renderLdr(data);

    const badge = byId('trk-status-badge');
    badge.className = `lock-badge ${data ? STATUS_CLASSES[data.status] || '' : ''}`;
    badge.textContent = data ? data.status : 'No data';
}

async function fetchLatest() {
    const panelId = state.panelId;
    try {
        const json = await apiGet('latest', { panel_id: panelId });
        if (panelId !== state.panelId) return;

        state.latest = json.data;
        renderLatest(json.data);

        if (!json.data) {
            renderConnection('waiting', 'No data yet');
            return;
        }
        const ageSeconds = (Date.now() - new Date(json.data.created_at).getTime()) / 1000;
        if (ageSeconds <= OFFLINE_AFTER_SECONDS) renderConnection('online', 'System Active');
        else renderConnection('offline', 'System Offline');
    } catch (error) {
        if (panelId === state.panelId) renderConnection('offline', 'Server unreachable');
        console.error('Could not load the latest reading:', error);
    }
}

function restartPolling() {
    clearInterval(state.pollTimer);
    state.pollTimer = setInterval(() => {
        if (!document.hidden) fetchLatest();
    }, state.pollMs);
}

/* Controls */

async function setTrackerMode(mode) {
    const panel = PANELS[state.panelId];
    const buttons = document.querySelectorAll('[data-mode]');
    buttons.forEach((button) => { button.disabled = true; });
    try {
        await apiPost('mode', { mode, panel_id: state.panelId });
        await fetchLatest();
        if (state.latest) showToast('success', `${panel.name} set to ${MODE_LABELS[mode]}`);
        else showToast('info', `${panel.name} will use ${MODE_LABELS[mode]} when it first reports in`);
    } catch (error) {
        showToast('error', `Could not change the mode: ${error.message}`);
    } finally {
        buttons.forEach((button) => { button.disabled = false; });
    }
}

async function sendManualCommand(command) {
    const panel = PANELS[state.panelId];
    try {
        await apiPost('command', { command, panel_id: state.panelId });
        showToast('success', `${COMMAND_LABELS[command]} queued for ${panel.name}`);
    } catch (error) {
        showToast('error', `Could not send the command: ${error.message}`);
    }
}

/* Charts */

function chartColors() {
    const styles = getComputedStyle(document.documentElement);
    const read = (name) => styles.getPropertyValue(name).trim();
    return {
        text: read('--text-muted'),
        grid: read('--border-color'),
        amber: read('--chart-amber'),
        blue: read('--chart-blue'),
        bar: read('--chart-bar'),
    };
}

function axis(colors, overrides = {}) {
    return {
        ticks: { color: colors.text, maxTicksLimit: 6 },
        grid: { color: colors.grid },
        border: { display: false },
        ...overrides,
    };
}

function timeAxis(colors) {
    return axis(colors, {
        ticks: { color: colors.text, maxTicksLimit: 7, maxRotation: 0, autoSkip: true },
        grid: { display: false },
    });
}

function valueAxes(colors) {
    return {
        x: timeAxis(colors),
        y: axis(colors, { position: 'left', title: { display: true, text: 'Voltage (V)', color: colors.blue } }),
        y1: axis(colors, { position: 'right', grid: { display: false }, title: { display: true, text: 'Current (A)', color: colors.amber } }),
    };
}

function voltageCurrentDatasets(colors) {
    return [
        { label: 'Voltage (V)', data: [], yAxisID: 'y', borderColor: colors.blue, backgroundColor: colors.blue, borderWidth: 2, tension: 0.3, pointRadius: 0, pointHoverRadius: 4 },
        { label: 'Current (A)', data: [], yAxisID: 'y1', borderColor: colors.amber, backgroundColor: colors.amber, borderWidth: 2, tension: 0.3, pointRadius: 0, pointHoverRadius: 4 },
    ];
}

function legend(colors) {
    return { position: 'bottom', labels: { color: colors.text, boxWidth: 10, usePointStyle: true } };
}

function initCharts() {
    Chart.defaults.font.family = "'Inter', sans-serif";
    const colors = chartColors();
    const base = { responsive: true, maintainAspectRatio: false, animation: false, interaction: { mode: 'index', intersect: false } };

    state.charts.dashPower = new Chart(byId('chartDashPower'), {
        type: 'line',
        data: { labels: [], datasets: [{ label: 'Power (W)', data: [], borderColor: colors.amber, backgroundColor: colors.amber, borderWidth: 2, tension: 0.3, pointRadius: 0, pointHoverRadius: 4 }] },
        options: { ...base, plugins: { legend: { display: false } }, scales: { x: timeAxis(colors), y: axis(colors, { beginAtZero: true }) } },
    });
    state.charts.dashVA = new Chart(byId('chartDashVA'), {
        type: 'line',
        data: { labels: [], datasets: voltageCurrentDatasets(colors) },
        options: { ...base, plugins: { legend: legend(colors) }, scales: valueAxes(colors) },
    });
    state.charts.anaVA = new Chart(byId('chartAnaVA'), {
        type: 'line',
        data: { labels: [], datasets: voltageCurrentDatasets(colors) },
        options: { ...base, plugins: { legend: legend(colors) }, scales: valueAxes(colors) },
    });
    state.charts.anaEnergy = new Chart(byId('chartAnaEnergy'), {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'Estimated energy (Wh)', data: [], backgroundColor: colors.bar, borderRadius: 3 }] },
        options: { ...base, plugins: { legend: { display: false } }, scales: { x: timeAxis(colors), y: axis(colors, { beginAtZero: true }) } },
    });
}

function applyChartTheme() {
    const colors = chartColors();
    const { dashPower, dashVA, anaVA, anaEnergy } = state.charts;
    if (!dashPower) return;

    dashPower.data.datasets[0].borderColor = colors.amber;
    dashPower.data.datasets[0].backgroundColor = colors.amber;
    dashPower.options.scales = { x: timeAxis(colors), y: axis(colors, { beginAtZero: true }) };

    [dashVA, anaVA].forEach((chart) => {
        chart.data.datasets[0].borderColor = colors.blue;
        chart.data.datasets[0].backgroundColor = colors.blue;
        chart.data.datasets[1].borderColor = colors.amber;
        chart.data.datasets[1].backgroundColor = colors.amber;
        chart.options.plugins.legend = legend(colors);
        chart.options.scales = valueAxes(colors);
    });

    anaEnergy.options.scales = { x: timeAxis(colors), y: axis(colors, { beginAtZero: true }) };
    colorEnergyBars(anaEnergy, colors);

    Object.values(state.charts).forEach((chart) => chart.update());
}

function colorEnergyBars(chart, colors) {
    const count = chart.data.labels.length;
    chart.data.datasets[0].backgroundColor = Array.from({ length: count }, (_, index) => (index === count - 1 ? colors.amber : colors.bar));
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
        const { data } = await apiGet('series', { panel_id: panelId, range: '24h' });
        if (panelId !== state.panelId) return;

        const labels = data.map((point) => seriesLabel(point.t, '24h'));
        state.charts.dashPower.data.labels = labels;
        state.charts.dashPower.data.datasets[0].data = data.map((point) => point.power);
        state.charts.dashPower.update();
        fillVoltageCurrent(state.charts.dashVA, labels, data);

        ids.forEach((id) => setChartState(id, data.length ? '' : 'No readings in the last 24 hours'));
    } catch (error) {
        if (panelId === state.panelId) ids.forEach((id) => setChartState(id, 'Could not load this chart. It will retry shortly.'));
        console.error('Could not load the dashboard charts:', error);
    }
}

function renderSummary(summary, canEstimateEnergy) {
    const wh = summary && canEstimateEnergy ? summary.energy_wh : null;
    const useKwh = wh === null || wh >= 1000;
    setText('ana-energy', wh === null ? '--' : formatNumber(useKwh ? wh / 1000 : wh, useKwh ? 2 : 0));
    setText('ana-energy-unit', useKwh ? 'kWh' : 'Wh');
    setText('ana-peak', summary ? formatNumber(summary.peak_power, 0) : '--');
    setText('ana-avg', summary ? formatNumber(summary.avg_power, 0) : '--');
    setText('ana-samples', summary ? formatNumber(summary.samples, 0) : '--');
}

async function loadAnalyticsSeries() {
    const panelId = state.panelId;
    const range = state.range;
    const ids = ['chartAnaVA', 'chartAnaEnergy'];
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
        const canEstimateEnergy = data.some((point) => point.samples > 1);
        renderSummary(data.length ? summary : null, canEstimateEnergy);
        setChartState('chartAnaVA', data.length ? '' : 'No readings in this period');
        let energyMessage = '';
        if (!data.length) energyMessage = 'No readings in this period';
        else if (!canEstimateEnergy) energyMessage = 'Readings are too far apart to estimate energy for this period';
        setChartState('chartAnaEnergy', energyMessage);
    } catch (error) {
        if (panelId === state.panelId) {
            renderSummary(null);
            ids.forEach((id) => setChartState(id, 'Could not load this chart. It will retry shortly.'));
        }
        console.error('Could not load the analytics charts:', error);
    }
}

/* Logs */

function renderLogMessage(message, withRetry = false) {
    const body = byId('log-table-body');
    body.innerHTML = '';
    const row = document.createElement('tr');
    const cell = document.createElement('td');
    cell.colSpan = 7;
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

function renderLogRows(rows, animate) {
    state.logRows = rows;
    byId('log-table-body').innerHTML = rows.map((log, index) => `
        <tr class="${animate ? 'row-enter' : ''}" style="${animate ? `animation-delay: ${index * 0.03}s` : ''}">
            <td class="ps-4 text-nowrap">${escapeHtml(formatDateTime(log.created_at))}</td>
            <td>#${escapeHtml(String(log.id))}</td>
            <td class="text-end">${formatNumber(log.voltage, 2)}</td>
            <td class="text-end">${formatNumber(log.current, 2)}</td>
            <td class="text-end">${formatNumber(log.power_output, 1)}</td>
            <td class="text-nowrap"><span class="lock-badge ${STATUS_CLASSES[log.status] || ''}">${escapeHtml(log.status)}</span></td>
            <td class="pe-4 text-end">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-log-index="${index}" aria-label="Show details for log ${escapeHtml(String(log.id))}">Details</button>
            </td>
        </tr>
    `).join('');
}

function renderPagination(meta) {
    const controls = byId('pagination-controls');
    const { current_page: current, last_page: last, total, per_page: perPage } = meta;

    if (total === 0) {
        setText('pagination-info', 'Showing 0 entries');
        controls.innerHTML = '';
        return;
    }

    const first = (current - 1) * perPage + 1;
    setText('pagination-info', `Showing ${first.toLocaleString()} to ${Math.min(current * perPage, total).toLocaleString()} of ${total.toLocaleString()} entries`);

    const item = (label, page, { disabled = false, active = false, ariaLabel = '' } = {}) => `
        <li class="page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}">
            <button type="button" class="page-link" ${page && !disabled && !active ? `data-page="${page}"` : ''} ${disabled ? 'disabled' : ''} ${active ? 'aria-current="page"' : ''} ${ariaLabel ? `aria-label="${ariaLabel}"` : ''}>${label}</button>
        </li>`;

    const start = Math.max(1, current - 1);
    const end = Math.min(last, current + 1);
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
            renderLogMessage(hasLogFilters() ? 'No readings match this filter.' : 'No readings yet. They appear here once the device reports in.');
            state.logRows = [];
        } else {
            renderLogRows(data, animate);
        }
        renderPagination(meta);
        setText('log-updated', `Updated ${new Date().toLocaleTimeString('en-GB')}, refreshes every ${LOG_REFRESH_MS / 1000} s`);
        if (announce) showToast('success', `Found ${meta.total.toLocaleString()} readings`);
    } catch (error) {
        if (panelId !== state.panelId) return;
        if (background && state.logRows.length) {
            setText('log-updated', 'Could not refresh. Showing the last loaded readings.');
        } else {
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
    state.logFilters = { start, end, status: byId('filter-status').value };
    state.logPage = 1;
    loadLogs({ announce: true });
}

function clearLogFilter() {
    byId('log-filter-form').reset();
    state.logFilters = { start: '', end: '', status: '' };
    state.logPage = 1;
    loadLogs();
}

function showLogDetail(log) {
    const isDual = log.panel_id === 'panel1';
    const facts = [
        ['Time (WITA)', formatDateTime(log.created_at)],
        ['Array', PANELS[log.panel_id]?.name || log.panel_id],
        ['Status', log.status],
        ['Mode', MODE_LABELS[log.tracking_mode] || log.tracking_mode],
        ['Voltage', `${formatNumber(log.voltage, 2)} V`],
        ['Current', `${formatNumber(log.current, 2)} A`],
        ['Power', `${formatNumber(log.power_output, 1)} W`],
        [isDual ? 'Azimuth' : 'Target angle', `${formatNumber(log.azimuth)}°`],
        [isDual ? 'Tilt' : 'Current angle', `${formatNumber(log.elevation)}°`],
    ];
    if (isDual) {
        facts.push(
            ['LDR North', formatNumber(log.ldr_nw, 0)],
            ['LDR East', formatNumber(log.ldr_ne, 0)],
            ['LDR West', formatNumber(log.ldr_sw, 0)],
            ['LDR South', formatNumber(log.ldr_se, 0)],
        );
    }

    setText('log-detail-title', `Reading #${log.id}`);
    byId('log-detail-body').innerHTML = facts
        .map(([term, value]) => `<div><dt>${escapeHtml(term)}</dt><dd>${escapeHtml(value)}</dd></div>`)
        .join('');
    bootstrap.Modal.getOrCreateInstance(byId('log-detail-modal')).show();
}

/* Export */

function downloadCsv(filename, header, rows) {
    const cell = (value) => {
        const text = String(value ?? '');
        return /[",\n]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
    };
    const csv = [header, ...rows].map((row) => row.map(cell).join(',')).join('\n');
    const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
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
        showToast('error', 'There are no readings to export.');
        return;
    }

    const isDual = panelId === 'panel1';
    const header = ['Timestamp (WITA)', 'Azimuth', 'Elevation', 'Voltage (V)', 'Current (A)', 'Power (W)',
        ...(isDual ? ['LDR North', 'LDR East', 'LDR West', 'LDR South'] : []), 'Mode', 'Status'];
    const rows = data.map((row) => [
        formatDateTime(row.created_at), row.azimuth, row.elevation, row.voltage, row.current, row.power_output,
        ...(isDual ? [row.ldr_nw, row.ldr_ne, row.ldr_sw, row.ldr_se] : []), row.tracking_mode, row.status,
    ]);
    downloadCsv(`solaris_${panelId}_logs_${Date.now()}.csv`, header, rows);
    showToast('success', `Exported ${data.length.toLocaleString()} readings`);
}

async function exportAnalytics() {
    const { data } = await apiGet('series', { panel_id: state.panelId, range: state.range });
    if (!data.length) {
        showToast('error', 'There is no chart data to export for this period.');
        return;
    }
    const header = ['Period start (WITA)', 'Avg voltage (V)', 'Avg current (A)', 'Avg power (W)', 'Peak power (W)', 'Estimated energy (Wh)', 'Readings'];
    const rows = data.map((row) => [formatDateTime(row.t), row.voltage, row.current, row.power, row.peak_power, row.energy_wh, row.samples]);
    downloadCsv(`solaris_${state.panelId}_${state.range}_${Date.now()}.csv`, header, rows);
    showToast('success', 'Chart data exported');
}

/* Test data tools (only rendered when TRACKER_DEV_TOOLS is on) */

function refreshAfterDataChange() {
    state.logPage = 1;
    fetchLatest();
    refreshView(state.view);
}

async function seedDatabase() {
    showToast('info', 'Adding random readings');
    try {
        const json = await apiGet('seed', { panel_id: byId('dev-tools-subsystem').value });
        showToast('success', json.message);
        refreshAfterDataChange();
    } catch (error) {
        showToast('error', `Could not add readings: ${error.message}`);
    }
}

async function resetDatabase() {
    const select = byId('dev-tools-subsystem');
    const label = select.options[select.selectedIndex].text;
    if (!confirm(`Permanently delete every log for: ${label}? This cannot be undone.`)) return;
    try {
        await apiPost('reset', { panel_id: select.value });
        showToast('success', `Logs deleted for: ${label}`);
        refreshAfterDataChange();
    } catch (error) {
        showToast('error', `Could not delete logs: ${error.message}`);
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
    byId('theme-toggle').setAttribute('aria-label', active === 'dark' ? 'Switch to light theme' : 'Switch to dark theme');
    applyChartTheme();
}

function setTheme(theme) {
    localStorage.setItem('theme', theme);
    byId('theme-select').value = theme;
    applyTheme(theme);
}

/* Weather */

function describeWeather(code) {
    if (code === 0) return ['Clear sky', 'light_mode'];
    if (code >= 1 && code <= 2) return ['Partly cloudy', 'partly_cloudy_day'];
    if (code === 3) return ['Overcast', 'cloud'];
    if (code >= 45 && code <= 48) return ['Foggy', 'foggy'];
    if (code >= 51 && code <= 67) return ['Rain', 'rainy'];
    if (code >= 80 && code <= 82) return ['Showers', 'rainy'];
    if (code >= 95) return ['Thunderstorm', 'thunderstorm'];
    return ['Unclassified', 'cloud'];
}

async function loadWeather() {
    try {
        const url = `https://api.open-meteo.com/v1/forecast?latitude=${SITE.lat}&longitude=${SITE.lon}&current=temperature_2m,weather_code`;
        const { current } = await requestJson(url);
        const [description, icon] = describeWeather(current.weather_code);
        setText('weather-desc', description);
        setText('weather-temp', current.temperature_2m);
        setText('weather-icon', icon);
    } catch (error) {
        setText('weather-desc', 'Unavailable');
        setText('weather-temp', '--');
        console.error('Could not load the weather:', error);
    }
}

/* Wiring */

function bindEvents() {
    document.addEventListener('click', (event) => {
        const view = event.target.closest('[data-view]');
        if (view) {
            navigateTo(view.dataset.view);
            return;
        }
        const panel = event.target.closest('[data-panel]');
        if (panel) {
            switchPanel(panel.dataset.panel);
            return;
        }
        const mode = event.target.closest('[data-mode]');
        if (mode) {
            setTrackerMode(mode.dataset.mode);
            return;
        }
        const command = event.target.closest('[data-command]');
        if (command) {
            sendManualCommand(command.dataset.command);
            return;
        }
        const page = event.target.closest('[data-page]');
        if (page) {
            state.logPage = Number(page.dataset.page);
            loadLogs();
            return;
        }
        const detail = event.target.closest('[data-log-index]');
        if (detail) {
            showLogDetail(state.logRows[Number(detail.dataset.logIndex)]);
        }
    });

    byId('log-filter-form').addEventListener('submit', (event) => {
        event.preventDefault();
        applyLogFilter();
    });
    byId('filter-clear').addEventListener('click', clearLogFilter);

    byId('export-logs').addEventListener('click', (event) => runExport(event.currentTarget, exportLogs));
    byId('export-analytics').addEventListener('click', (event) => runExport(event.currentTarget, exportAnalytics));

    byId('analytics-range').addEventListener('change', (event) => {
        state.range = event.target.value;
        loadAnalyticsSeries();
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

function init() {
    initCharts();
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
    setInterval(() => {
        if (document.hidden) return;
        if (state.view === 'dashboard') loadDashboardSeries();
        if (state.view === 'charts') loadAnalyticsSeries();
    }, SERIES_REFRESH_MS);
    setInterval(() => {
        if (!document.hidden && state.view === 'logs') loadLogs({ animate: false, background: true });
    }, LOG_REFRESH_MS);
}

window.addEventListener('DOMContentLoaded', init);
