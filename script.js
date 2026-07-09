// Initialize Chart.js
document.addEventListener('DOMContentLoaded', () => {

    // Authentication is handled server-side by auth_check.php.

    const trendsCanvas = document.getElementById('trendsChart');
    const ctx = trendsCanvas ? trendsCanvas.getContext('2d') : null;

    // Gradient for the line chart
    const gradientFill = ctx ? ctx.createLinearGradient(0, 0, 0, 300) : null;
    if (gradientFill) {
        gradientFill.addColorStop(0, 'rgba(0, 229, 255, 0.4)');
        gradientFill.addColorStop(1, 'rgba(0, 229, 255, 0.0)');
    }

    const gradientFill2 = ctx ? ctx.createLinearGradient(0, 0, 0, 300) : null;
    if (gradientFill2) {
        gradientFill2.addColorStop(0, 'rgba(139, 92, 246, 0.4)');
        gradientFill2.addColorStop(1, 'rgba(139, 92, 246, 0.0)');
    }

    Chart.defaults.color = '#94a3b8';
    Chart.defaults.font.family = "'Outfit', sans-serif";

    const LIVE_CHART_MAX_POINTS = 36;

    let lastRiskNotificationKey = null;
    let audioNotificationUnlocked = false;

    function normalizeNotificationRisk(risk) {
        const value = String(risk || '').toLowerCase().trim();

        if (value.includes('critical') || value.includes('danger') || value.includes('high')) {
            return 'Critical Risk';
        }

        if (value.includes('moderate') || value.includes('warning') || value.includes('caution')) {
            return 'Moderate Risk';
        }

        if (value.includes('low') || value.includes('safe') || value.includes('normal')) {
            return 'Low Risk';
        }

        return '';
    }

    function unlockAudioNotification() {
        audioNotificationUnlocked = true;
    }

    document.addEventListener('click', unlockAudioNotification, { once: true });
    document.addEventListener('keydown', unlockAudioNotification, { once: true });

    function playRiskAlertSound(risk) {
        const finalRisk = normalizeNotificationRisk(risk);

        if (!audioNotificationUnlocked) {
            return;
        }

        try {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;

            if (!AudioContextClass) {
                return;
            }

            const audioCtx = new AudioContextClass();
            const beepCount = finalRisk === 'Critical Risk' ? 3 : 2;
            const frequency = finalRisk === 'Critical Risk' ? 880 : 660;

            for (let i = 0; i < beepCount; i++) {
                const oscillator = audioCtx.createOscillator();
                const gainNode = audioCtx.createGain();

                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(frequency, audioCtx.currentTime + i * 0.35);

                gainNode.gain.setValueAtTime(0.18, audioCtx.currentTime + i * 0.35);
                gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + i * 0.35 + 0.18);

                oscillator.connect(gainNode);
                gainNode.connect(audioCtx.destination);

                oscillator.start(audioCtx.currentTime + i * 0.35);
                oscillator.stop(audioCtx.currentTime + i * 0.35 + 0.18);
            }
        } catch (error) {
            console.warn('Audio alert could not be played:', error);
        }
    }

    function showRiskNotification(risk, row = {}) {
        const finalRisk = normalizeNotificationRisk(risk);

        if (finalRisk !== 'Moderate Risk' && finalRisk !== 'Critical Risk') {
            return;
        }

        const readingKey = row.id || row.reading_time || row.updated_at || `${finalRisk}-${row.ph}-${row.turbidity}-${row.tds}`;

        if (lastRiskNotificationKey === readingKey) {
            return;
        }

        lastRiskNotificationKey = readingKey;

        const container = document.getElementById('risk-notification-container');

        if (!container) {
            return;
        }

        const typeClass = finalRisk === 'Critical Risk' ? 'critical' : 'moderate';

        const title = finalRisk === 'Critical Risk'
            ? 'Critical Water Quality Alert'
            : 'Water Quality Caution';

        const message = finalRisk === 'Critical Risk'
            ? 'A critical risk reading was detected. Audio alert activated. Avoid drinking the water and perform re-sampling after flushing.'
            : 'A moderate risk reading was detected. Audio alert activated. Re-test the water and continue monitoring.';

        const timeText = row.display_time || row.reading_time || row.updated_at || row.timestamp || 'Latest saved reading';

        const notification = document.createElement('div');
        notification.className = `risk-notification ${typeClass}`;

        notification.innerHTML = `
            <div class="risk-notification-header">
                <div class="risk-notification-title">${title}</div>
                <button class="risk-notification-close" type="button" aria-label="Close notification">×</button>
            </div>
            <div class="risk-notification-message">${message}</div>
            <div class="risk-notification-time">${timeText}</div>
        `;

        notification.querySelector('.risk-notification-close')?.addEventListener('click', () => {
            notification.remove();
        });

        container.appendChild(notification);
        playRiskAlertSound(finalRisk);

        setTimeout(() => {
            notification.remove();
        }, 12000);
    }

    const gradientFill3 = ctx ? ctx.createLinearGradient(0, 0, 0, 300) : null;
    if (gradientFill3) {
        gradientFill3.addColorStop(0, 'rgba(245, 158, 11, 0.35)');
        gradientFill3.addColorStop(1, 'rgba(245, 158, 11, 0.0)');
    }

    const gradientFill4 = ctx ? ctx.createLinearGradient(0, 0, 0, 300) : null;
    if (gradientFill4) {
        gradientFill4.addColorStop(0, 'rgba(52, 211, 153, 0.35)');
        gradientFill4.addColorStop(1, 'rgba(52, 211, 153, 0.0)');
    }

    let chartTimeLabels = [];
    let tempSeries = [];
    let turbSeries = [];
    let tdsSeries = [];
    let phSeries = [];

    const trendsChart = ctx ? new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartTimeLabels,
            datasets: [
                {
                    label: 'Temperature (°C)',
                    data: tempSeries,
                    borderColor: '#f59e0b',
                    backgroundColor: gradientFill3,
                    borderWidth: 2,
                    tension: 0.35,
                    fill: true,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    yAxisID: 'yTemp'
                },
                {
                    label: 'Turbidity (NTU)',
                    data: turbSeries,
                    borderColor: '#00e5ff',
                    backgroundColor: gradientFill,
                    borderWidth: 2,
                    tension: 0.35,
                    fill: true,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    yAxisID: 'yTurb'
                },
                {
                    label: 'TDS (ppm)',
                    data: tdsSeries,
                    borderColor: '#34d399',
                    backgroundColor: gradientFill4,
                    borderWidth: 2,
                    tension: 0.35,
                    fill: true,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    yAxisID: 'yTds'
                },
                {
                    label: 'pH',
                    data: phSeries,
                    borderColor: '#8b5cf6',
                    backgroundColor: gradientFill2,
                    borderWidth: 2,
                    tension: 0.35,
                    fill: true,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    yAxisID: 'yPh'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 0 },
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 14,
                        font: { size: 11, weight: '500' }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 22, 36, 0.9)',
                    titleColor: '#e2e8f0',
                    bodyColor: '#e2e8f0',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                    padding: 12,
                    boxPadding: 6,
                    usePointStyle: true
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)', drawBorder: false },
                    ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 8 }
                },
                yTemp: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { display: true, text: '°C', color: '#94a3b8', font: { size: 11 } },
                    grid: { color: 'rgba(255, 255, 255, 0.05)', drawBorder: false },
                    suggestedMin: 10,
                    suggestedMax: 35
                },
                yTds: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    offset: true,
                    title: { display: true, text: 'ppm', color: '#94a3b8', font: { size: 11 } },
                    grid: { drawOnChartArea: false },
                    suggestedMin: 0,
                    suggestedMax: 500
                },
                yTurb: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: { display: true, text: 'NTU', color: '#94a3b8', font: { size: 11 } },
                    grid: { drawOnChartArea: false },
                    suggestedMin: 0,
                    suggestedMax: 5
                },
                yPh: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    offset: true,
                    title: { display: true, text: 'pH', color: '#94a3b8', font: { size: 11 } },
                    grid: { drawOnChartArea: false },
                    suggestedMin: 0,
                    suggestedMax: 14
                }
            }
        }
    }) : null;

    function pushLiveChartSample(sample) {
        const t = sample.time instanceof Date ? sample.time : new Date();
        const label = t.toLocaleTimeString(undefined, {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });

        chartTimeLabels.push(label);
        tempSeries.push(sample.temperature ?? null);
        turbSeries.push(sample.turbidity ?? null);
        tdsSeries.push(sample.tds ?? null);
        phSeries.push(sample.ph ?? null);

        while (chartTimeLabels.length > LIVE_CHART_MAX_POINTS) {
            chartTimeLabels.shift();
            tempSeries.shift();
            turbSeries.shift();
            tdsSeries.shift();
            phSeries.shift();
        }

        if (trendsChart) trendsChart.update('none');
    }

    // ==========================================
    // THEME TOGGLE INSIDE ACCOUNT MENU
    // ==========================================
    const themeBtn = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');
    const themeMenuLabel = document.getElementById('theme-menu-label');

    function applyTheme(theme) {
        if (theme === 'light') {
            document.body.setAttribute('data-theme', 'light');
            if (themeIcon) themeIcon.className = 'ph ph-moon';
            if (themeMenuLabel) themeMenuLabel.textContent = 'Dark Mode';
        } else {
            document.body.removeAttribute('data-theme');
            if (themeIcon) themeIcon.className = 'ph ph-sun';
            if (themeMenuLabel) themeMenuLabel.textContent = 'Light Mode';
        }

        localStorage.setItem('theme', theme);
        updateChartTheme(theme);
    }

    const currentTheme = localStorage.getItem('theme') || 'dark';
    applyTheme(currentTheme === 'light' ? 'light' : 'dark');

    if (themeBtn) {
        themeBtn.addEventListener('click', (event) => {
            event.preventDefault();

            const nextTheme = document.body.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
            applyTheme(nextTheme);
            closeAccountMenu();
        });
    }

    function updateChartTheme(theme) {
        if (!trendsChart) return;

        const yGrids = ['yTemp', 'yTds', 'yTurb', 'yPh'];

        if (theme === 'light') {
            Chart.defaults.color = '#64748b';
            trendsChart.options.plugins.tooltip.backgroundColor = 'rgba(255, 255, 255, 0.95)';
            trendsChart.options.plugins.tooltip.titleColor = '#0f172a';
            trendsChart.options.plugins.tooltip.bodyColor = '#0f172a';
            trendsChart.options.plugins.tooltip.borderColor = 'rgba(0,0,0,0.1)';

            trendsChart.options.scales.x.grid.color = 'rgba(0, 0, 0, 0.05)';

            yGrids.forEach((id) => {
                const g = trendsChart.options.scales[id]?.grid;
                if (g && 'color' in g) g.color = 'rgba(0, 0, 0, 0.05)';
            });

            trendsChart.update();
        } else {
            Chart.defaults.color = '#94a3b8';
            trendsChart.options.plugins.tooltip.backgroundColor = 'rgba(15, 22, 36, 0.9)';
            trendsChart.options.plugins.tooltip.titleColor = '#e2e8f0';
            trendsChart.options.plugins.tooltip.bodyColor = '#e2e8f0';
            trendsChart.options.plugins.tooltip.borderColor = 'rgba(255,255,255,0.1)';

            trendsChart.options.scales.x.grid.color = 'rgba(255, 255, 255, 0.05)';

            yGrids.forEach((id) => {
                const g = trendsChart.options.scales[id]?.grid;
                if (g && 'color' in g) g.color = 'rgba(255, 255, 255, 0.05)';
            });

            trendsChart.update();
        }
    }

    // ==========================================
    // HARDWARE INTEGRATION
    // ==========================================
    function normalizeTelemetry(raw) {
        const r = raw && typeof raw === 'object' ? raw : {};

        const pick = (...keys) => {
            for (const k of keys) {
                if (r[k] === undefined || r[k] === null || r[k] === '') continue;
                const v = Number(r[k]);
                return Number.isFinite(v) ? v : undefined;
            }
            return undefined;
        };

        return {
            temperature: pick('temperature', 'temp', 'Temperature', 'TEMP'),
            turbidity: pick('turbidity', 'turb', 'Turbidity', 'ntu', 'NTU', 'turbidity_ntu'),
            tds: pick('tds', 'TDS', 'tds_ppm', 'TDS_ppm', 'ec_ppm'),
            ph: pick('ph', 'pH', 'PH')
        };
    }

    const userRecentReadings = [];
    const MAX_USER_TIMELINE_ITEMS = 8;

    function getCurrentUserRole() {
        return (document.body?.dataset?.userRole || 'viewer').toLowerCase();
    }

    function isViewerDashboard() {
        return getCurrentUserRole() !== 'admin' && !!document.getElementById('user-risk-card');
    }

    function userFriendlyRiskLabel(risk) {
        const label = normalizeRiskLabel(risk);

        if (label === 'Low Risk') return 'Safe';
        if (label === 'Moderate Risk') return 'Caution';
        if (label === 'Critical Risk') return 'Danger';

        return 'Awaiting Data';
    }

    function deriveRiskFromValues(values) {
        const ph = Number(values.ph);
        const turb = Number(values.turbidity);
        const tds = Number(values.tds);

        const hasCriticalPh = Number.isFinite(ph) && (ph < 6.5 || ph > 8.5);
        const hasCriticalTurb = Number.isFinite(turb) && turb > 5;
        const hasCriticalTds = Number.isFinite(tds) && tds > 600;

        if (hasCriticalPh || hasCriticalTurb || hasCriticalTds) return 'Critical Risk';

        const nearPhLimit = Number.isFinite(ph) && ((ph >= 6.5 && ph < 6.8) || (ph > 8.2 && ph <= 8.5));
        const nearTurbLimit = Number.isFinite(turb) && turb >= 3.5 && turb <= 5;
        const nearTdsLimit = Number.isFinite(tds) && tds >= 450 && tds <= 600;

        if (nearPhLimit || nearTurbLimit || nearTdsLimit) return 'Moderate Risk';

        if (Number.isFinite(ph) || Number.isFinite(turb) || Number.isFinite(tds)) return 'Low Risk';
        return 'Awaiting Data';
    }

    function riskUiConfig(risk) {
        const raw = String(risk || '').trim().toLowerCase();
        if (!raw || raw === 'awaiting data' || raw === 'no data yet' || raw === 'no data') {
            return {
                label: 'Awaiting Data',
                css: 'status-awaiting',
                icon: 'ph-hourglass-medium',
                title: 'Awaiting Reading',
                badge: 'No Data Yet',
                message: 'The dashboard is waiting for the latest water quality data from the AquaIntelX device.',
                actionTitle: 'Wait for sensor reading',
                actionText: 'Once the first live reading is received, AquaIntelX will show whether the water status is Safe, Caution, or Danger.'
            };
        }

        const label = normalizeRiskLabel(risk);

        if (label === 'Critical Risk') {
            return {
                label,
                css: 'status-critical',
                icon: 'ph-x-circle',
                title: 'Danger',
                badge: 'Danger',
                message: 'The latest reading indicates a serious water quality concern.',
                actionTitle: 'Do not drink the water',
                actionText: 'Avoid drinking the water until the source is inspected, retested, treated, or validated by an authorized laboratory.'
            };
        }

        if (label === 'Moderate Risk') {
            return {
                label,
                css: 'status-moderate',
                icon: 'ph-warning-circle',
                title: 'Caution',
                badge: 'Caution',
                message: 'Some readings need attention before the water should be used for drinking.',
                actionTitle: 'Treat or re-test before use',
                actionText: 'Run another test cycle, flush the chamber, and treat or inspect the source if the same reading remains abnormal.'
            };
        }

        if (label === 'Low Risk') {
            return {
                label,
                css: 'status-low',
                icon: 'ph-check-circle',
                title: 'Safe',
                badge: 'Safe',
                message: 'Water appears acceptable based on the current AquaIntelX sensor readings.',
                actionTitle: 'Water may be used normally',
                actionText: 'Current readings are within the reference values used by the system. Continue routine monitoring.'
            };
        }

        return {
            label: 'Awaiting Data',
            css: 'status-awaiting',
            icon: 'ph-hourglass-medium',
            title: 'Awaiting Reading',
            badge: 'No Data Yet',
            message: 'The dashboard is waiting for the latest water quality data from the AquaIntelX device.',
            actionTitle: 'Wait for sensor reading',
            actionText: 'Once the first live reading is received, AquaIntelX will show whether the water status is Safe, Caution, or Danger.'
        };
    }

    function setPlainText(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function sensorStatus(value, type) {
        const n = Number(value);
        if (!Number.isFinite(n)) return { text: 'No data', css: 'neutral' };

        if (type === 'ph') {
            if (n < 6.5 || n > 8.5) return { text: 'Outside Range', css: 'bad' };
            if ((n >= 6.5 && n < 6.8) || (n > 8.2 && n <= 8.5)) return { text: 'Near Limit', css: 'warn' };
            return { text: 'Normal', css: 'good' };
        }

        if (type === 'turbidity') {
            if (n > 5) return { text: 'Above Limit', css: 'bad' };
            if (n >= 3.5) return { text: 'Near Limit', css: 'warn' };
            return { text: 'Normal', css: 'good' };
        }

        if (type === 'tds') {
            if (n > 600) return { text: 'Above Limit', css: 'bad' };
            if (n >= 450) return { text: 'Near Limit', css: 'warn' };
            return { text: 'Normal', css: 'good' };
        }

        return { text: 'Recorded', css: 'neutral' };
    }

    function updateUserSensorCard(cardId, statusId, value, type) {
        const card = document.getElementById(cardId);
        const statusEl = document.getElementById(statusId);
        const status = sensorStatus(value, type);

        if (card) {
            card.classList.remove('good', 'warn', 'bad', 'neutral');
            card.classList.add(status.css);
        }

        if (statusEl) {
            statusEl.className = `sensor-pill ${status.css}`;
            statusEl.textContent = status.text;
        }
    }

    function buildUserReasons(values, riskLabel) {
        const why = [];
        const output = [];

        const ph = Number(values.ph);
        const turb = Number(values.turbidity);
        const tds = Number(values.tds);
        const temp = Number(values.temperature);

        // Recommendation first for easier user understanding
        if (riskLabel === 'Critical Risk') {
            output.push('Do not drink the water until the source is inspected, retested, treated, or validated by an authorized laboratory.');
        } else if (riskLabel === 'Moderate Risk') {
            output.push('Run another test cycle, flush the chamber, and treat or inspect the source if the same reading remains abnormal.');
        } else if (riskLabel === 'Low Risk') {
            output.push('Water may be used normally. Continue routine monitoring.');
        } else {
            output.push('Wait for complete sensor values before using the water quality result.');
        }

        // Why this result comes after the recommendation
        if (Number.isFinite(ph)) {
            if (ph < 6.5) why.push('pH is below the acceptable range of 6.5 to 8.5.');
            else if (ph > 8.5) why.push('pH is above the acceptable range of 6.5 to 8.5.');
            else why.push('pH is within the acceptable range of 6.5 to 8.5.');
        }

        if (Number.isFinite(turb)) {
            if (turb > 5) why.push('Turbidity is above the 5 NTU reference value, meaning the water may be less clear.');
            else why.push('Turbidity is within the 5 NTU reference value.');
        }

        if (Number.isFinite(tds)) {
            if (tds > 600) why.push('TDS is above the 600 ppm reference value.');
            else why.push('TDS is within the 600 ppm reference value.');
        }

        if (Number.isFinite(tds) && tds < 50 && Number.isFinite(ph) && (ph < 6.5 || ph > 8.5)) {
            why.push('Very low TDS with abnormal pH can make the pH reading less stable, so re-testing is recommended.');
        }

        if (Number.isFinite(temp)) {
            why.push('Temperature was recorded as supporting sensor data.');
        }

        if (why.length) {
            output.push('Why this result:');
            output.push(...why);
        } else {
            output.push('Why this result: Waiting for complete sensor values before explaining the result.');
        }

        return output;
    }

    function setRecentSensorValue(id, value, decimals = 1) {
        const el = document.getElementById(id);
        if (!el) return;

        const n = Number(value);
        el.textContent = Number.isFinite(n) ? n.toFixed(decimals) : '--';
    }

    function updateRecentSensorCard(cardId, statusId, value, type) {
        const card = document.getElementById(cardId);
        const statusEl = document.getElementById(statusId);
        const status = sensorStatus(value, type);

        if (card) {
            card.classList.remove('good', 'warn', 'bad', 'neutral');
            card.classList.add(status.css);
        }

        if (statusEl) {
            statusEl.className = `sensor-pill ${status.css}`;
            statusEl.textContent = status.text.toUpperCase();
        }
    }

    function updateUserTimeline(rawData, values) {
        const container = document.getElementById('user-risk-timeline');
        if (!container) return;

        setRecentSensorValue('recent-ph-val', values.ph, 2);
        setRecentSensorValue('recent-turb-val', values.turbidity, 2);
        setRecentSensorValue('recent-tds-val', values.tds, 0);
        setRecentSensorValue('recent-temp-val', values.temperature, 1);

        updateRecentSensorCard('recent-ph-card', 'recent-ph-status', values.ph, 'ph');
        updateRecentSensorCard('recent-turb-card', 'recent-turb-status', values.turbidity, 'turbidity');
        updateRecentSensorCard('recent-tds-card', 'recent-tds-status', values.tds, 'tds');
        updateRecentSensorCard('recent-temp-card', 'recent-temp-status', values.temperature, 'temperature');

        const displayTime = rawData?.display_time || rawData?.reading_time || rawData?.timestamp || rawData?.server_display_time || '';
        container.dataset.lastUpdated = displayTime || new Date().toLocaleString();
    }

    function updateUserDashboardStatus(rawData, values) {
        if (!isViewerDashboard()) return;

        const rawStatus = String(rawData?.status || '').trim().toLowerCase();
        const statusIsTransport = ['success', 'empty', 'error'].includes(rawStatus);
        const incomingRisk = rawData?.risk_level ||
            rawData?.final_status ||
            rawData?.standards_risk ||
            rawData?.model_risk ||
            rawData?.risk ||
            (!statusIsTransport ? rawData?.status : '');

        const riskLabel = incomingRisk ? normalizeRiskLabel(incomingRisk) : deriveRiskFromValues(values);
        const config = riskUiConfig(riskLabel);

        showRiskNotification(riskLabel, rawData);

        const card = document.getElementById('user-risk-card');
        if (card) {
            card.classList.remove('status-awaiting', 'status-low', 'status-moderate', 'status-critical');
            card.classList.add(config.css);
        }

        const icon = document.getElementById('user-risk-icon');
        if (icon) icon.innerHTML = `<i class="ph ${config.icon}"></i>`;

        setPlainText('user-risk-title', config.title);
        setPlainText('user-risk-message', config.message);
        setPlainText('user-risk-badge', config.badge);

        const displayTime = rawData?.display_time || rawData?.reading_time || rawData?.timestamp || rawData?.server_display_time || '';
        setPlainText('user-last-checked', `Last checked: ${displayTime || new Date().toLocaleString()}`);
        setPlainText('user-sensor-node', `Sensor node: ${rawData?.sensor_node || 'NODE-01'}`);

        updateUserSensorCard('user-ph-card', 'ph-status', values.ph, 'ph');
        updateUserSensorCard('user-turb-card', 'turb-status', values.turbidity, 'turbidity');
        updateUserSensorCard('user-tds-card', 'tds-status', values.tds, 'tds');
        updateUserSensorCard('user-temp-card', 'temp-status', values.temperature, 'temperature');

        const reasonsEl = document.getElementById('user-risk-reasons');
        if (reasonsEl) {
            reasonsEl.innerHTML = buildUserReasons(values, config.label).map((reason) => `<li>${reason}</li>`).join('');
        }

        updateUserTimeline(rawData, values);
    }

    class HardwareConnection {
        constructor() {
            this.mode = 'DATABASE';
            this.websocketUrl = '';
            this.restEndpoint = '';
            this.pollingInterval = 1000;

            this.lastSample = {};
            this.aiBusy = false;
            this.lastAIRequest = 0;

            this.ui = {
                temp: document.getElementById('temp-val'),
                turb: document.getElementById('turb-val'),
                tds: document.getElementById('tds-val'),
                ph: document.getElementById('ph-val')
            };

            this.setupInteractiveStatus();

            const indicator = document.querySelector('.status-indicator');
            const statusText = document.querySelector('.system-status span');

            if (indicator) {
                indicator.classList.add('active');
                indicator.style.backgroundColor = 'var(--success)';
            }

            if (statusText) {
                statusText.textContent = 'Reading Database';
            }
        }

        setupInteractiveStatus() {
            const statusEl = document.querySelector('.system-status');
            if (!statusEl) return;

            statusEl.style.cursor = 'pointer';
            statusEl.title = 'Click to configure hardware connection';

            const modal = document.getElementById('connection-modal');
            const closeBtn = document.getElementById('close-conn-modal');

            statusEl.addEventListener('click', () => {
                if (modal) {
                    modal.classList.add('open');
                    this.setupModalInteractions();
                }
            });

            if (closeBtn && modal) {
                closeBtn.addEventListener('click', () => {
                    modal.classList.remove('open');
                });
            }
        }

        setupModalInteractions() {
            const modal = document.getElementById('connection-modal');
            const wifiCard = document.getElementById('conn-wifi');
            const wiredCard = document.getElementById('conn-wired');
            const wifiConfig = document.getElementById('wifi-config');
            const connectWifiBtn = document.getElementById('btn-connect-wifi');
            const wifiInput = document.getElementById('wifi-url-input');

            if (!modal || !wifiCard || !wiredCard || !wifiConfig || !connectWifiBtn || !wifiInput) return;

            wifiInput.value = this.mode === 'REST' ? this.restEndpoint : this.websocketUrl;

            const clearActive = () => {
                wifiCard.classList.remove('active');
                wiredCard.classList.remove('active');
                wifiConfig.classList.remove('active');
            };

            wifiCard.onclick = () => {
                clearActive();
                wifiCard.classList.add('active');
                wifiConfig.classList.add('active');
            };

            wiredCard.onclick = async () => {
                clearActive();
                wiredCard.classList.add('active');
                modal.classList.remove('open');

                this.mode = 'SERIAL';
                localStorage.setItem('hw_mode', 'SERIAL');
                this.init();
            };

            connectWifiBtn.onclick = () => {
                const url = wifiInput.value.trim();
                if (!url) return;

                if (/^https?:\/\//i.test(url)) {
                    this.mode = 'REST';
                    this.restEndpoint = url;
                    localStorage.setItem('hw_mode', 'REST');
                    localStorage.setItem('hw_rest_url', url);
                } else {
                    this.mode = 'WEBSOCKET';
                    this.websocketUrl = url;
                    localStorage.setItem('hw_mode', 'WEBSOCKET');
                    localStorage.setItem('hw_url', url);
                }

                modal.classList.remove('open');
                this.init();
            };
        }

        init() {
            if (this.ws) {
                this.ws.onclose = null;
                this.ws.close();
            }

            if (this.pollingIntervalId) {
                clearInterval(this.pollingIntervalId);
            }

            const indicator = document.querySelector('.status-indicator');
            const statusText = document.querySelector('.system-status span');

            if (indicator) {
                indicator.classList.remove('active');
                indicator.style.backgroundColor = 'var(--warning)';
            }

            if (statusText) {
                statusText.textContent = 'Connecting...';
            }

            if (this.serialReader) {
                try {
                    this.serialReader.cancel();
                } catch (e) {}
                this.serialReader = null;
            }

            if (this.mode === 'WEBSOCKET') {
                this.connectWebSocket();
            } else if (this.mode === 'REST') {
                this.startRestPolling();
            } else if (this.mode === 'SERIAL') {
                this.connectSerial();
            }
        }

        connectWebSocket() {
            console.log(`Attempting to connect to hardware via WebSocket at ${this.websocketUrl}...`);

            try {
                this.ws = new WebSocket(this.websocketUrl);

                this.ws.onopen = () => {
                    console.log('Hardware connected successfully.');

                    const indicator = document.querySelector('.status-indicator');
                    const statusText = document.querySelector('.system-status span');

                    if (indicator) {
                        indicator.classList.add('active');
                        indicator.style.backgroundColor = 'var(--success)';
                    }

                    if (statusText) {
                        statusText.textContent = 'Hardware Connected';
                    }
                };

                this.ws.onmessage = (event) => {
                    try {
                        const data = JSON.parse(event.data);
                        this.updateDashboard(data);
                    } catch (e) {
                        console.error('Error parsing hardware data:', e);
                    }
                };

                this.ws.onclose = () => {
                    console.warn(`Hardware disconnected from ${this.websocketUrl}. Reconnecting in 5s...`);

                    const indicator = document.querySelector('.status-indicator');
                    const statusText = document.querySelector('.system-status span');

                    if (indicator) {
                        indicator.classList.remove('active');
                        indicator.style.backgroundColor = 'var(--danger)';
                    }

                    if (statusText) {
                        statusText.textContent = 'Connection Lost (Click to Edit)';
                    }

                    setTimeout(() => {
                        if (this.mode === 'WEBSOCKET') this.connectWebSocket();
                    }, 5000);
                };

                this.ws.onerror = (error) => {
                    console.error('WebSocket Error:', error);
                };
            } catch (err) {
                console.error('Invalid WebSocket URL:', err);

                const indicator = document.querySelector('.status-indicator');
                const statusText = document.querySelector('.system-status span');

                if (indicator) {
                    indicator.style.backgroundColor = 'var(--danger)';
                }

                if (statusText) {
                    statusText.textContent = 'Invalid IP (Click to Edit)';
                }
            }
        }

        startRestPolling() {
            console.log('Starting REST polling to hardware...');

            const indicator = document.querySelector('.status-indicator');
            const statusText = document.querySelector('.system-status span');

            if (indicator) {
                indicator.classList.add('active');
                indicator.style.backgroundColor = 'var(--success)';
            }

            if (statusText) {
                statusText.textContent = 'Polling Hardware';
            }

            const poll = async () => {
                try {
                    const response = await fetch(this.restEndpoint);
                    if (!response.ok) throw new Error('Hardware unavailable');

                    const data = await response.json();
                    this.updateDashboard(data);
                } catch (error) {
                    console.error('REST Polling Error:', error);
                }
            };

            poll();
            this.pollingIntervalId = setInterval(poll, this.pollingInterval);
        }

        async connectSerial() {
            if (!('serial' in navigator)) {
                alert('Web Serial API not supported in this browser window. Ensure you are using Chrome/Edge via localhost or HTTPS.');
                return;
            }

            try {
                const port = await navigator.serial.requestPort();
                await port.open({ baudRate: 115200 });

                const indicator = document.querySelector('.status-indicator');
                const statusText = document.querySelector('.system-status span');

                if (indicator) {
                    indicator.classList.add('active');
                    indicator.style.backgroundColor = 'var(--success)';
                }

                if (statusText) {
                    statusText.textContent = 'USB Connected';
                }

                const textDecoder = new TextDecoderStream();
                port.readable.pipeTo(textDecoder.writable);
                this.serialReader = textDecoder.readable.getReader();

                let accumulatedString = '';

                while (true) {
                    const { value, done } = await this.serialReader.read();
                    if (done) break;

                    accumulatedString += value;
                    const lines = accumulatedString.split('\n');
                    accumulatedString = lines.pop();

                    for (const line of lines) {
                        try {
                            const trimmed = line.trim();
                            if (trimmed) {
                                const data = JSON.parse(trimmed);
                                this.updateDashboard(data);
                            }
                        } catch (e) {}
                    }
                }
            } catch (err) {
                console.error('Serial Connection Error:', err);

                const indicator = document.querySelector('.status-indicator');
                const statusText = document.querySelector('.system-status span');

                if (indicator) {
                    indicator.classList.remove('active');
                    indicator.style.backgroundColor = 'var(--danger)';
                }

                if (statusText) {
                    statusText.textContent = 'USB Error (Click Setup)';
                }
            }
        }

        mergeSample(data) {
            const n = normalizeTelemetry(data);
            const m = { ...this.lastSample };

            if (n.temperature !== undefined) m.temperature = n.temperature;
            if (n.turbidity !== undefined) m.turbidity = n.turbidity;
            if (n.tds !== undefined) m.tds = n.tds;
            if (n.ph !== undefined) m.ph = n.ph;

            this.lastSample = m;
            return m;
        }

        updateDashboard(data) {
            const live = this.mergeSample(data);

            const updateMetric = (el, value, suffix, decimals = 1) => {
                if (!el) return;
                if (value === undefined || value === null) return;

                const num = parseFloat(value);
                if (!Number.isFinite(num)) return;

                const formatted = num.toFixed(decimals);
                const currentNum = parseFloat(el.textContent);
                const prev = Number.isFinite(currentNum) ? currentNum.toFixed(decimals) : null;

                if (formatted !== prev) {
                    el.innerHTML = `${formatted}<span class="unit">${suffix}</span>`;
                    el.classList.remove('value-update');
                    void el.offsetWidth;
                    el.classList.add('value-update');
                }
            };

            updateMetric(this.ui.temp, live.temperature, '°C');
            updateMetric(this.ui.turb, live.turbidity, 'NTU', 2);
            updateMetric(this.ui.tds, live.tds, 'ppm', 0);

            if (this.ui.ph && live.ph !== undefined && live.ph !== null) {
                const phNum = Number(live.ph);
                if (Number.isFinite(phNum)) {
                    const phFormatted = phNum.toFixed(2);
                    const phCurrentNum = parseFloat(this.ui.ph.textContent);
                    const phCurrent = Number.isFinite(phCurrentNum) ? phCurrentNum.toFixed(2) : null;

                    if (phFormatted !== phCurrent) {
                        this.ui.ph.textContent = phFormatted;
                        this.ui.ph.classList.remove('value-update');
                        void this.ui.ph.offsetWidth;
                        this.ui.ph.classList.add('value-update');
                    }
                }
            }

            const setStatus = (id, text) => {
                const el = document.getElementById(id);
                if (!el) return;
                el.innerHTML = `<i class="ph ph-database"></i><span>${text}</span>`;
            };

            setStatus('temp-status', 'Latest Reading');
            setStatus('turb-status', 'Latest Reading');
            setStatus('tds-status', 'Latest Reading');
            setStatus('ph-status', 'Latest Reading');

            updateUserDashboardStatus(data, live);

        }

        async analyzeWaterQuality(metrics) {
            if (this.aiBusy) return;

            const now = Date.now();
            if (now - this.lastAIRequest < 10000) return;

            this.aiBusy = true;
            this.lastAIRequest = now;

            const listEl = document.getElementById('ai-insights-list');

            if (!listEl) {
                this.aiBusy = false;
                return;
            }

            try {
                const params = new URLSearchParams({
                    temperature: metrics.temp ?? '',
                    ph: metrics.ph ?? '',
                    turbidity: metrics.turb ?? '',
                    tds: metrics.tds ?? ''
                });

                const response = await fetch(`ai_predict.php?${params.toString()}`);
                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message);
                }

                const riskLabel = normalizeRiskLabel(result.risk);

                let icon = 'ph-check-circle';
                let cssClass = 'normal';

                if (riskLabel === 'Moderate Risk') {
                    icon = 'ph-warning-circle';
                    cssClass = 'warning';
                }

                if (riskLabel === 'Critical Risk') {
                    icon = 'ph-x-circle';
                    cssClass = 'critical';
                }

                listEl.innerHTML = renderAdminAIInsightCard({
                    riskLabel,
                    readings: {
                        temperature: metrics.temp,
                        ph: metrics.ph,
                        turbidity: metrics.turb,
                        tds: metrics.tds
                    },
                    issue: buildAdminAIReason(riskLabel, {
                        temperature: metrics.temp,
                        ph: metrics.ph,
                        turbidity: metrics.turb,
                        tds: metrics.tds
                    }),
                    action: buildAdminAIAction(riskLabel),
                    sourceText: 'Live AI check from current sensor values.'
                });
            } catch (error) {
                console.error(error);

                listEl.innerHTML = `
                    <div class="insight-item warning">
                        <i class="ph ph-warning-circle insight-icon"></i>
                        <div class="insight-content">
                            <h4>AI Analysis Unavailable</h4>
                            <p>Could not connect to the machine learning model.</p>
                        </div>
                    </div>
                `;
            } finally {
                this.aiBusy = false;
            }
        }
    }

    // Start the connection manager
    window.hardwareTracker = new HardwareConnection();

    // ── Live Preview from get_live.php ─────────────────
    async function loadLivePreview() {
        try {
            const res = await fetch(`get_live.php?sensor_node=NODE-01&_=${Date.now()}`, {
                cache: 'no-store'
            });

            const data = await res.json();
            console.log('Live Preview:', data);

            if (data.status !== 'success') {
                console.warn('No live preview data:', data.message);
                return;
            }

            if (window.hardwareTracker) {
                window.hardwareTracker.updateDashboard({
                    sensor_node: data.sensor_node || 'NODE-01',
                    display_time: data.display_time || data.reading_time || data.updated_at || '',
                    reading_time: data.reading_time || '',
                    risk_level: data.risk_level || data.final_status || data.standards_risk || data.model_risk || '',
                    temperature: parseInt(data.temperature_valid) === 1 ? parseFloat(data.temperature) : undefined,
                    turbidity: data.turbidity !== null ? parseFloat(data.turbidity) : undefined,
                    tds: data.tds !== null ? parseFloat(data.tds) : undefined,
                    ph: parseInt(data.ph_valid) === 1 ? parseFloat(data.ph) : undefined
                });
            }

            const statusText = document.querySelector('.system-status span');

            if (statusText) {
                statusText.textContent = data.system_state || 'Live Preview';
            }
        } catch (err) {
            console.error('Live preview fetch failed:', err);
        }
    }

    loadLivePreview();
    setInterval(loadLivePreview, 5000);

    // ==========================================
    // SPA ROUTING & UI INTERACTIONS
    // ==========================================
    const navItems = document.querySelectorAll('.nav-item[data-target]');
    const routeLinks = document.querySelectorAll('.nav-item[data-target], .account-menu-link[data-target]');
    const pageSections = document.querySelectorAll('.page-section');
    const mainTitle = document.getElementById('main-title');
    const mainSubtitle = document.getElementById('main-subtitle');

    const pageTitles = {
        analytics: { title: 'Dashboard', subtitle: 'Real-time water quality monitoring overview' },
        dashboard: { title: 'Dashboard', subtitle: 'Real-time water quality monitoring overview' },
        history: { title: 'Analytics', subtitle: 'Filtered database statistics and exportable reports' },
        about: { title: 'About AquaIntelX', subtitle: 'System architecture and mission' },
        contact: { title: 'Contact Support', subtitle: 'Technical support and hardware requests' }
    };

    // ==========================================
    // MOBILE NAV TOGGLE
    // ==========================================
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.getElementById('sidebar');
    const closeSidebarBtn = document.getElementById('close-sidebar-btn');
    const mobileOverlay = document.getElementById('mobile-overlay');

    const toggleMenu = () => {
        if (sidebar) sidebar.classList.toggle('open');
        if (mobileOverlay) mobileOverlay.classList.toggle('open');
    };

    if (mobileMenuBtn && closeSidebarBtn && mobileOverlay) {
        mobileMenuBtn.addEventListener('click', toggleMenu);
        closeSidebarBtn.addEventListener('click', toggleMenu);
        mobileOverlay.addEventListener('click', toggleMenu);
    }



    // ==========================================
    // ANIMATED CUSTOM SELECT DROPDOWNS
    // Uses the same open/close motion pattern as the account/logout dropdown.
    // Native <select> elements stay in the page so existing filters still work.
    // ==========================================
    function closeAnimatedSelects(except = null) {
        document.querySelectorAll('.animated-select.open').forEach((selectBox) => {
            if (except && selectBox === except) return;
            selectBox.classList.remove('open');
            const trigger = selectBox.querySelector('.animated-select-trigger');
            if (trigger) trigger.setAttribute('aria-expanded', 'false');
        });
    }

    function syncAnimatedSelect(originalSelect) {
        if (!originalSelect) return;

        const selectBox = originalSelect.nextElementSibling;
        if (!selectBox || !selectBox.classList.contains('animated-select')) return;

        const selectedOption = originalSelect.options[originalSelect.selectedIndex];
        const label = selectBox.querySelector('.animated-select-label');
        const options = selectBox.querySelectorAll('.animated-select-option');

        if (label && selectedOption) {
            label.textContent = selectedOption.textContent;
        }

        options.forEach((optionButton) => {
            const isSelected = optionButton.dataset.value === originalSelect.value;
            optionButton.classList.toggle('selected', isSelected);
            optionButton.setAttribute('aria-selected', isSelected ? 'true' : 'false');
        });
    }

    function initAnimatedSelects() {
        const selects = document.querySelectorAll('select.time-filter, .filter-group select, select.contact-topic-select');

        selects.forEach((originalSelect) => {
            if (originalSelect.dataset.animatedSelect === 'true') {
                syncAnimatedSelect(originalSelect);
                return;
            }

            originalSelect.dataset.animatedSelect = 'true';
            originalSelect.classList.add('native-select-hidden');
            originalSelect.setAttribute('aria-hidden', 'true');
            originalSelect.tabIndex = -1;

            const selectBox = document.createElement('div');
            selectBox.className = 'animated-select';

            if (originalSelect.classList.contains('time-filter')) {
                selectBox.classList.add('animated-select-chart');
            }

            if (originalSelect.classList.contains('contact-topic-select')) {
                selectBox.classList.add('animated-select-contact');
            }

            const trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = 'animated-select-trigger';
            trigger.setAttribute('aria-haspopup', 'listbox');
            trigger.setAttribute('aria-expanded', 'false');

            const label = document.createElement('span');
            label.className = 'animated-select-label';

            const caret = document.createElement('i');
            caret.className = 'ph ph-caret-down animated-select-caret';

            trigger.appendChild(label);
            trigger.appendChild(caret);

            const menu = document.createElement('div');
            menu.className = 'animated-select-menu';
            menu.setAttribute('role', 'listbox');

            Array.from(originalSelect.options).forEach((option) => {
                const optionButton = document.createElement('button');
                optionButton.type = 'button';
                optionButton.className = 'animated-select-option';
                optionButton.textContent = option.textContent;
                optionButton.dataset.value = option.value;
                optionButton.setAttribute('role', 'option');

                if (option.disabled) {
                    optionButton.disabled = true;
                    optionButton.classList.add('disabled');
                }

                optionButton.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (optionButton.disabled) return;

                    originalSelect.value = option.value;
                    originalSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    syncAnimatedSelect(originalSelect);
                    closeAnimatedSelects();
                });

                menu.appendChild(optionButton);
            });

            selectBox.appendChild(trigger);
            selectBox.appendChild(menu);
            originalSelect.insertAdjacentElement('afterend', selectBox);

            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                const willOpen = !selectBox.classList.contains('open');
                closeAnimatedSelects(selectBox);
                selectBox.classList.toggle('open', willOpen);
                trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            });

            selectBox.addEventListener('click', (e) => {
                e.stopPropagation();
            });

            originalSelect.addEventListener('change', () => syncAnimatedSelect(originalSelect));
            syncAnimatedSelect(originalSelect);
        });
    }

    initAnimatedSelects();

    document.addEventListener('click', () => {
        closeAnimatedSelects();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAnimatedSelects();
        }
    });

    // ==========================================
    // ACCOUNT DROPDOWN + SHARED PAGE ROUTING
    // ==========================================
    const accountMenuWrapper = document.querySelector('.account-menu-wrapper');
    const accountMenuToggle = document.getElementById('account-menu-toggle');
    const accountDropdown = document.getElementById('account-dropdown');

    function closeAccountMenu() {
        if (!accountMenuToggle || !accountDropdown) return;
        accountMenuToggle.classList.remove('open');
        accountDropdown.classList.remove('open');
        accountMenuToggle.setAttribute('aria-expanded', 'false');
    }

    function setPageHeader(targetId) {
        const pageInfo = pageTitles[targetId] || pageTitles.analytics;
        if (mainTitle) mainTitle.textContent = pageInfo.title;
        if (mainSubtitle) mainSubtitle.textContent = pageInfo.subtitle;
    }

    function enforceDashboardHeaderIfActive() {
        const dashboardSection = document.getElementById('analytics') || document.getElementById('dashboard');
        if (dashboardSection?.classList.contains('active')) {
            setPageHeader('analytics');
        }
    }

    function switchPage(targetId) {
        if (!targetId) return;

        // Keep compatibility with older markup/scripts that used "analytics" internally.
        const resolvedTarget = targetId === 'dashboard' ? 'analytics' : targetId;

        navItems.forEach(nav => {
            const navTarget = nav.getAttribute('data-target');
            const resolvedNavTarget = navTarget === 'dashboard' ? 'analytics' : navTarget;
            nav.classList.toggle('active', resolvedNavTarget === resolvedTarget);
        });

        pageSections.forEach(section => {
            const sectionId = section.id === 'dashboard' ? 'analytics' : section.id;
            section.classList.toggle('active', sectionId === resolvedTarget);
        });

        setPageHeader(resolvedTarget);
        enforceDashboardHeaderIfActive();

        if (resolvedTarget === 'history') {
            loadHistory(1, currentHistoryFilters);
        }

        if (window.innerWidth <= 768 && sidebar?.classList.contains('open')) {
            toggleMenu();
        }

        closeAccountMenu();
    }

    if (accountMenuToggle && accountDropdown) {
        accountMenuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = accountDropdown.classList.toggle('open');
            accountMenuToggle.classList.toggle('open', isOpen);
            accountMenuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    document.addEventListener('click', (e) => {
        if (accountMenuWrapper && !accountMenuWrapper.contains(e.target)) {
            closeAccountMenu();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAccountMenu();
        }
    });

    routeLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            const targetId = link.getAttribute('data-target');
            if (!targetId) return;
            e.preventDefault();
            switchPage(targetId);
        });
    });

    // Force the default dashboard title on load.
    switchPage('analytics');
    enforceDashboardHeaderIfActive();

    // Contact form UI only
    const contactForm = document.getElementById('contactForm');

    if (contactForm) {
        contactForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const btn = contactForm.querySelector('.submit-btn');
            if (!btn) return;

            const originalText = btn.textContent;
            btn.innerHTML = '<i class="ph ph-spinner-gap" style="animation: spin 1s linear infinite;"></i> Sending...';

            setTimeout(() => {
                btn.innerHTML = '<i class="ph ph-check"></i> Sent Successfully';
                btn.style.backgroundColor = 'var(--success)';
                btn.style.boxShadow = '0 0 15px rgba(16, 185, 129, 0.4)';
                contactForm.reset();

                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.style.backgroundColor = '';
                    btn.style.boxShadow = '';
                }, 3000);
            }, 1500);
        });
    }


    // ==========================================
    // CLICKABLE LOGO + PROFILE SETTINGS
    // ==========================================
    const brandHome = document.getElementById('brand-home');
    if (brandHome) {
        brandHome.addEventListener('click', (e) => {
            e.preventDefault();
            window.location.reload();
        });
    }

    const profileModal = document.getElementById('profile-modal');
    const openProfileModal = document.getElementById('open-profile-modal');
    const closeProfileModalBtn = document.getElementById('close-profile-modal');
    const cancelProfileModalBtn = document.getElementById('cancel-profile-modal');
    const profileForm = document.getElementById('profile-form');
    const profileImageInput = document.getElementById('profile-image');
    const profilePreviewAvatar = document.getElementById('profile-preview-avatar');
    const profileMessage = document.getElementById('profile-message');
    const saveProfileBtn = document.getElementById('save-profile-btn');
    const currentPasswordInput = document.getElementById('current-password');
    const newPasswordInput = document.getElementById('new-password');
    const confirmNewPasswordInput = document.getElementById('confirm-new-password');


    // Password visibility toggle for profile modal
    document.querySelectorAll('[data-toggle-password]').forEach((toggleBtn) => {
        toggleBtn.addEventListener('click', () => {
            const inputId = toggleBtn.getAttribute('data-toggle-password');
            const input = document.getElementById(inputId);
            const icon = toggleBtn.querySelector('i');

            if (!input) return;

            const willShow = input.type === 'password';
            input.type = willShow ? 'text' : 'password';

            if (icon) {
                icon.className = willShow ? 'ph ph-eye-slash' : 'ph ph-eye';
            }

            toggleBtn.setAttribute(
                'aria-label',
                willShow ? 'Hide password' : 'Show password'
            );
        });
    });


    function setProfileMessage(message, type = 'info') {
        if (!profileMessage) return;
        profileMessage.textContent = message || '';
        profileMessage.className = `profile-message ${type}`;
    }

    function closeProfileModal() {
        if (!profileModal) return;
        profileModal.classList.remove('open');
        setProfileMessage('');
    }

    if (openProfileModal && profileModal) {
        openProfileModal.addEventListener('click', (e) => {
            e.preventDefault();
            closeAccountMenu();
            profileModal.classList.add('open');
        });
    }

    [closeProfileModalBtn, cancelProfileModalBtn].forEach((btn) => {
        if (!btn) return;
        btn.addEventListener('click', closeProfileModal);
    });

    if (profileModal) {
        profileModal.addEventListener('click', (e) => {
            if (e.target === profileModal) {
                closeProfileModal();
            }
        });
    }

    if (profileImageInput && profilePreviewAvatar) {
        profileImageInput.addEventListener('change', () => {
            const file = profileImageInput.files && profileImageInput.files[0];
            if (!file) return;

            if (!file.type.startsWith('image/')) {
                setProfileMessage('Please select a valid image file.', 'error');
                profileImageInput.value = '';
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                setProfileMessage('Profile image must be 2 MB or smaller.', 'error');
                profileImageInput.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = () => {
                profilePreviewAvatar.innerHTML = `<img src="${reader.result}" alt="Profile image preview">`;
            };
            reader.readAsDataURL(file);
            setProfileMessage('');
        });
    }

    if (profileForm) {
        profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const currentPassword = currentPasswordInput ? currentPasswordInput.value.trim() : '';
            const newPassword = newPasswordInput ? newPasswordInput.value.trim() : '';
            const confirmNewPassword = confirmNewPasswordInput ? confirmNewPasswordInput.value.trim() : '';
            const wantsPasswordChange = currentPassword !== '' || newPassword !== '' || confirmNewPassword !== '';

            if (wantsPasswordChange) {
                if (!currentPassword || !newPassword || !confirmNewPassword) {
                    setProfileMessage('To change password, fill in current password, new password, and confirmation.', 'error');
                    return;
                }

                if (newPassword.length < 8) {
                    setProfileMessage('New password must be at least 8 characters.', 'error');
                    return;
                }

                if (newPassword !== confirmNewPassword) {
                    setProfileMessage('New password and confirmation do not match.', 'error');
                    return;
                }

                if (currentPassword === newPassword) {
                    setProfileMessage('New password must be different from your current password.', 'error');
                    return;
                }
            }

            const originalText = saveProfileBtn ? saveProfileBtn.innerHTML : '';
            if (saveProfileBtn) {
                saveProfileBtn.disabled = true;
                saveProfileBtn.innerHTML = '<i class="ph ph-spinner-gap" style="animation: spin 1s linear infinite;"></i> Saving...';
            }

            setProfileMessage('Saving profile changes...', 'info');

            try {
                const formData = new FormData(profileForm);
                const res = await fetch('profile_update.php', {
                    method: 'POST',
                    body: formData,
                    cache: 'no-store'
                });

                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Could not update profile.');
                }

                setProfileMessage('Profile updated. Reloading dashboard...', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 900);
            } catch (err) {
                console.error('Profile update failed:', err);
                setProfileMessage(err.message || 'Could not update profile.', 'error');
            } finally {
                if (saveProfileBtn) {
                    saveProfileBtn.disabled = false;
                    saveProfileBtn.innerHTML = originalText;
                }
            }
        });
    }


    // ==========================================
    // DATABASE INTEGRATION
    // ==========================================

    function getReadingDate(row) {
        const timeValue = row.display_time || row.reading_time || row.recorded_at;

        if (!timeValue) return new Date();

        if (typeof timeValue === 'string' && timeValue.includes(' ')) {
            return new Date(timeValue.replace(' ', 'T'));
        }

        return new Date(timeValue);
    }

    function formatReadingTime(row) {
        const ts = getReadingDate(row);

        return ts.toLocaleString(undefined, {
            dateStyle: 'short',
            timeStyle: 'medium'
        });
    }

    function normalizeRiskLabel(status) {
    const s = String(status || '').trim().toLowerCase();

        if (
            s === 'normal' ||
            s === 'low' ||
            s === 'low risk' ||
            s === 'safe' ||
            s === 'optimal'
        ) {
            return 'Low Risk';
        }

        if (
            s === 'moderate' ||
            s === 'moderate risk' ||
            s === 'warning' ||
            s === 'caution'
        ) {
            return 'Moderate Risk';
        }

        if (
            s === 'critical' ||
            s === 'critical risk' ||
            s === 'high' ||
            s === 'high risk' ||
            s === 'danger' ||
            s === 'unsafe'
        ) {
            return 'Critical Risk';
        }

        if (s.includes('critical') || s.includes('high') || s.includes('danger') || s.includes('unsafe')) {
            return 'Critical Risk';
        }

        if (s.includes('moderate') || s.includes('warning') || s.includes('caution')) {
            return 'Moderate Risk';
        }

        if (s.includes('normal') || s.includes('low') || s.includes('safe') || s.includes('optimal')) {
            return 'Low Risk';
        }

        return 'Moderate Risk';
    }

    function statusClassFromText(status) {
        const label = normalizeRiskLabel(status);

        if (label === 'Critical Risk') return 'critical';
        if (label === 'Moderate Risk') return 'warning';
        if (label === 'Low Risk') return 'optimal';

        return 'warning';
    }

    function displayStatusText(status) {
        return normalizeRiskLabel(status);
    }

    // ── History Table ─────────────────────────────────────
    const historyTbody = document.getElementById('history-table-body');

    let currentPage = 1;
    const HISTORY_PAGE_SIZE = 10;

    let currentHistoryFilters = {
        range: 'all',
        status: 'all',
        node: 'all'
    };

    let historySummaryChart = null;
    let historyRiskChart = null;

    async function loadHistory(page = 1, filters = currentHistoryFilters) {
        currentPage = page;

        // Keep Database Summary Statistics tied to the same History filter, not the Dashboard chart range.
        loadHistoryStats(filters);

        // The History Logs table was removed from the UI. Keep this function as the shared
        // refresh entry point for filters, export sync, and backward compatibility.
        if (!historyTbody) return;

        historyTbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted);">
                    <i class="ph ph-spinner-gap" style="animation:spin 1s linear infinite;font-size:1.5rem;display:block;margin-bottom:.5rem;"></i>
                    Loading data from database...
                </td>
            </tr>
        `;

        try {
            const params = new URLSearchParams({
                action: 'history',
                limit: String(HISTORY_PAGE_SIZE),
                page: String(page),
                range: filters.range || 'all',
                status: filters.status || 'all',
                node: filters.node || 'all'
            });

            const res = await fetch(`sensor_api.php?${params.toString()}`);
            const data = await res.json();

            if (!data.success || !data.data || !data.data.length) {
                historyTbody.innerHTML = `
                    <tr>
                        <td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted);">
                            <i class="ph ph-plugs" style="font-size:2rem;margin-bottom:.5rem;display:block;"></i>
                            No historical data found for the selected filter.
                        </td>
                    </tr>
                `;

                const paginationEl = document.getElementById('history-pagination');
                if (paginationEl) paginationEl.innerHTML = '';
                return;
            }

            historyTbody.innerHTML = data.data.map(row => {
                const tsStr = formatReadingTime(row);
                const finalStatus = row.risk_level || row.final_status || row.status || 'Moderate Risk';
                const badgeText = normalizeRiskLabel(finalStatus);
                const badgeClass = statusClassFromText(badgeText);

                const fmt = (v, d = 1) => {
                    if (v === null || v === undefined || v === '') return '—';
                    const num = parseFloat(v);
                    if (!Number.isFinite(num)) return '—';
                    return num.toFixed(d);
                };

                return `
                    <tr>
                        <td>${tsStr}</td>
                        <td>${row.sensor_node ?? '—'}</td>
                        <td>${fmt(row.temperature)}</td>
                        <td>${fmt(row.turbidity, 2)}</td>
                        <td>${fmt(row.ph, 2)}</td>
                        <td>${fmt(row.tds, 0)}</td>
                        <td><span class="status-badge ${badgeClass}">${badgeText}</span></td>
                    </tr>
                `;
            }).join('');

            const paginationEl = document.getElementById('history-pagination');

            if (paginationEl && data.meta) {
                currentPage = data.meta.page;

                paginationEl.innerHTML = `
                    <span style="color:var(--text-muted);font-size:14px;">
                        Page ${data.meta.page} of ${data.meta.total_pages} &nbsp;|&nbsp; ${data.meta.total} readings &nbsp;|&nbsp; ${HISTORY_PAGE_SIZE} per page
                    </span>
                    <div style="display:flex;gap:8px;">
                        <button class="btn btn-outline" ${page <= 1 ? 'disabled' : ''} onclick="window._loadHistory(${page - 1})">
                            <i class="ph ph-caret-left"></i> Prev
                        </button>
                        <button class="btn btn-outline" ${page >= data.meta.total_pages ? 'disabled' : ''} onclick="window._loadHistory(${page + 1})">
                            Next <i class="ph ph-caret-right"></i>
                        </button>
                    </div>
                `;
            }
        } catch (err) {
            historyTbody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align:center;padding:2rem;color:var(--danger);">
                        <i class="ph ph-warning" style="font-size:1.5rem;display:block;margin-bottom:.5rem;"></i>
                        Could not load data: ${err.message}
                    </td>
                </tr>
            `;
        }
    }

    window._loadHistory = function(page = 1) {
        loadHistory(page, currentHistoryFilters);
    };

    // ── Analytics Filter Popup + Export Sync ─────────────────
    const historyFilterBtn = document.getElementById('history-filter-btn');
    const historyFilterMenu = document.getElementById('history-filter-menu');
    const historyApplyFilter = document.getElementById('history-apply-filter');
    const historyResetFilter = document.getElementById('history-reset-filter');

    const historyRangeSelect = document.getElementById('history-range-select');
    const historyStatusSelect = document.getElementById('history-status-select');
    const historyNodeSelect = document.getElementById('history-node-select');
    const exportBtn = document.getElementById('export-btn');

    function syncExportLink() {
        if (!exportBtn) return;

        const params = new URLSearchParams({
            range: currentHistoryFilters.range,
            status: currentHistoryFilters.status,
            node: currentHistoryFilters.node
        });

        exportBtn.href = `export_csv.php?${params.toString()}`;
    }

    function applyHistoryFilters() {
        currentHistoryFilters = {
            range: historyRangeSelect?.value || 'all',
            status: historyStatusSelect?.value || 'all',
            node: historyNodeSelect?.value || 'all'
        };

        currentPage = 1;
        syncExportLink();
        loadHistory(1, currentHistoryFilters);

        if (historyFilterMenu) {
            historyFilterMenu.classList.remove('open');
        }

        closeAnimatedSelects();

        closeAnimatedSelects();
    }

    function resetHistoryFilters() {
        if (historyRangeSelect) historyRangeSelect.value = 'all';
        if (historyStatusSelect) historyStatusSelect.value = 'all';
        if (historyNodeSelect) historyNodeSelect.value = 'all';

        syncAnimatedSelect(historyRangeSelect);
        syncAnimatedSelect(historyStatusSelect);
        syncAnimatedSelect(historyNodeSelect);

        currentHistoryFilters = {
            range: 'all',
            status: 'all',
            node: 'all'
        };

        currentPage = 1;
        syncExportLink();
        loadHistory(1, currentHistoryFilters);

        if (historyFilterMenu) {
            historyFilterMenu.classList.remove('open');
        }
    }

    if (historyFilterBtn && historyFilterMenu) {
        historyFilterBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            historyFilterMenu.classList.toggle('open');
            closeAnimatedSelects();
        });

        historyFilterMenu.addEventListener('click', (e) => {
            e.stopPropagation();
        });

        document.addEventListener('click', () => {
            historyFilterMenu.classList.remove('open');
            closeAnimatedSelects();
        });
    }

    if (historyApplyFilter) {
        historyApplyFilter.addEventListener('click', applyHistoryFilters);
    }

    if (historyResetFilter) {
        historyResetFilter.addEventListener('click', resetHistoryFilters);
    }

    syncExportLink();

    function insightClassFromRisk(risk) {
        const label = normalizeRiskLabel(risk);

        if (label === 'Critical Risk') return 'critical';
        if (label === 'Moderate Risk') return 'warning';
        return 'normal';
    }

    function insightIconFromRisk(risk) {
        const label = normalizeRiskLabel(risk);

        if (label === 'Critical Risk') return 'ph-x-circle';
        if (label === 'Moderate Risk') return 'ph-warning-circle';
        return 'ph-check-circle';
    }

    function formatAdminReading(value, decimals = 2, fallback = '—') {
        const n = Number(value);
        return Number.isFinite(n) ? n.toFixed(decimals) : fallback;
    }

    function buildAdminAIReason(riskLabel, readings) {
        const ph = Number(readings.ph);
        const turbidity = Number(readings.turbidity);
        const tds = Number(readings.tds);
        const temperature = Number(readings.temperature);

        if (riskLabel === 'Critical Risk') {
            if (Number.isFinite(turbidity) && turbidity > 5) return 'Turbidity exceeds the 5 NTU reference value.';
            if (Number.isFinite(ph) && (ph < 6.5 || ph > 8.5)) return 'pH is outside the 6.5 to 8.5 acceptable range.';
            if (Number.isFinite(tds) && tds > 600) return 'TDS exceeds the 600 ppm reference value.';
            return 'Critical anomaly detected by AI and standards guardrails.';
        }

        if (riskLabel === 'Moderate Risk') {
            if (Number.isFinite(turbidity) && turbidity > 1) return 'Turbidity is elevated and should be re-checked.';
            if (Number.isFinite(tds) && tds >= 500) return 'TDS is approaching the 600 ppm reference value.';
            if (Number.isFinite(temperature) && (temperature < 10 || temperature > 35)) return 'Temperature may affect reading reliability.';
            return 'One or more readings need verification before normal use.';
        }

        if (riskLabel === 'Low Risk') {
            return 'Key readings are within the configured reference values.';
        }

        return 'Waiting for enough data to complete AI assessment.';
    }

    function buildAdminAIAction(riskLabel) {
        if (riskLabel === 'Critical Risk') return 'Avoid use, inspect the source, then re-sample after flushing.';
        if (riskLabel === 'Moderate Risk') return 'Re-test after flushing and verify calibration if repeated.';
        if (riskLabel === 'Low Risk') return 'Continue routine monitoring.';
        return 'Wait for a valid processed reading.';
    }

    function renderAdminAIInsightCard({ riskLabel, readings = {}, issue = '', action = '', sourceText = '' }) {
        const label = normalizeRiskLabel(riskLabel);
        const cssClass = insightClassFromRisk(label);
        const icon = insightIconFromRisk(label);

        const ph = formatAdminReading(readings.ph, 2);
        const turbidity = formatAdminReading(readings.turbidity, 2);
        const temperature = formatAdminReading(readings.temperature, 1);
        const tds = formatAdminReading(readings.tds, 0);

        const readingText = `Latest reading: pH ${ph}, turbidity ${turbidity} NTU, TDS ${tds} ppm, and temperature ${temperature}°C.`;

        return `
            <div class="admin-ai-card ${cssClass} admin-ai-text-card">
                <div class="admin-ai-header">
                    <div class="admin-ai-icon">
                        <i class="ph ${icon}"></i>
                    </div>
                    <div>
                        <span class="admin-ai-eyebrow">AI Assessment</span>
                        <h4>${label}</h4>
                    </div>
                </div>

                <p class="admin-ai-reading-text">${readingText}</p>

                <div class="admin-ai-text-block">
                    <small>Key finding</small>
                    <p>${issue}</p>
                </div>

                <div class="admin-ai-text-block">
                    <small>Recommended action</small>
                    <p>${action}</p>
                </div>

                ${sourceText ? `<p class="admin-ai-source">${sourceText}</p>` : ''}
            </div>
        `;
    }

    async function loadLatestAIInsightFromDB() {
        const listEl = document.getElementById('ai-insights-list');
        if (!listEl) return;

        try {
            const params = new URLSearchParams({
                action: 'history',
                limit: '1',
                page: '1',
                range: 'all',
                status: 'all',
                node: 'all'
            });

            const res = await fetch(`sensor_api.php?${params.toString()}`, {
                cache: 'no-store'
            });

            const data = await res.json();

            if (!data.success || !data.data || !data.data.length) {
                listEl.innerHTML = `
                    <div class="insight-item warning">
                        <i class="ph ph-warning-circle insight-icon"></i>
                        <div class="insight-content">
                            <h4>No Final Reading Yet</h4>
                            <p>Waiting for the first processed database reading.</p>
                        </div>
                    </div>
                `;
                return;
            }

            const row = data.data[0];

            const latestRiskForNotification = row.risk_level || row.final_status || row.status || '';
            showRiskNotification(latestRiskForNotification, row);

            const temp = row.temperature;
            const ph = row.ph;
            const turbidity = row.turbidity;
            const tds = row.tds;

            const savedRisk = row.risk_level || row.final_status || row.status || '';

            const aiParams = new URLSearchParams({
                temperature: temp ?? '',
                ph: ph ?? '',
                turbidity: turbidity ?? '',
                tds: tds ?? '',
                risk_level: savedRisk
            });

            const aiRes = await fetch(`ai_predict.php?${aiParams.toString()}`, {
                cache: 'no-store'
            });

            const result = await aiRes.json();

            if (!result.success) {
                throw new Error(result.message || 'AI prediction failed');
            }

            const riskLabel = normalizeRiskLabel(result.risk);
            const cssClass = insightClassFromRisk(riskLabel);
            const icon = insightIconFromRisk(riskLabel);

            const readings = {
                temperature: temp,
                ph,
                turbidity,
                tds
            };

            listEl.innerHTML = renderAdminAIInsightCard({
                riskLabel,
                readings,
                issue: buildAdminAIReason(riskLabel, readings),
                action: buildAdminAIAction(riskLabel),
                sourceText: 'Based on the latest saved database reading.'
            });
        } catch (error) {
            console.error(error);

            listEl.innerHTML = `
                <div class="insight-item warning">
                    <i class="ph ph-warning-circle insight-icon"></i>
                    <div class="insight-content">
                        <h4>AI Analysis Unavailable</h4>
                        <p>Could not analyze the latest saved database reading.</p>
                    </div>
                </div>
            `;
        }
    }

    // ── Chart Data from DB ────────────────────────────────
    async function loadChartFromDB(range = '24h') {
        try {
            const res = await fetch(`sensor_api.php?action=chart&range=${encodeURIComponent(range)}`);
            const data = await res.json();

            if (!data.success || !data.data || !data.data.length) return;

            chartTimeLabels.length = 0;
            tempSeries.length = 0;
            turbSeries.length = 0;
            tdsSeries.length = 0;
            phSeries.length = 0;

            data.data.forEach(row => {
                const ts = getReadingDate(row);

                chartTimeLabels.push(ts.toLocaleTimeString(undefined, {
                    hour: '2-digit',
                    minute: '2-digit'
                }));

                tempSeries.push(row.temperature !== null ? parseFloat(row.temperature) : null);
                turbSeries.push(row.turbidity !== null ? parseFloat(row.turbidity) : null);
                tdsSeries.push(row.tds !== null ? parseFloat(row.tds) : null);
                phSeries.push(row.ph !== null ? parseFloat(row.ph) : null);
            });

            trendsChart.update();
        } catch (err) {
            console.warn('Could not load chart data from DB:', err);
        }
    }

    // ── Analytics Database Summary + Graphs ───────────────────
    function getCssVar(name) {
        return getComputedStyle(document.body).getPropertyValue(name).trim();
    }

    function getHistoryFilterLabel(filters = currentHistoryFilters) {
        const rangeLabels = {
            all: 'All Records',
            '24h': 'Last 24 Hours',
            '7d': 'Last 7 Days',
            '30d': 'Last 30 Days'
        };

        const statusLabels = {
            all: 'All Risk Levels',
            normal: 'Low Risk Only',
            warning: 'Moderate Risk Only',
            critical: 'Critical Risk Only'
        };

        const pieces = [rangeLabels[filters.range] || 'All Records'];

        if ((filters.status || 'all') !== 'all') {
            pieces.push(statusLabels[filters.status] || filters.status);
        }

        if ((filters.node || 'all') !== 'all') {
            pieces.push(filters.node);
        }

        return pieces.join(' • ');
    }

    function destroyHistoryCharts() {
        if (historySummaryChart) {
            historySummaryChart.destroy();
            historySummaryChart = null;
        }

        if (historyRiskChart) {
            historyRiskChart.destroy();
            historyRiskChart = null;
        }
    }

    function renderHistoryStatsCharts(stats) {
        const summaryCanvas = document.getElementById('history-summary-chart');
        const riskCanvas = document.getElementById('history-risk-chart');

        if (!summaryCanvas || !riskCanvas || typeof Chart === 'undefined') return;

        destroyHistoryCharts();

        const textColor = getCssVar('--text-muted') || '#94a3b8';
        const borderColor = getCssVar('--border') || 'rgba(255,255,255,0.08)';
        const primary = getCssVar('--primary') || '#00e5ff';
        const success = getCssVar('--success') || '#10b981';
        const warning = getCssVar('--warning') || '#f59e0b';
        const danger = getCssVar('--danger') || '#ef4444';
        const accent = getCssVar('--accent') || '#8b5cf6';
        const secondary = getCssVar('--secondary') || '#3b82f6';

        const safeNumber = (value) => {
            const n = Number(value);
            return Number.isFinite(n) ? n : 0;
        };

        historySummaryChart = new Chart(summaryCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Temp °C', 'Turbidity NTU', 'TDS ppm', 'pH'],
                datasets: [{
                    label: 'Average',
                    data: [
                        safeNumber(stats.avg_temp),
                        safeNumber(stats.avg_turb),
                        safeNumber(stats.avg_tds),
                        safeNumber(stats.avg_ph)
                    ],
                    backgroundColor: [warning, primary, success, accent],
                    borderColor: [warning, primary, success, accent],
                    borderWidth: 1,
                    borderRadius: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 22, 36, 0.92)',
                        titleColor: '#e2e8f0',
                        bodyColor: '#e2e8f0',
                        borderColor,
                        borderWidth: 1
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: textColor, font: { size: 11, weight: '600' } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: borderColor },
                        ticks: { color: textColor }
                    }
                }
            }
        });

        historyRiskChart = new Chart(riskCanvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Low Risk', 'Moderate Risk', 'Critical Risk'],
                datasets: [{
                    data: [
                        safeNumber(stats.low_count),
                        safeNumber(stats.warning_count),
                        safeNumber(stats.critical_count)
                    ],
                    backgroundColor: [success, warning, danger],
                    borderColor: getCssVar('--bg-card') || '#151e32',
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: textColor,
                            usePointStyle: true,
                            padding: 14,
                            font: { size: 11, weight: '600' }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 22, 36, 0.92)',
                        titleColor: '#e2e8f0',
                        bodyColor: '#e2e8f0',
                        borderColor,
                        borderWidth: 1
                    }
                }
            }
        });
    }

    async function loadHistoryStats(filters = currentHistoryFilters) {
        try {
            const params = new URLSearchParams({
                action: 'stats',
                range: filters.range || 'all',
                status: filters.status || 'all',
                node: filters.node || 'all'
            });

            const res = await fetch(`sensor_api.php?${params.toString()}`);
            const data = await res.json();

            const statsEl = document.getElementById('db-stats-summary');
            const filterLabelEl = document.getElementById('db-stats-filter-label');

            if (!statsEl) return;

            if (filterLabelEl) {
                filterLabelEl.textContent = getHistoryFilterLabel(filters);
            }

            const s = data.data || data.stats || null;

            if (!data.success || !s) {
                statsEl.innerHTML = `
                    <div class="summary-empty-state">
                        <i class="ph ph-warning-circle"></i>
                        Could not load database summary statistics.
                    </div>
                `;
                destroyHistoryCharts();
                return;
            }

            const totalReadings = Number(s.total_readings || 0);

            if (totalReadings === 0) {
                statsEl.innerHTML = `
                    <div class="summary-empty-state">
                        <i class="ph ph-database"></i>
                        No records match the selected filter.
                    </div>
                `;
                destroyHistoryCharts();
                return;
            }

            const fmt = (value, decimals = 2, unit = '') => {
                if (value === null || value === undefined || value === '') return '—';
                const n = Number(value);
                if (!Number.isFinite(n)) return '—';
                return `${n.toFixed(decimals)}${unit ? ` ${unit}` : ''}`;
            };

            const rangeText = getHistoryFilterLabel(filters);

            statsEl.innerHTML = `
                <div class="summary-stat-grid">
                    <div class="summary-stat-card">
                        <span class="summary-stat-label">Total Records</span>
                        <strong>${totalReadings}</strong>
                        <small>${rangeText}</small>
                    </div>
                    <div class="summary-stat-card low">
                        <span class="summary-stat-label">Low Risk</span>
                        <strong>${Number(s.low_count || 0)}</strong>
                        <small>records</small>
                    </div>
                    <div class="summary-stat-card moderate">
                        <span class="summary-stat-label">Moderate Risk</span>
                        <strong>${Number(s.warning_count || 0)}</strong>
                        <small>records</small>
                    </div>
                    <div class="summary-stat-card critical">
                        <span class="summary-stat-label">Critical Risk</span>
                        <strong>${Number(s.critical_count || 0)}</strong>
                        <small>records</small>
                    </div>
                </div>

                <div class="summary-table-wrap">
                    <table class="summary-table">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Avg</th>
                                <th>Min</th>
                                <th>Max</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Temperature</td>
                                <td>${fmt(s.avg_temp, 2, '°C')}</td>
                                <td>${fmt(s.min_temp, 2, '°C')}</td>
                                <td>${fmt(s.max_temp, 2, '°C')}</td>
                            </tr>
                            <tr>
                                <td>Turbidity</td>
                                <td>${fmt(s.avg_turb, 2, 'NTU')}</td>
                                <td>${fmt(s.min_turb, 2, 'NTU')}</td>
                                <td>${fmt(s.max_turb, 2, 'NTU')}</td>
                            </tr>
                            <tr>
                                <td>TDS</td>
                                <td>${fmt(s.avg_tds, 2, 'ppm')}</td>
                                <td>${fmt(s.min_tds, 2, 'ppm')}</td>
                                <td>${fmt(s.max_tds, 2, 'ppm')}</td>
                            </tr>
                            <tr>
                                <td>pH</td>
                                <td>${fmt(s.avg_ph, 2)}</td>
                                <td>${fmt(s.min_ph, 2)}</td>
                                <td>${fmt(s.max_ph, 2)}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `;

            renderHistoryStatsCharts(s);
        } catch (err) {
            console.warn('History stats load error:', err);
        }
    }

    // ── Analytics Time Filter ─────────────────────────────
    const timeFilterSelect = document.querySelector('.time-filter');
    let currentAnalyticsRange = '24h';

    if (timeFilterSelect) {
        timeFilterSelect.addEventListener('change', () => {
            const map = {
                'Last 24 Hours': '24h',
                'Last 7 Days': '7d',
                'Last 30 Days': '30d'
            };

            currentAnalyticsRange = map[timeFilterSelect.value] || '24h';

            loadChartFromDB(currentAnalyticsRange);
            loadLatestAIInsightFromDB();
        });
    }

    // ── Initial load ──────────────────────────────────────
    loadChartFromDB(currentAnalyticsRange);
    loadLatestAIInsightFromDB();

    if (document.getElementById('history')?.classList.contains('active')) {
        loadHistory(1, currentHistoryFilters);
    }

    // Refresh dashboard chart/insight every 30 seconds.
    // Analytics summary is refreshed only when the Analytics section is active.
    setInterval(() => {
        if (document.getElementById('history')?.classList.contains('active')) {
            loadHistory(currentPage, currentHistoryFilters);
        }

        loadChartFromDB(currentAnalyticsRange);
        loadLatestAIInsightFromDB();
    }, 30000);

});