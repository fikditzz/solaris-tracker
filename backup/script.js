// --- 1. SPA Navigation Logic ---
const pageTitles = {
    'dashboard': 'Main Dashboard',
    'tracker': 'Panel Tracking',
    'charts': 'Charts & Analytics',
    'logs': 'Recent Logs',
    'settings': 'Settings'
};
function navigateTo(viewId, navElement) {
    // Hide all views
    document.querySelectorAll('.page-view').forEach(el => el.classList.remove('active'));
    // Remove active class from all nav links
    document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
    // Show target view
    document.getElementById('view-' + viewId).classList.add('active');
    // Set nav active
    navElement.classList.add('active');
    // Update Title
    document.getElementById('top-title').innerText = pageTitles[viewId];
    // Resize charts if needed due to display:none transitions
    Object.values(chartInstances).forEach(chart => chart.resize());
}
// --- 2. Chart.js Global Config & Initialization ---
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.color = '#64748B';
const chartInstances = {};
function initCharts() {
    // Dashboard Power Line Chart
    const ctxPower = document.getElementById('chartDashPower').getContext('2d');
    chartInstances.dashPower = new Chart(ctxPower, {
        type: 'line',
        data: {
            labels: ['06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', 'Now'],
            datasets: [{
                label: 'Power (W)',
                data: [1000, 2500, 3800, 4300, 4100, 2800, 1200, 4250],
                borderColor: '#EAB308', // yellow
                backgroundColor: 'rgba(234, 179, 8, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                pointBackgroundColor: '#EAB308',
                fill: true
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { borderDash: [4, 4] } }, x: { grid: { display: false } } } }
    });
    // Dashboard V/A Area Chart
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
    // Analytics Canvas: Big V/A Chart (from referensi gambar)
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
    // Analytics Canvas: Bar Chart
    const ctxConsump = document.getElementById('chartAnaConsumption').getContext('2d');
    chartInstances.anaConsump = new Chart(ctxConsump, {
        type: 'bar',
        data: {
            labels: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6'],
            datasets: [{ data: [30, 40, 20, 85, 50, 35], backgroundColor: ['#E2E8F0', '#E2E8F0', '#E2E8F0', '#A16207', '#E2E8F0', '#E2E8F0'], borderRadius: 4 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { display: false }, x: { display: false } } }
    });
    // Analytics Canvas: Battery Area
    const ctxBattery = document.getElementById('chartAnaBattery').getContext('2d');
    chartInstances.anaBattery = new Chart(ctxBattery, {
        type: 'line',
        data: {
            labels: ['1', '2', '3', '4', '5', '6', '7'],
            datasets: [{ data: [20, 30, 25, 60, 55, 84, 80], borderColor: '#166534', backgroundColor: 'rgba(34, 197, 94, 0.2)', borderWidth: 3, fill: true, tension: 0, pointRadius: [0,0,0,0,0,5,0], pointBackgroundColor: '#fff', pointBorderColor: '#166534', pointBorderWidth: 2 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { display: false }, x: { display: false } } }
    });
}
// --- 3. Initial Populate Logs Table ---
const dummyLogs = [
    { time: "2023-10-25 08:15:02", node: "INV-ALPHA-01", v: "48.2", c: "12.5", e: "1,245.8", status: "Optimal" },
    { time: "2023-10-25 08:14:58", node: "INV-ALPHA-02", v: "48.1", c: "12.3", e: "1,242.1", status: "Optimal" },
    { time: "2023-10-25 08:14:15", node: "STR-BETA-04", v: "46.5", c: "18.2", e: "890.4", status: "High Load" },
    { time: "2023-10-25 08:13:50", node: "INV-ALPHA-03", v: "48.3", c: "12.4", e: "1,250.0", status: "Optimal" },
    { time: "2023-10-25 08:12:05", node: "INV-GAMMA-01", v: "0.0", c: "0.0", e: "412.1", status: "Offline" },
    { time: "2023-10-25 08:11:45", node: "STR-BETA-01", v: "48.0", c: "10.1", e: "910.5", status: "Optimal" },
    { time: "2023-10-25 08:10:22", node: "STR-BETA-02", v: "48.1", c: "10.2", e: "912.0", status: "Optimal" }
];
function populateLogs() {
    const tbody = document.getElementById('log-table-body');
    tbody.innerHTML = '';
    dummyLogs.forEach(log => {
        let badgeClass = 'badge-optimal';
        let trStyle = '';
        let vClass = '';
        if(log.status === 'High Load') { badgeClass = 'badge-warning'; trStyle = 'background-color: #FEF9C3;'; vClass="text-warning fw-bold";}
        if(log.status === 'Offline') { badgeClass = 'badge bg-danger bg-opacity-10 text-danger border border-danger-subtle'; trStyle = 'background-color: #FEF2F2;'; vClass="text-danger fw-bold";}
        tbody.innerHTML += `
            <tr style="${trStyle}">
                <td class="text-start ps-4">${log.time}</td>
                <td class="fw-semibold">${log.node}</td>
                <td class="${vClass}">${log.v}</td>
                <td class="${vClass}">${log.c}</td>
                <td>${log.e}</td>
                <td><span class="${badgeClass}"><div style="display:inline-block; width:6px; height:6px; background:currentColor; border-radius:50%; margin-right:4px;"></div>${log.status}</span></td>
                <td class="pe-4"><button class="btn btn-sm btn-light text-muted"><span class="material-symbols-rounded" style="font-size:16px;">more_horiz</span></button></td>
            </tr>
        `;
    });
}
// --- 4. Simulation Engine ---
// State variables
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
    // Color flash
    el.classList.remove('val-up', 'val-down');
    void el.offsetWidth; // trigger reflow
    if(newValue > currentVal) el.classList.add('val-up');
    else if(newValue < currentVal) el.classList.add('val-down');
    setTimeout(() => { el.classList.remove('val-up', 'val-down'); }, 400);
}
function runSimulationTick() {
    // Check if simulation is enabled
    if(!document.getElementById('simMode').checked) return;
    // Random walk logic
    simState.voltage += (Math.random() * 2 - 1);
    if(simState.voltage < 330) simState.voltage = 330; if(simState.voltage > 360) simState.voltage = 360;
    simState.current += (Math.random() * 0.5 - 0.25);
    if(simState.current < 8) simState.current = 8; if(simState.current > 15) simState.current = 15;
    simState.power = Math.round(simState.voltage * simState.current);
    simState.battery += 0.05; // slowly charging
    if(simState.battery > 100) simState.battery = 100;
    simState.azimuth += (Math.random() * 0.2 - 0.05); // slightly moving west
    simState.elevation += (Math.random() * 0.1 - 0.05);
    simState.ldrNW += Math.floor(Math.random() * 10 - 5);
    simState.ldrNE += Math.floor(Math.random() * 10 - 5);
    simState.ldrSW += Math.floor(Math.random() * 10 - 5);
    simState.ldrSE += Math.floor(Math.random() * 10 - 5);
    simState.mot1Load = 2.0 + Math.random() * 1.0;
    simState.mot2Load = 2.5 + Math.random() * 1.5;
    // Update DOM (Dashboard)
    animateValueChange('dash-power', simState.power, 0);
    animateValueChange('dash-voltage', simState.voltage, 1);
    animateValueChange('dash-current', simState.current, 1);
    document.getElementById('dash-battery').innerText = Math.floor(simState.battery);
    document.getElementById('dash-battery-ring').style.background = `conic-gradient(#3B82F6 0% ${simState.battery}%, #E2E8F0 ${simState.battery}% 100%)`;
    animateValueChange('dash-azimuth', simState.azimuth, 1);
    animateValueChange('dash-elevation', simState.elevation, 1);
    document.getElementById('map-az').innerText = simState.azimuth.toFixed(0);
    // Visual Map rotation
    document.getElementById('dash-panel-ui').style.transform = `rotate(${simState.azimuth - 135}deg) scaleY(${Math.max(0.3, simState.elevation/90)})`;
    animateValueChange('dash-ldr-tl', simState.ldrNW, 0);
    animateValueChange('dash-ldr-tr', simState.ldrNE, 0);
    animateValueChange('dash-ldr-bl', simState.ldrSW, 0);
    animateValueChange('dash-ldr-br', simState.ldrSE, 0);
    // Update DOM (Tracker)
    animateValueChange('trk-azimuth', simState.azimuth, 0);
    animateValueChange('trk-elevation', simState.elevation, 0);
    animateValueChange('trk-ldr-nw', simState.ldrNW, 0);
    animateValueChange('trk-ldr-ne', simState.ldrNE, 0);
    animateValueChange('trk-ldr-sw', simState.ldrSW, 0);
    animateValueChange('trk-ldr-se', simState.ldrSE, 0);
    animateValueChange('trk-mot1-load', simState.mot1Load, 1);
    animateValueChange('trk-mot2-load', simState.mot2Load, 1);
    // Highlight highest LDR box (Tracker)
    let maxLdr = Math.max(simState.ldrNW, simState.ldrNE, simState.ldrSW, simState.ldrSE);
    ['nw', 'ne', 'sw', 'se'].forEach(dir => {
        const box = document.getElementById(`box-ldr-${dir}`);
        const val = simState[`ldr${dir.toUpperCase()}`];
        if(val === maxLdr) {
            box.style.background = 'var(--primary)';
            box.style.borderColor = 'var(--primary-hover)';
            box.querySelector('.ldr-label').classList.remove('text-muted');
            box.querySelector('.ldr-label').classList.add('text-dark');
        } else {
            box.style.background = '#FFFFFF';
            box.style.borderColor = 'var(--border-color)';
            box.querySelector('.ldr-label').classList.add('text-muted');
            box.querySelector('.ldr-label').classList.remove('text-dark');
        }
    });
    // Update Charts (Live feel)
    if(chartInstances.dashPower) {
        let data = chartInstances.dashPower.data.datasets[0].data;
        data.shift(); data.push(simState.power);
        chartInstances.dashPower.update('none'); // update without full animation
    }
    if(chartInstances.dashVA) {
        let dataV = chartInstances.dashVA.data.datasets[0].data;
        let dataA = chartInstances.dashVA.data.datasets[1].data;
        dataV.shift(); dataV.push(simState.voltage);
        dataA.shift(); dataA.push(simState.current);
        chartInstances.dashVA.update('none');
    }
}
// --- 5. Boot System ---
window.onload = () => {
    initCharts();
    populateLogs();
    // Clock
    setInterval(() => {
        const now = new Date();
        document.getElementById('dash-time').innerText = now.toISOString().substr(11, 8);
    }, 1000);
    // Simulation Loop (Every 2 seconds)
    setInterval(runSimulationTick, 2000);
};