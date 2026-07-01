// Initialize Chart.js
document.addEventListener('DOMContentLoaded', () => {

    // Authentication is handled server-side by auth_check.php.

    const ctx = document.getElementById('trendsChart').getContext('2d');

    // Gradient for the line chart
    const gradientFill = ctx.createLinearGradient(0, 0, 0, 300);
    gradientFill.addColorStop(0, 'rgba(0, 229, 255, 0.4)');
    gradientFill.addColorStop(1, 'rgba(0, 229, 255, 0.0)');

    const gradientFill2 = ctx.createLinearGradient(0, 0, 0, 300);
    gradientFill2.addColorStop(0, 'rgba(139, 92, 246, 0.4)');
    gradientFill2.addColorStop(1, 'rgba(139, 92, 246, 0.0)');

    Chart.defaults.color = '#94a3b8';
    Chart.defaults.font.family = "'Outfit', sans-serif";

    const LIVE_CHART_MAX_POINTS = 36;

    const gradientFill3 = ctx.createLinearGradient(0, 0, 0, 300);
    gradientFill3.addColorStop(0, 'rgba(245, 158, 11, 0.35)');
    gradientFill3.addColorStop(1, 'rgba(245, 158, 11, 0.0)');

    const gradientFill4 = ctx.createLinearGradient(0, 0, 0, 300);
    gradientFill4.addColorStop(0, 'rgba(52, 211, 153, 0.35)');
    gradientFill4.addColorStop(1, 'rgba(52, 211, 153, 0.0)');

    let chartTimeLabels = [];
    let tempSeries = [];
    let turbSeries = [];
    let tdsSeries = [];
    let phSeries = [];

    const trendsChart = new Chart(ctx, {
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
    });

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

        trendsChart.update('none');
    }

    // ==========================================
    // THEME TOGGLE
    // ==========================================
    const themeBtn = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');

    const currentTheme = localStorage.getItem('theme') || 'dark';

    if (currentTheme === 'light') {
        document.body.setAttribute('data-theme', 'light');
        if (themeIcon) themeIcon.className = 'ph ph-moon';
        updateChartTheme('light');
    }

    if (themeBtn) {
        themeBtn.addEventListener('click', () => {
            let theme = document.body.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
            document.body.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);

            if (themeIcon) {
                themeIcon.className = theme === 'light' ? 'ph ph-moon' : 'ph ph-sun';
            }

            updateChartTheme(theme);
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

            if (live.temperature !== undefined) {
                pushLiveChartSample({ ...live, time: new Date() });

                this.analyzeWaterQuality({
                    temp: live.temperature,
                    turb: live.turbidity,
                    tds: live.tds,
                    ph: live.ph
                });
            }
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

                let icon = 'ph-check-circle';
                let cssClass = 'normal';

                if (result.risk === 'Moderate Risk') {
                    icon = 'ph-warning-circle';
                    cssClass = 'warning';
                }

                if (result.risk === 'High Risk' || result.risk === 'Critical Risk') {
                    icon = 'ph-x-circle';
                    cssClass = 'critical';
                }

                listEl.innerHTML = `
                    <div class="insight-item ${cssClass}" style="display:block; padding:22px;">
                        <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                            <i class="ph ${icon}" style="font-size:34px;"></i>
                            <h4 style="font-size:20px; margin:0;">${result.risk}</h4>
                        </div>

                        <p style="font-size:15px; line-height:1.6; margin-bottom:16px;">
                            ${result.suggestion}
                        </p>

                        <div style="
                            padding:10px 14px;
                            border-radius:10px;
                            background:rgba(0,229,255,0.08);
                            font-size:13px;
                            color:var(--text-muted);
                        ">
                            AI analysis based on pH, Turbidity, Temperature, and TDS.
                        </div>
                    </div>
                `;
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
    const navItems = document.querySelectorAll('.nav-item');
    const pageSections = document.querySelectorAll('.page-section');
    const mainTitle = document.getElementById('main-title');
    const mainSubtitle = document.getElementById('main-subtitle');

    const pageTitles = {
        analytics: { title: 'Live Analytics', subtitle: 'Real-time telemetry and anomaly detection' },
        history: { title: 'History & Logs', subtitle: 'Historical sensor data and exportable reports' },
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

    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            const targetId = item.getAttribute('data-target');
            if (!targetId) return;

            e.preventDefault();

            navItems.forEach(nav => nav.classList.remove('active'));
            item.classList.add('active');

            pageSections.forEach(section => {
                section.classList.remove('active');
            });

            const targetSection = document.getElementById(targetId);
            if (targetSection) {
                targetSection.classList.add('active');
            }

            if (pageTitles[targetId]) {
                if (mainTitle) mainTitle.textContent = pageTitles[targetId].title;
                if (mainSubtitle) mainSubtitle.textContent = pageTitles[targetId].subtitle;
            }

            if (targetId === 'history') {
                loadHistory(1, currentHistoryFilters);
            }

            if (window.innerWidth <= 768) {
                toggleMenu();
            }
        });
    });

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

    function statusClassFromText(status) {
        const s = String(status || '').toLowerCase();

        if (s.includes('critical') || s.includes('high')) return 'critical';
        if (s.includes('warning') || s.includes('moderate')) return 'warning';
        if (s.includes('normal') || s.includes('low')) return 'optimal';

        return 'optimal';
    }

    function displayStatusText(status) {
        const s = String(status || '').trim();

        if (!s) return 'NORMAL';
        if (s.toLowerCase() === 'high risk') return 'CRITICAL';

        return s.replace(' Risk', '').toUpperCase();
    }

    // ── History Table ─────────────────────────────────────
    const historyTbody = document.getElementById('history-table-body');

    let currentPage = 1;

    let currentHistoryFilters = {
        range: 'all',
        status: 'all',
        node: 'all'
    };

    async function loadHistory(page = 1, filters = currentHistoryFilters) {
        if (!historyTbody) return;

        currentPage = page;

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
                limit: '50',
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
                const finalStatus = row.status || row.risk_level || row.final_status || 'Normal';
                const badgeClass = statusClassFromText(finalStatus);
                const badgeText = displayStatusText(finalStatus);

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
                        Page ${data.meta.page} of ${data.meta.total_pages} &nbsp;|&nbsp; ${data.meta.total} readings
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

    // ── History Filter Popup + Export Sync ─────────────────
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
    }

    function resetHistoryFilters() {
        if (historyRangeSelect) historyRangeSelect.value = 'all';
        if (historyStatusSelect) historyStatusSelect.value = 'all';
        if (historyNodeSelect) historyNodeSelect.value = 'all';

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
        });

        historyFilterMenu.addEventListener('click', (e) => {
            e.stopPropagation();
        });

        document.addEventListener('click', () => {
            historyFilterMenu.classList.remove('open');
        });
    }

    if (historyApplyFilter) {
        historyApplyFilter.addEventListener('click', applyHistoryFilters);
    }

    if (historyResetFilter) {
        historyResetFilter.addEventListener('click', resetHistoryFilters);
    }

    syncExportLink();

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

    // ── Stats Summary ─────────────────────────────────────
    async function loadStats(range = '24h') {
        try {
            const res = await fetch(`sensor_api.php?action=stats&range=${encodeURIComponent(range)}`);
            const data = await res.json();

            if (!data.success || !data.data) return;

            const s = data.data;
            const statsEl = document.getElementById('db-stats-summary');

            if (!statsEl) return;

            const fmtRow = (label, avg, min, max, unit) => `
                <tr>
                    <td style="font-weight:500;color:var(--text-main)">${label}</td>
                    <td>${avg ?? '—'} ${unit}</td>
                    <td>${min ?? '—'} ${unit}</td>
                    <td>${max ?? '—'} ${unit}</td>
                </tr>
            `;

            statsEl.innerHTML = `
                <table style="width:100%;font-size:14px;border-collapse:collapse;">
                    <thead>
                        <tr style="color:var(--text-muted);font-size:12px;text-transform:uppercase;letter-spacing:.5px;">
                            <th style="text-align:left;padding:8px 0;">Parameter</th>
                            <th>Avg</th>
                            <th>Min</th>
                            <th>Max</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${fmtRow('Temperature', s.avg_temp, s.min_temp, s.max_temp, '°C')}
                        ${fmtRow('Turbidity', s.avg_turb, s.min_turb, s.max_turb, 'NTU')}
                        ${fmtRow('TDS', s.avg_tds, s.min_tds, s.max_tds, 'ppm')}
                        ${fmtRow('pH', s.avg_ph, s.min_ph, s.max_ph, '')}
                    </tbody>
                </table>
                <p style="margin-top:12px;font-size:13px;color:var(--text-muted);">
                    <i class="ph ph-database"></i> ${s.total_readings ?? 0} readings in last ${range} &nbsp;|&nbsp;
                    <span style="color:var(--warning)">${s.warning_count ?? 0} warnings</span> &nbsp;
                    <span style="color:var(--danger)">${s.critical_count ?? 0} critical</span>
                </p>
            `;
        } catch (err) {
            console.warn('Stats load error:', err);
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
            loadStats(currentAnalyticsRange);
        });
    }

    // ── Initial load ──────────────────────────────────────
    loadChartFromDB(currentAnalyticsRange);
    loadStats(currentAnalyticsRange);

    if (document.getElementById('history')?.classList.contains('active')) {
        loadHistory(1, currentHistoryFilters);
    }

    // Refresh history & stats every 30 seconds in the background
    setInterval(() => {
        if (document.getElementById('history')?.classList.contains('active')) {
            loadHistory(currentPage, currentHistoryFilters);
        }

        loadStats(currentAnalyticsRange);
    }, 30000);

});