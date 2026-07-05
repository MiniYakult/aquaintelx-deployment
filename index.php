<?php require_once 'auth_check.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AquaIntelX Analytics</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="dashboard-container">
        <!-- Mobile Overlay -->
        <div class="mobile-overlay" id="mobile-overlay"></div>

        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="brand">
                <i class="ph ph-waves brand-icon"></i>
                <span class="brand-text">AquaIntelX</span>
                <button class="close-sidebar-btn" id="close-sidebar-btn">
                    <i class="ph ph-x"></i>
                </button>
            </div>

            <nav class="nav-menu">
                <a href="#" class="nav-item active" data-target="analytics">
                    <i class="ph ph-chart-line-up"></i>
                    <span>Analytics</span>
                </a>

                <a href="#" class="nav-item" data-target="history">
                    <i class="ph ph-clock-counter-clockwise"></i>
                    <span>History</span>
                </a>

                <a href="#" class="nav-item" data-target="about">
                    <i class="ph ph-info"></i>
                    <span>About</span>
                </a>

                <a href="#" class="nav-item" data-target="contact">
                    <i class="ph ph-envelope-simple"></i>
                    <span>Contact</span>
                </a>

                <a href="logout.php" class="nav-item" id="logout-btn" style="margin-top: auto; color: var(--danger);">
                    <i class="ph ph-sign-out"></i>
                    <span>Sign Out</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">

            <!-- GLOBAL TOP HEADER -->
            <!-- Keep this clean. Do NOT put History Filter or Export CSV here. -->
            <header class="top-header">
                <div class="header-left">
                    <button class="action-btn mobile-menu-btn" id="mobile-menu-btn" aria-label="Open Menu">
                        <i class="ph ph-list"></i>
                    </button>

                    <div>
                        <h1 class="page-title" id="main-title">Live Analytics</h1>
                        <p class="page-subtitle" id="main-subtitle">Real-time telemetry and anomaly detection</p>
                    </div>
                </div>

                <div class="header-actions">
                    <!-- Renamed to avoid duplicate ID conflict with History summary panel -->
                    <div id="db-stats-summary-header" style="font-size:13px;color:var(--text-muted);display:none;"></div>

                    <div class="system-status">
                        <div class="status-indicator active"></div>
                        <span>System Online</span>
                    </div>

                    <button class="action-btn theme-toggle" id="theme-toggle" aria-label="Toggle Theme">
                        <i class="ph ph-sun" id="theme-icon"></i>
                    </button>
                </div>
            </header>

            <!-- ANALYTICS SECTION -->
            <div id="analytics" class="page-section active">
                <section class="metrics-grid">

                    <!-- Temperature Card -->
                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-icon temp">
                                <i class="ph ph-thermometer"></i>
                            </div>
                            <span class="metric-title">Temperature</span>
                        </div>

                        <div class="metric-body">
                            <h2 class="metric-value" id="temp-val">--<span class="unit">°C</span></h2>
                            <div class="metric-trend" id="temp-status">
                                <i class="ph ph-spinner-gap" style="animation: spin 1s linear infinite;"></i>
                                <span>Awaiting data...</span>
                            </div>
                        </div>

                        <div class="card-glow"></div>
                    </div>

                    <!-- Turbidity Card -->
                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-icon turb">
                                <i class="ph ph-drop-half-bottom"></i>
                            </div>
                            <span class="metric-title">Turbidity</span>
                        </div>

                        <div class="metric-body">
                            <h2 class="metric-value" id="turb-val">--<span class="unit">NTU</span></h2>
                            <div class="metric-trend" id="turb-status">
                                <i class="ph ph-spinner-gap" style="animation: spin 1s linear infinite;"></i>
                                <span>Awaiting data...</span>
                            </div>
                        </div>

                        <div class="card-glow"></div>
                    </div>

                    <!-- TDS Card -->
                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-icon tds">
                                <i class="ph ph-grains"></i>
                            </div>
                            <span class="metric-title">TDS Level</span>
                        </div>

                        <div class="metric-body">
                            <h2 class="metric-value" id="tds-val">--<span class="unit">ppm</span></h2>
                            <div class="metric-trend" id="tds-status">
                                <i class="ph ph-spinner-gap" style="animation: spin 1s linear infinite;"></i>
                                <span>Awaiting data...</span>
                            </div>
                        </div>

                        <div class="card-glow"></div>
                    </div>

                    <!-- pH Level Card -->
                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-icon ph">
                                <i class="ph ph-flask"></i>
                            </div>
                            <span class="metric-title">pH Level</span>
                        </div>

                        <div class="metric-body">
                            <h2 class="metric-value" id="ph-val">--</h2>
                            <div class="metric-trend" id="ph-status">
                                <i class="ph ph-spinner-gap" style="animation: spin 1s linear infinite;"></i>
                                <span>Awaiting data...</span>
                            </div>
                        </div>

                        <div class="card-glow"></div>
                    </div>
                </section>

                <section class="chart-section">
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Water Quality Trends</h3>

                            <!-- Analytics-only time filter -->
                            <div class="chart-actions">
                                <select class="time-filter">
                                    <option>Last 24 Hours</option>
                                    <option>Last 7 Days</option>
                                    <option>Last 30 Days</option>
                                </select>
                            </div>
                        </div>

                        <div class="canvas-wrapper">
                            <canvas id="trendsChart"></canvas>
                        </div>
                    </div>

                    <div class="ai-insights">
                        <h3>AI-Assisted Insights</h3>

                        <div class="insight-list" id="ai-insights-list">
                            <div class="insight-item normal">
                                <div class="insight-icon">
                                    <i class="ph ph-spinner-gap" style="animation: spin 1s linear infinite; display: inline-block;"></i>
                                </div>

                                <div class="insight-content">
                                    <h4>System Ready</h4>
                                    <p>Waiting for hardware telemetry to begin analysis...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- HISTORY SECTION -->
            <div id="history" class="page-section">
                <div class="panel">
                    <div class="panel-header">
                        <h3>Historical Logs</h3>

                        <!-- History-only Filter and Export controls -->
                        <div class="header-actions-inline history-actions">
                            <button id="history-filter-btn" type="button" class="btn btn-primary">
                                <i class="ph ph-funnel"></i> Filter
                            </button>

                            <a href="export_csv.php?range=all&status=all&node=all" class="btn btn-outline" id="export-btn">
                                <i class="ph ph-download-simple"></i> Export CSV
                            </a>

                            <!-- Filter Popup Menu -->
                            <div id="history-filter-menu" class="history-filter-menu">
                                <h4>Filter History</h4>

                                <div class="filter-group">
                                    <label for="history-range-select">Date Range</label>
                                    <select id="history-range-select">
                                        <option value="all" selected>All Time</option>
                                        <option value="24h">Last 24 Hours</option>
                                        <option value="7d">Last 7 Days</option>
                                        <option value="30d">Last 30 Days</option>
                                    </select>
                                </div>

                                <div class="filter-group">
                                    <label for="history-status-select">Sensor Status</label>
                                    <select id="history-status-select">
                                        <option value="all" selected>All</option>
                                        <option value="normal">Normal / Low Risk</option>
                                        <option value="warning">Warning / Moderate Risk</option>
                                        <option value="critical">Critical Risk</option>
                                    </select>
                                </div>

                                <div class="filter-group">
                                    <label for="history-node-select">Sensor Node</label>
                                    <select id="history-node-select">
                                        <option value="all" selected>All</option>
                                        <option value="NODE-01">NODE-01</option>
                                    </select>
                                </div>

                                <div class="filter-actions">
                                    <button id="history-apply-filter" type="button" class="btn btn-primary">
                                        Apply
                                    </button>

                                    <button id="history-reset-filter" type="button" class="btn btn-outline">
                                        Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Sensor Node</th>
                                    <th>Temp (°C)</th>
                                    <th>Turbidity (NTU)</th>
                                    <th>pH</th>
                                    <th>TDS (ppm)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody id="history-table-body">
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                        <i class="ph ph-plugs" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
                                        No historical data available. Connect hardware to start logging.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div id="history-pagination" style="
                        display:flex;
                        justify-content:space-between;
                        align-items:center;
                        padding:16px 0 0;
                        gap:12px;
                        flex-wrap:wrap;">
                    </div>
                </div>

                <!-- DB Stats Summary Panel -->
                <div class="panel" style="margin-top:24px;">
                    <h3 style="margin-bottom:16px;font-size:16px;">
                        <i class="ph ph-chart-bar" style="color:var(--primary);margin-right:8px;"></i>
                        Database Summary Statistics
                    </h3>

                    <!-- Keep this ID. script.js updates this one. -->
                    <div id="db-stats-summary"></div>
                </div>
            </div>

            <!-- ABOUT SECTION -->
            <div id="about" class="page-section">
                <div class="about-grid">
                    <div class="panel intro-panel">
                        <div class="brand-large">
                            <i class="ph ph-waves brand-icon"></i>
                            <span class="brand-text">AquaIntelX</span>
                        </div>

                        <h2>Water Quality Monitoring</h2>

                        <p>
                            AquaIntelX leverages machine learning algorithms and IoT sensors to provide real-time,
                            actionable insights into municipal and industrial water supplies.
                        </p>

                        <p>
                            Our mission is to proactively identify contaminants and anomalies before they impact
                            public health or operational integrity.
                        </p>
                    </div>

                    <div class="panel stats-panel">
                        <h3>System Capability</h3>

                        <div class="stats-list">
                            <div class="stat-item">
                                <span class="stat-val">99.9%</span>
                                <span class="stat-label">Uptime</span>
                            </div>

                            <div class="stat-item">
                                <span class="stat-val">&lt;50ms</span>
                                <span class="stat-label">Latency</span>
                            </div>

                            <div class="stat-item">
                                <span class="stat-val">256-bit</span>
                                <span class="stat-label">Encryption</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CONTACT SECTION -->
            <div id="contact" class="page-section">
                <div class="contact-container">
                    <div class="panel contact-info">
                        <h3>Get in Touch</h3>

                        <p>
                            Need support or want to expand your AquaIntelX sensor network?
                            Reach out to our engineering team.
                        </p>

                        <div class="info-list">
                            <div class="info-item">
                                <i class="ph ph-map-pin"></i>
                                <span>Polytechnic University of the Philippines</span>
                            </div>

                            <div class="info-item">
                                <i class="ph ph-envelope-simple"></i>
                                <span>AquaIntelX2026@gmail.com</span>
                            </div>

                            <div class="info-item">
                                <i class="ph ph-phone"></i>
                                <span>09660725867</span>
                            </div>
                        </div>
                    </div>

                    <div class="panel contact-form-panel">
                        <form class="contact-form" id="contactForm">
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" placeholder="Mark Sobremonte" required>
                            </div>

                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" placeholder="Mark@example.com" required>
                            </div>

                            <div class="form-group">
                                <label>Subject</label>
                                <select required>
                                    <option value="" disabled selected>Select a topic</option>
                                    <option>Technical Support</option>
                                    <option>Hardware Request</option>
                                    <option>Billing</option>
                                    <option>Other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Message</label>
                                <textarea placeholder="How can we help you?" rows="4" required></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary submit-btn">
                                Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- Connection Settings Modal -->
    <div class="modal-overlay" id="connection-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Hardware Connection Setup</h2>

                <button class="close-modal-btn" id="close-conn-modal">
                    <i class="ph ph-x"></i>
                </button>
            </div>

            <div class="modal-body">
                <p class="modal-desc">
                    Select how to connect to your AquaIntelX sensor array.
                </p>

                <div class="conn-options">
                    <div class="conn-card" id="conn-wifi" tabindex="0">
                        <div class="conn-icon">
                            <i class="ph ph-wifi-high"></i>
                        </div>

                        <div class="conn-info">
                            <h3>WiFi Network</h3>
                            <p>WebSocket or REST JSON API.</p>
                        </div>
                    </div>

                    <div class="conn-card" id="conn-wired" tabindex="0">
                        <div class="conn-icon">
                            <i class="ph ph-usb"></i>
                        </div>

                        <div class="conn-info">
                            <h3>Wired (USB)</h3>
                            <p>Direct serial connection.</p>
                        </div>
                    </div>
                </div>

                <div class="conn-details" id="wifi-config">
                    <div class="form-group">
                        <label>Network Address (WebSocket or REST)</label>
                        <input type="text" id="wifi-url-input" placeholder="ws://192.168.1.100:81">
                    </div>

                    <button class="btn btn-primary" id="btn-connect-wifi" style="width: 100%;">
                        Connect to Network
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Main Script -->
    <script src="script.js"></script>
</body>
</html>