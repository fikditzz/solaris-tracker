window.currentPanelId = localStorage.getItem('panelId') || 'panel1';

function switchPanel(panelId) {
    window.currentPanelId = panelId;
    localStorage.setItem('panelId', panelId);
    document.querySelectorAll('.page-view.active').forEach(el => {
        el.classList.remove('panel-switch-anim');
        void el.offsetWidth;
        el.classList.add('panel-switch-anim');
    });
    
    let titleEl = document.getElementById('tracker-panel-title');
    let azMapCol = document.getElementById('azimuth-map-col');
    let azDataCard = document.getElementById('azimuth-data-card');
    let btnLeft = document.getElementById('btn-manual-left');
    let btnRight = document.getElementById('btn-manual-right');
    let azMotor = document.getElementById('azimuth-motor-health');
    let elMapCol = document.getElementById('elevation-map-col');
    let elDataCard = document.getElementById('elevation-data-card');
    let dashTrackType = document.getElementById('dash-tracking-type');
    let dashAzMapCol = document.getElementById('dash-azimuth-map-col');
    let dashElMapCol = document.getElementById('dash-elevation-map-col');
    let dashAzValCol = document.getElementById('dash-azimuth-val-col');
    let dashElValCol = document.getElementById('dash-elevation-val-col');

    if(panelId === 'panel2') {
        if(titleEl) titleEl.innerText = "Single-Axis Tracker Array";
        if(dashTrackType) dashTrackType.innerText = "Single-Axis Tracking Active";
        if(document.getElementById('panelDropdownText')) document.getElementById('panelDropdownText').innerText = "Array Beta (Single Axis)";
        if(document.getElementById('panelSelector')) document.getElementById('panelSelector').value = 'panel2';
        
        if(azMapCol) azMapCol.style.setProperty('display', 'none', 'important');
        if(azDataCard) azDataCard.style.setProperty('display', 'none', 'important');
        if(btnLeft) btnLeft.style.visibility = 'hidden';
        if(btnRight) btnRight.style.visibility = 'hidden';
        if(azMotor) azMotor.style.setProperty('display', 'none', 'important');
        
        if(elMapCol) { elMapCol.classList.remove('col-6'); elMapCol.classList.add('col-12'); }
        if(elDataCard) { elDataCard.classList.remove('col-md-6'); elDataCard.classList.add('col-md-12'); }
        
        if(dashAzMapCol) dashAzMapCol.style.setProperty('display', 'none', 'important');
        if(dashAzValCol) dashAzValCol.style.setProperty('display', 'none', 'important');
        if(dashElMapCol) { dashElMapCol.classList.remove('col-6'); dashElMapCol.classList.add('col-12'); }
        if(dashElValCol) { dashElValCol.classList.remove('col-6'); dashElValCol.classList.add('col-12'); }
    } else {
        if(titleEl) titleEl.innerText = "Dual-Axis Tracker Array";
        if(dashTrackType) dashTrackType.innerText = "Dual-Axis Tracking Active";
        if(document.getElementById('panelDropdownText')) document.getElementById('panelDropdownText').innerText = "Array Alpha (Dual Axis)";
        if(document.getElementById('panelSelector')) document.getElementById('panelSelector').value = 'panel1';
        
        if(azMapCol) azMapCol.style.display = '';
        if(azDataCard) azDataCard.style.display = '';
        if(btnLeft) btnLeft.style.visibility = 'visible';
        if(btnRight) btnRight.style.visibility = 'visible';
        if(azMotor) azMotor.style.display = '';
        
        if(elMapCol) { elMapCol.classList.remove('col-12'); elMapCol.classList.add('col-6'); }
        if(elDataCard) { elDataCard.classList.remove('col-md-12'); elDataCard.classList.add('col-md-6'); }
        
        if(dashAzMapCol) { dashAzMapCol.style.display = ''; dashAzMapCol.classList.remove('col-12'); dashAzMapCol.classList.add('col-6'); }
        if(dashAzValCol) { dashAzValCol.style.display = ''; dashAzValCol.classList.remove('col-12'); dashAzValCol.classList.add('col-6'); }
        if(dashElMapCol) { dashElMapCol.style.display = ''; dashElMapCol.classList.remove('col-12'); dashElMapCol.classList.add('col-6'); }
        if(dashElValCol) { dashElValCol.style.display = ''; dashElValCol.classList.remove('col-12'); dashElValCol.classList.add('col-6'); }
    }

    populateLogs();
    fetchApiData();
    showToast('info', 'Switched to ' + (panelId === 'panel1' ? 'Array Alpha (Dual Axis)' : 'Array Beta (Single Axis)'));
}
const pageTitles = {
    'dashboard': 'Main Dashboard',
    'tracker': 'Panel Tracking',
    'charts': 'Charts & Analytics',
    'logs': 'Recent Logs',
    'settings': 'Settings'
};
function navigateTo(viewId, navElement) {
    document.querySelectorAll('.page-view').forEach(el => el.classList.remove('active', 'panel-switch-anim'));
    document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
    const targetView = document.getElementById('view-' + viewId);
    targetView.classList.add('active');
    void targetView.offsetWidth;
    targetView.classList.add('panel-switch-anim');
    navElement.classList.add('active');
    document.getElementById('top-title').innerText = pageTitles[viewId];
    Object.values(chartInstances).forEach(chart => chart.resize());
}
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.color = '#64748B';
const chartInstances = {};
function initCharts() {
    const ctxPower = document.getElementById('chartDashPower').getContext('2d');
    chartInstances.dashPower = new Chart(ctxPower, {
        type: 'line',
        data: {
            labels: ['06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', 'Now'],
            datasets: [{
                label: 'Power (W)',
                data: [1000, 2500, 3800, 4300, 4100, 2800, 1200, 4250],
                borderColor: '#EAB308',
                backgroundColor: 'rgba(234, 179, 8, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                pointBackgroundColor: '#EAB308',
                fill: true
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { borderDash: [4, 4] } }, x: { grid: { display: false } } } }
    });
    const ctxVA = document.getElementById('chartDashVA').getContext('2d');
    chartInstances.dashVA = new Chart(ctxVA, {
        type: 'line',
        data: {
            labels: ['06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', 'Now'],
            datasets: [
                { label: 'Voltage (V)', data: [310, 320, 340, 345, 343, 330, 315, 345], borderColor: '#3B82F6', backgroundColor: 'rgba(59, 130, 246, 0.1)', borderWidth: 2, fill: true, tension: 0.4, pointRadius: 0 },
                { label: 'Current (A)', data: [2, 5, 10, 12.5, 11, 8, 3, 12.3], borderColor: '#22C55E', backgroundColor: 'rgba(34, 197, 94, 0.1)', borderWidth: 2, fill: true, tension: 0.4, pointRadius: 0 }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true } } }, scales: { y: { display: false }, x: { grid: { display: false } } } }
    });
    const ctxAnaVA = document.getElementById('chartAnaVA').getContext('2d');
    chartInstances.anaVA = new Chart(ctxAnaVA, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [
                { label: 'Voltage Output', data: [70, 480, 380, 250, 400, 500, 520], borderColor: '#854D0E', borderWidth: 3, tension: 0.5, pointRadius: 0 },
                { label: 'Current Flow', data: [ -40, 150, 80, 100, 50, 200, 180], borderColor: '#475569', borderWidth: 2, tension: 0.5, pointRadius: 0 }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { grid: { borderDash: [4, 4] } }, x: { grid: { display: false } } } }
    });
    const ctxConsump = document.getElementById('chartAnaConsumption').getContext('2d');
    chartInstances.anaConsump = new Chart(ctxConsump, {
        type: 'bar',
        data: {
            labels: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6'],
            datasets: [{ data: [30, 40, 20, 85, 50, 35], backgroundColor: ['#E2E8F0', '#E2E8F0', '#E2E8F0', '#A16207', '#E2E8F0', '#E2E8F0'], borderRadius: 4 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { display: false }, x: { display: false } } }
    });
}
function renderLogs(data) {
    const tbody = document.getElementById('log-table-body');
    if(!tbody) return;
    tbody.innerHTML = '';
    data.forEach(log => {
        let badgeClass = 'badge-optimal';
        let trStyle = '';
        let vClass = '';
        let status = log.power_output > 4000 ? 'High Load' : (log.power_output == 0 ? 'Offline' : 'Optimal');
        
        if(status === 'High Load') { badgeClass = 'badge-warning'; trStyle = 'background-color: #FEF9C3;'; vClass="text-warning fw-bold";}
        if(status === 'Offline') { badgeClass = 'badge bg-danger bg-opacity-10 text-danger border border-danger-subtle'; trStyle = 'background-color: #FEF2F2;'; vClass="text-danger fw-bold";}
        let dateObj = new Date(log.created_at);
        let timeStr = dateObj.toLocaleString('en-CA', { timeZone: 'Asia/Makassar', hour12: false }).replace(',', '');
        
        tbody.innerHTML += `
            <tr style="${trStyle}">
                <td class="text-start ps-4">${timeStr}</td>
                <td class="fw-semibold">Node-${log.id}</td>
                <td class="${vClass}">${log.voltage}</td>
                <td class="${vClass}">${log.current}</td>
                <td>${log.power_output}</td>
                <td><span class="${badgeClass}"><div style="display:inline-block; width:6px; height:6px; background:currentColor; border-radius:50%; margin-right:4px;"></div>${status}</span></td>
                <td class="pe-4"><button class="btn btn-sm btn-light text-muted"><span class="material-symbols-rounded" style="font-size:16px;">more_horiz</span></button></td>
            </tr>
        `;
    });
}

async function populateLogs() {
    try {
        const response = await fetch('/api/tracker/logs?panel_id=' + window.currentPanelId);
        const json = await response.json();
        if(json.success && json.data) {
            window.logDataCache = json.data;
            renderLogs(json.data);
            updateCharts(json.data);
        }
    } catch(err) {
        console.error(err);
    }
}

function updateCharts(data) {
    if(!data || data.length === 0) return;
    let chartData = [...data].reverse().slice(-15);
    
    let labels = chartData.map(log => {
        let d = new Date(log.created_at);
        return `${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}`;
    });
    
    let voltage = chartData.map(log => log.voltage);
    let current = chartData.map(log => log.current);
    let power = chartData.map(log => log.power_output);
    
    if(chartInstances.anaVA) {
        chartInstances.anaVA.data.labels = labels;
        chartInstances.anaVA.data.datasets[0].data = voltage;
        chartInstances.anaVA.data.datasets[1].data = current;
        chartInstances.anaVA.update();
    }
    
    if(chartInstances.anaConsump) {
        chartInstances.anaConsump.data.labels = labels;
        chartInstances.anaConsump.data.datasets[0].data = power;
        let bgColors = Array(labels.length).fill('#E2E8F0');
        if(bgColors.length > 0) bgColors[bgColors.length - 1] = '#A16207';
        chartInstances.anaConsump.data.datasets[0].backgroundColor = bgColors;
        chartInstances.anaConsump.update();
    }
}
let simState = {
    voltage: 345.2, current: 12.3, power: 4250, battery: 85,
    azimuth: 180.0, elevation: 45.0,
    ldrNW: 885, ldrNE: 912, ldrSW: 860, ldrSE: 874,
    mot1Load: 2.4, mot2Load: 3.1
};
function animateValueChange(id, newValue, decimals = 1) {
    const el = document.getElementById(id);
    if(!el) return;
    const currentVal = parseFloat(el.innerText.replace(/,/g, ''));
    el.innerText = newValue.toLocaleString('en-US', {minimumFractionDigits: decimals, maximumFractionDigits: decimals});
    el.classList.remove('val-up', 'val-down');
    void el.offsetWidth;
    if(newValue > currentVal) el.classList.add('val-up');
    else if(newValue < currentVal) el.classList.add('val-down');
    setTimeout(() => { el.classList.remove('val-up', 'val-down'); }, 400);
}
async function fetchApiData() {
    try {
        const response = await fetch('/api/tracker/latest?panel_id=' + window.currentPanelId);
        const json = await response.json();
        
        if (json.success && json.data) {
            const data = json.data;
            
            simState.voltage = data.voltage;
            simState.current = data.current;
            simState.power = data.power_output;
            simState.battery = data.battery_percentage;
            simState.azimuth = data.azimuth;
            simState.elevation = data.elevation;
            simState.ldrNW = data.ldr_nw || simState.ldrNW;
            simState.ldrNE = data.ldr_ne || simState.ldrNE;
            simState.ldrSW = data.ldr_sw || simState.ldrSW;
            simState.ldrSE = data.ldr_se || simState.ldrSE;
            simState.mot1Load = data.motor1_load || simState.mot1Load;
            simState.mot2Load = data.motor2_load || simState.mot2Load;
            let dateObj = new Date(data.created_at);
            if(document.getElementById('dash-time')) {
                document.getElementById('dash-time').innerText = dateObj.toLocaleTimeString('en-GB', { timeZone: 'Asia/Makassar' });
            }
            if(document.getElementById('dash-date')) {
                document.getElementById('dash-date').innerText = dateObj.toLocaleDateString('en-US', { timeZone: 'Asia/Makassar', weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
            }

            if(data.tracking_mode) {
                if(document.getElementById('dash-mode')) document.getElementById('dash-mode').innerText = data.tracking_mode;
                let btnAuto = document.getElementById('btn-trk-auto');
                let btnManual = document.getElementById('btn-trk-manual');
                let dPad = document.getElementById('d-pad-container');
                if(btnAuto && btnManual) {
                    if(data.tracking_mode === 'Automatic') {
                        btnAuto.className = "btn btn-warning rounded-pill flex-grow-1 fw-semibold transition-all";
                        btnManual.className = "btn text-muted rounded-pill flex-grow-1 fw-semibold transition-all";
                        if(dPad) { dPad.style.opacity = '0.5'; dPad.style.pointerEvents = 'none'; }
                    } else if(data.tracking_mode === 'Manual') {
                        btnAuto.className = "btn text-muted rounded-pill flex-grow-1 fw-semibold transition-all";
                        btnManual.className = "btn btn-warning rounded-pill flex-grow-1 fw-semibold transition-all";
                        if(dPad) { dPad.style.opacity = '1'; dPad.style.pointerEvents = 'auto'; }
                    }
                }
            }
            if(document.getElementById('dash-mode') && data.tracking_mode) {
                document.getElementById('dash-mode').innerText = data.tracking_mode;
            }
            animateValueChange('dash-power', simState.power, 0);
            animateValueChange('dash-voltage', simState.voltage, 1);
            animateValueChange('dash-current', simState.current, 1);
            animateValueChange('dash-azimuth', simState.azimuth, 1);
            animateValueChange('dash-elevation', simState.elevation, 1);
            document.getElementById('map-az').innerText = simState.azimuth.toFixed(0);
            if(document.getElementById('dash-azimuth-ptr')) document.getElementById('dash-azimuth-ptr').style.transform = `rotate(${simState.azimuth}deg)`;
            if(document.getElementById('dash-elevation-ptr')) document.getElementById('dash-elevation-ptr').style.transform = `rotate(${simState.elevation - 90}deg)`;
            if(document.getElementById('map-el')) document.getElementById('map-el').innerText = simState.elevation.toFixed(0);
            
            animateValueChange('dash-ldr-tl', simState.ldrNW, 0);
            animateValueChange('dash-ldr-tr', simState.ldrNE, 0);
            animateValueChange('dash-ldr-bl', simState.ldrSW, 0);
            animateValueChange('dash-ldr-br', simState.ldrSE, 0);
            if(document.getElementById('trk-azimuth-ptr')) document.getElementById('trk-azimuth-ptr').style.transform = `rotate(${simState.azimuth}deg)`;
            if(document.getElementById('trk-elevation-ptr')) document.getElementById('trk-elevation-ptr').style.transform = `rotate(${simState.elevation - 90}deg)`;
            
            animateValueChange('trk-azimuth', simState.azimuth, 0);
            animateValueChange('trk-elevation', simState.elevation, 0);
            animateValueChange('trk-ldr-nw', simState.ldrNW, 0);
            animateValueChange('trk-ldr-ne', simState.ldrNE, 0);
            animateValueChange('trk-ldr-sw', simState.ldrSW, 0);
            animateValueChange('trk-ldr-se', simState.ldrSE, 0);
            animateValueChange('trk-mot1-load', simState.mot1Load, 1);
            animateValueChange('trk-mot2-load', simState.mot2Load, 1);
            let maxLdr = Math.max(simState.ldrNW, simState.ldrNE, simState.ldrSW, simState.ldrSE);
            ['nw', 'ne', 'sw', 'se'].forEach(dir => {
                ['dash-box-ldr', 'trk-box-ldr'].forEach(prefix => {
                    const box = document.getElementById(`${prefix}-${dir}`);
                    if(box) {
                        const val = simState[`ldr${dir.toUpperCase()}`];
                        if(val === maxLdr) {
                            box.classList.remove('bg-primary', 'bg-opacity-10', 'border-primary-subtle');
                            box.classList.add('bg-warning', 'bg-opacity-25', 'border-warning-subtle');
                            box.querySelector('.ldr-label').classList.add('text-dark');
                        } else {
                            box.classList.remove('bg-warning', 'bg-opacity-25', 'border-warning-subtle');
                            box.classList.add('bg-primary', 'bg-opacity-10', 'border-primary-subtle');
                            box.querySelector('.ldr-label').classList.remove('text-dark');
                        }
                    }
                });
            });
            if(chartInstances.dashPower) {
                let pData = chartInstances.dashPower.data.datasets[0].data;
                pData.shift(); pData.push(simState.power);
                chartInstances.dashPower.update('none'); 
            }
            if(chartInstances.dashVA) {
                let dataV = chartInstances.dashVA.data.datasets[0].data;
                let dataA = chartInstances.dashVA.data.datasets[1].data;
                dataV.shift(); dataV.push(simState.voltage);
                dataA.shift(); dataA.push(simState.current);
                chartInstances.dashVA.update('none');
            }
        }
    } catch (error) {
        console.error("Gagal mengambil data dari API: ", error);
    }
}
async function setTrackerMode(modeStr, forcePanel = null) {
    let target = forcePanel || window.currentPanelId;
    try {
        await fetch('/api/tracker/mode', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ mode: modeStr, panel_id: target })
        });
        fetchApiData();
    } catch(err) { console.error(err); }
}

async function sendManualCommand(dir) {
    try {
        await fetch('/api/tracker/command', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ command: dir, panel_id: window.currentPanelId })
        });
        console.log("Command sent:", dir);
    } catch(err) { console.error(err); }
}

window.onload = () => {
    // Initialize theme
    let savedTheme = localStorage.getItem('theme') || 'light';
    applyTheme(savedTheme);
    let themeSelect = document.getElementById('theme-select');
    if(themeSelect) themeSelect.value = savedTheme;

    if(document.getElementById('btn-mode-auto')) {
        document.getElementById('btn-mode-auto').onclick = () => setTrackerMode('Automatic', 'all');
    }
    if(document.getElementById('btn-mode-stop')) {
        document.getElementById('btn-mode-stop').onclick = () => setTrackerMode('Manual', 'all');
    }
    if(document.getElementById('btn-mode-reset')) {
        document.getElementById('btn-mode-reset').onclick = () => setTrackerMode('Reset', 'all');
    }
    if (document.getElementById('panelSelector')) {
        document.getElementById('panelSelector').value = window.currentPanelId;
    }
    switchPanel(window.currentPanelId);

    initCharts();
    populateLogs();
    
    if(typeof flatpickr !== 'undefined') {
        flatpickr("#filter-date-start", { dateFormat: "m/d/Y" });
        flatpickr("#filter-date-end", { dateFormat: "m/d/Y" });
    }
    
    if(typeof TomSelect !== 'undefined') {
        new TomSelect("#filter-status", { create: false, controlInput: null });
        new TomSelect("#filter-subsystem", { create: false, controlInput: null });
    }
    if(document.getElementById('btn-mode-auto')) {
        document.getElementById('btn-mode-auto').onclick = () => setTrackerMode('Automatic');
    }
    if(document.getElementById('btn-mode-reset')) {
        document.getElementById('btn-mode-reset').onclick = () => setTrackerMode('Reset');
    }
    if(document.getElementById('btn-mode-stop')) {
        document.getElementById('btn-mode-stop').onclick = () => setTrackerMode('Stop');
    }
    setInterval(fetchApiData, 2000);
    setInterval(populateLogs, 10000);
};

function showToast(type, message) {
    const container = document.getElementById('toast-container');
    if(!container) return;
    
    const toast = document.createElement('div');
    toast.className = 'toast-custom';
    
    let icon = 'info';
    let color = '#3B82F6';
    if(type === 'success') { icon = 'check_circle'; color = '#22C55E'; }
    if(type === 'warning') { icon = 'warning'; color = '#F59E0B'; }
    if(type === 'error') { icon = 'error'; color = '#EF4444'; }
    
    toast.innerHTML = `<span class="material-symbols-rounded" style="color: ${color}">${icon}</span> <span>${message}</span>`;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function applyTheme(theme) {
    if (theme === 'auto') {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.setAttribute('data-bs-theme', 'dark');
        } else {
            document.documentElement.setAttribute('data-bs-theme', 'light');
        }
    } else {
        document.documentElement.setAttribute('data-bs-theme', theme);
    }
}

function changeTheme(theme) {
    localStorage.setItem('theme', theme);
    applyTheme(theme);
    showToast('info', 'Theme changed to ' + theme);
}

function exportCSV(type) {
    if(!window.logDataCache || window.logDataCache.length === 0) {
        showToast('error', 'No data available to export.');
        return;
    }
    
    let csvContent = 'data:text/csv;charset=utf-8,';
    csvContent += 'Timestamp,Azimuth,Elevation,LDR NW,LDR NE,LDR SW,LDR SE,Mode,Command\n';
    
    window.logDataCache.forEach(row => {
        let ts = row.created_at ? new Date(row.created_at).toLocaleString('en-US', { timeZone: 'Asia/Makassar' }) : 'N/A';
        let rowStr = `${ts},${row.azimuth},${row.elevation},${row.ldr_nw},${row.ldr_ne},${row.ldr_sw},${row.ldr_se},${row.tracking_mode || 'Auto'},${row.manual_command || 'None'}`;
        csvContent += rowStr + '\n';
    });
    
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `solaris_data_${type}_${new Date().getTime()}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showToast('success', 'Data exported successfully!');
}

function filterLogs() {
    if(!window.logDataCache) return;
    
    const startDate = document.getElementById('filter-date-start').value;
    const endDate = document.getElementById('filter-date-end').value;
    const statusFilter = document.getElementById('filter-status').value;
    
    let filtered = window.logDataCache.filter(log => {
        let status = log.power_output > 4000 ? 'High Load' : (log.power_output == 0 ? 'Offline' : 'Optimal');
        let dateObj = new Date(log.created_at);
        
        let matchStatus = true;
        if(statusFilter !== 'All' && statusFilter !== 'All Statuses') {
            matchStatus = (status === statusFilter);
        }
        
        let matchDate = true;
        if(startDate) {
            let sDate = new Date(startDate);
            if(dateObj < sDate) matchDate = false;
        }
        if(endDate) {
            let eDate = new Date(endDate);
            eDate.setHours(23, 59, 59);
            if(dateObj > eDate) matchDate = false;
        }
        
        return matchStatus && matchDate;
    });
    
    renderLogs(filtered);
    showToast('success', `Found ${filtered.length} records.`);
}

function seedDatabase() {
    const subsystem = document.getElementById('dev-tools-subsystem').value;
    showToast('info', 'Generating random historical records...');
    fetch('/api/tracker/seed?panel_id=' + subsystem)
        .then(res => res.json())
        .then(data => {
            showToast('success', data.message || 'Data generated successfully!');
            populateLogs();
        })
        .catch(err => {
            showToast('error', 'Failed to generate data.');
        });
}

async function resetDatabase() {
    const subsystem = document.getElementById('dev-tools-subsystem').value;
    if(!confirm("Are you sure you want to completely wipe the database for " + subsystem + "? This cannot be undone!")) return;
    
    try {
        const response = await fetch('/api/tracker/reset', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ panel_id: subsystem })
        });
        const json = await response.json();
        if(json.success) {
            showToast('success', 'Database wiped successfully!');
            populateLogs();
            fetchApiData();
        } else {
            showToast('danger', 'Failed to wipe database.');
        }
    } catch(err) {
        console.error(err);
        showToast('danger', 'Error connecting to server.');
    }
}

// --- WEATHER WIDGET LOGIC ---
function initWeatherWidget() {
    // Koordinat permanen lokasi ESP32 / Solar Tracker
    const lat = -5.2150509;
    const lon = 119.5793433;
    getWeatherFromMeteo(lat, lon);
}

function getWeatherFromMeteo(lat, lon) {
    const url = `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current=temperature_2m,weather_code`;
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            if(!data || !data.current) return;
            const current = data.current;
            const temp = current.temperature_2m;
            const code = current.weather_code;
            
            let desc = 'Clear Sky';
            let icon = 'light_mode';
            
            if(code >= 1 && code <= 3) { desc = 'Partly Cloudy'; icon = 'partly_cloudy_day'; }
            else if(code >= 45 && code <= 48) { desc = 'Foggy'; icon = 'foggy'; }
            else if(code >= 51 && code <= 67) { desc = 'Rain'; icon = 'rainy'; }
            else if(code >= 80 && code <= 82) { desc = 'Showers'; icon = 'rainy'; }
            else if(code >= 95) { desc = 'Thunderstorm'; icon = 'thunderstorm'; }
            
            const wDesc = document.getElementById('weather-desc');
            const wIcon = document.getElementById('weather-icon');
            
            if(wDesc) {
                wDesc.innerHTML = `${desc} <span id="weather-temp" class="fs-5 text-muted ms-1">${temp}°C</span>`;
            }
            if(wIcon) {
                wIcon.innerText = icon;
                if(icon === 'rainy' || icon === 'thunderstorm') {
                    wIcon.classList.remove('text-warning');
                    wIcon.classList.add('text-info');
                } else {
                    wIcon.classList.remove('text-info');
                    wIcon.classList.add('text-warning');
                }
            }
        })
        .catch(err => console.error('Weather API Error:', err));
}

document.addEventListener('DOMContentLoaded', () => {
    initWeatherWidget();
    // Refresh weather every 30 minutes
    setInterval(initWeatherWidget, 30 * 60 * 1000);
});