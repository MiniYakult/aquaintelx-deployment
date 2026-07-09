<?php
require_once 'auth_check.php';

function ax_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$currentUserNameRaw  = trim((string)($currentUser['name'] ?? 'AquaIntelX User'));
$currentUserEmailRaw = trim((string)($currentUser['email'] ?? ''));
$currentUserRoleRaw  = trim((string)($currentUser['role'] ?? 'viewer'));

$nameParts = preg_split('/\s+/', $currentUserNameRaw ?: 'AquaIntelX User');
$currentUserInitials = '';
foreach ($nameParts as $part) {
    if ($part === '') continue;
    $currentUserInitials .= strtoupper(substr($part, 0, 1));
    if (strlen($currentUserInitials) >= 2) break;
}
if ($currentUserInitials === '') {
    $currentUserInitials = 'AX';
}

$currentUserName  = ax_h($currentUserNameRaw ?: 'AquaIntelX User');
$currentUserEmail = ax_h($currentUserEmailRaw ?: 'No email available');
$currentUserRole  = ax_h(ucfirst($currentUserRoleRaw ?: 'viewer'));
$currentUserInitials = ax_h($currentUserInitials);
$currentUserRoleLower = strtolower($currentUserRoleRaw);
$isAdminUser = str_contains($currentUserRoleLower, 'admin');
$bodyUserRole = $isAdminUser ? 'admin' : ax_h($currentUserRoleLower ?: 'viewer');

$currentUserProfileImageRaw = '';
try {
    $pdoProfile = getDB();
    $stmtProfileColumns = $pdoProfile->query("SHOW COLUMNS FROM users LIKE 'profile_image'");
    if ($stmtProfileColumns && $stmtProfileColumns->fetch()) {
        $stmtProfile = $pdoProfile->prepare("SELECT profile_image FROM users WHERE id = :id LIMIT 1");
        $stmtProfile->execute([':id' => $currentUser['id'] ?? $_SESSION['user_id'] ?? 0]);
        $profileRow = $stmtProfile->fetch(PDO::FETCH_ASSOC);
        $currentUserProfileImageRaw = trim((string)($profileRow['profile_image'] ?? ''));
    }
} catch (Throwable $e) {
    $currentUserProfileImageRaw = '';
}

$currentUserProfileImage = ax_h($currentUserProfileImageRaw);
$currentUserAvatarSmall = $currentUserProfileImageRaw !== ''
    ? '<span class="account-avatar has-image"><img src="' . $currentUserProfileImage . '" alt="Profile image"></span>'
    : '<span class="account-avatar">' . $currentUserInitials . '</span>';

$currentUserAvatarLarge = $currentUserProfileImageRaw !== ''
    ? '<span class="account-avatar account-avatar-lg has-image"><img src="' . $currentUserProfileImage . '" alt="Profile image"></span>'
    : '<span class="account-avatar account-avatar-lg">' . $currentUserInitials . '</span>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AquaIntelX Dashboard</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <link rel="stylesheet" href="style.css?v=password-visibility-1">
</head>

<body data-user-role="<?php echo $bodyUserRole; ?>">
    <div class="dashboard-container">
        <!-- Mobile Overlay -->
        <div class="mobile-overlay" id="mobile-overlay"></div>

        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="brand">
                <a href="index.php" class="brand-home" id="brand-home" aria-label="Reload AquaIntelX dashboard">
                    <i class="ph ph-waves brand-icon"></i>
                    <span class="brand-text">AquaIntelX</span>
                </a>
                <button class="close-sidebar-btn" id="close-sidebar-btn">
                    <i class="ph ph-x"></i>
                </button>
            </div>

            <nav class="nav-menu">
                <a href="#" class="nav-item active" data-target="analytics">
                    <i class="ph ph-chart-line-up"></i>
                    <span>Dashboard</span>
                </a>

                <a href="#" class="nav-item" data-target="history">
                    <i class="ph ph-clock-counter-clockwise"></i>
                    <span>Analytics</span>
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
                        <h1 class="page-title" id="main-title">Dashboard</h1>
                        <p class="page-subtitle" id="main-subtitle">Real-time water quality monitoring overview</p>
                    </div>
                </div>

                <div class="header-actions">
                    <!-- Renamed to avoid duplicate ID conflict with History summary panel -->
                    <div class="system-status">
                        <div class="status-indicator active"></div>
                        <span>System Online</span>
                    </div>

                    <div class="account-menu-wrapper">
                        <button class="account-trigger" id="account-menu-toggle" type="button" aria-haspopup="true" aria-expanded="false">
                            <?php echo $currentUserAvatarSmall; ?>

                            <span class="account-trigger-text">
                                <strong><?php echo $currentUserName; ?></strong>
                                <small><?php echo $currentUserRole; ?> Account</small>
                            </span>

                            <i class="ph ph-caret-down account-caret"></i>
                        </button>

                        <div class="account-dropdown" id="account-dropdown" role="menu">
                            <div class="account-dropdown-header">
                                <?php echo $currentUserAvatarLarge; ?>

                                <div>
                                    <strong><?php echo $currentUserName; ?></strong>
                                    <span><?php echo $currentUserEmail; ?></span>
                                    <em><?php echo $currentUserRole; ?></em>
                                </div>
                            </div>

                            <div class="account-dropdown-divider"></div>

                            <button type="button" class="account-menu-link" id="open-profile-modal" role="menuitem">
                                <i class="ph ph-user-circle-gear"></i>
                                <span>Edit Profile</span>
                            </button>

                            <button type="button" class="account-menu-link theme-menu-link" id="theme-toggle" role="menuitem" aria-label="Toggle light and dark mode">
                                <i class="ph ph-sun" id="theme-icon"></i>
                                <span id="theme-menu-label">Light Mode</span>
                            </button>

                            <?php if ($isAdminUser): ?>
                            <a href="register.html" class="account-menu-link" role="menuitem">
                                <i class="ph ph-user-plus"></i>
                                <span>Create User Account</span>
                            </a>
                            <?php endif; ?>

                            <a href="#" class="account-menu-link" data-target="about" role="menuitem">
                                <i class="ph ph-info"></i>
                                <span>About</span>
                            </a>

                            <a href="#" class="account-menu-link" data-target="contact" role="menuitem">
                                <i class="ph ph-envelope-simple"></i>
                                <span>Contact Support</span>
                            </a>

                            <div class="account-dropdown-divider"></div>

                            <a href="logout.php" class="account-menu-link danger" role="menuitem">
                                <i class="ph ph-sign-out"></i>
                                <span>Sign Out</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- DASHBOARD SECTION -->
            <div id="analytics" class="page-section active">
                <?php if ($isAdminUser): ?>
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
                <?php else: ?>
                <section class="user-water-dashboard">
                    <div class="user-status-hero status-awaiting" id="user-risk-card">
                        <div class="user-status-left">
                            <div class="user-status-icon" id="user-risk-icon">
                                <i class="ph ph-hourglass-medium"></i>
                            </div>

                            <div>
                                <span class="user-status-eyebrow">Current Water Status</span>
                                <h2 id="user-risk-title">Awaiting Reading</h2>
                                <p id="user-risk-message">The dashboard is waiting for the latest water quality data from the AquaIntelX device.</p>
                            </div>
                        </div>

                        <div class="user-status-meta">
                            <span class="user-risk-badge" id="user-risk-badge">No Data Yet</span>
                            <small id="user-last-checked">Last checked: --</small>
                            <small id="user-sensor-node">Sensor node: NODE-01</small>
                        </div>
                    </div>

                    <div class="user-explain-grid">
                        <div class="panel user-explain-card">
                            <div class="user-card-heading">
                                <i class="ph ph-question"></i>
                                <h3>Recommended Action</h3>
                            </div>
                            <ul id="user-risk-reasons" class="user-reason-list">
                                <li>Waiting for live sensor values.</li>
                            </ul>
                        </div>

                        <div class="panel user-explain-card user-recent-sensor-panel">
                            <div class="user-card-heading">
                                <i class="ph ph-gauge"></i>
                                <h3>Sensor Readings</h3>
                            </div>

                            <div class="recent-sensor-grid" id="user-risk-timeline">
                                <div class="recent-sensor-card good" id="recent-ph-card">
                                    <div class="recent-sensor-top">
                                        <div class="metric-icon ph"><i class="ph ph-flask"></i></div>
                                        <span class="sensor-pill good" id="recent-ph-status">NORMAL</span>
                                    </div>
                                    <h4>pH Level</h4>
                                    <h2 id="recent-ph-val">--</h2>
                                    <p>Acceptable range: 6.5 - 8.5</p>
                                </div>

                                <div class="recent-sensor-card good" id="recent-turb-card">
                                    <div class="recent-sensor-top">
                                        <div class="metric-icon turb"><i class="ph ph-drop-half-bottom"></i></div>
                                        <span class="sensor-pill good" id="recent-turb-status">NORMAL</span>
                                    </div>
                                    <h4>Turbidity</h4>
                                    <h2><span id="recent-turb-val">--</span><small> NTU</small></h2>
                                    <p>Reference value: 5 NTU</p>
                                </div>

                                <div class="recent-sensor-card good" id="recent-tds-card">
                                    <div class="recent-sensor-top">
                                        <div class="metric-icon tds"><i class="ph ph-grains"></i></div>
                                        <span class="sensor-pill good" id="recent-tds-status">NORMAL</span>
                                    </div>
                                    <h4>TDS Level</h4>
                                    <h2><span id="recent-tds-val">--</span><small> ppm</small></h2>
                                    <p>Reference value: 600 ppm</p>
                                </div>

                                <div class="recent-sensor-card neutral" id="recent-temp-card">
                                    <div class="recent-sensor-top">
                                        <div class="metric-icon temp"><i class="ph ph-thermometer"></i></div>
                                        <span class="sensor-pill neutral" id="recent-temp-status">RECORDED</span>
                                    </div>
                                    <h4>Temperature</h4>
                                    <h2><span id="recent-temp-val">--</span><small> °C</small></h2>
                                    <p>Supporting sensor reading</p>
                                </div>
                            </div>
                        </div>
                    </div>


                </section>
                <?php endif; ?>
            </div>

            <!-- ANALYTICS SUMMARY SECTION -->
            <div id="history" class="page-section">
                <!-- DB Stats Summary Panel -->
                <div class="panel history-summary-panel">
                    <div class="history-summary-heading">
                        <div>
                            <h3>
                                <i class="ph ph-chart-bar"></i>
                                Database Summary Statistics
                            </h3>
                            <p>Summary follows the selected Analytics filter. Default view shows all recorded data.</p>
                        </div>

                        <div class="history-summary-toolbar">
                            <span id="db-stats-filter-label" class="summary-filter-pill">All Records</span>

                            <!-- Analytics Summary Filter and Export controls -->
                            <div class="header-actions-inline history-actions">
                                <button id="history-filter-btn" type="button" class="btn btn-primary">
                                    <i class="ph ph-funnel"></i> Filter
                                </button>

                                <a href="export_csv.php?range=all&status=all&node=all" class="btn btn-outline" id="export-btn">
                                    <i class="ph ph-download-simple"></i> Export CSV
                                </a>

                                <!-- Filter Popup Menu -->
                                <div id="history-filter-menu" class="history-filter-menu">
                                    <h4>Filter Summary</h4>

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
                                            <option value="normal">Low Risk</option>
                                            <option value="warning">Moderate Risk</option>
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
                    </div>

                    <!-- Keep this ID. script.js updates this one. -->
                    <div id="db-stats-summary" class="db-stats-summary"></div>

                    <div class="history-summary-visuals">
                        <div class="history-chart-card">
                            <div class="history-chart-title">
                                <i class="ph ph-gauge"></i>
                                <span>Average Sensor Values</span>
                            </div>
                            <div class="history-summary-chart-wrap">
                                <canvas id="history-summary-chart"></canvas>
                            </div>
                        </div>

                        <div class="history-chart-card">
                            <div class="history-chart-title">
                                <i class="ph ph-warning-circle"></i>
                                <span>Risk Distribution</span>
                            </div>
                            <div class="history-summary-chart-wrap small">
                                <canvas id="history-risk-chart"></canvas>
                            </div>
                        </div>
                    </div>
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
                            actionable insights and recommendation.
                        </p>

                        <p>

                            Our mission is to proactively alert water lining for possible contaminants and anomalies before they impact
                            public health. With the continous water quality monitoring capabilies of Aquaintelx this can help to prevent
                            such things in public.                        </p>
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
                                <select class="time-filter contact-topic-select" required>
                                    <option value="" selected>Select a topic</option>
                                    <option>Technical Support</option>
                                    <option>Hardware Request</option>
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


    <!-- Profile Settings Modal -->
    <div class="modal-overlay" id="profile-modal">
        <div class="modal-content profile-modal-content">
            <div class="modal-header">
                <h2>Edit Profile</h2>

                <button class="close-modal-btn" id="close-profile-modal" type="button" aria-label="Close profile settings">
                    <i class="ph ph-x"></i>
                </button>
            </div>

            <form class="modal-body profile-form profile-form-landscape" id="profile-form" enctype="multipart/form-data">
                <p class="modal-desc profile-modal-desc">
                    Update your account information, profile image, and password. Your role is kept unchanged for security.
                </p>

                <div class="profile-layout-grid">
                    <div class="profile-info-panel">
                        <div class="profile-preview-row">
                            <div class="profile-preview-avatar" id="profile-preview-avatar">
                                <?php if ($currentUserProfileImageRaw !== ''): ?>
                                    <img src="<?php echo $currentUserProfileImage; ?>" alt="Profile image preview">
                                <?php else: ?>
                                    <span><?php echo $currentUserInitials; ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="profile-preview-copy">
                                <strong><?php echo $currentUserRole; ?> Account</strong>
                                <small>PNG, JPG, GIF, or WebP. Maximum 2 MB.</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="profile-name">Full Name</label>
                            <input type="text" id="profile-name" name="name" value="<?php echo $currentUserName; ?>" maxlength="100" required>
                        </div>

                        <div class="form-group">
                            <label for="profile-email">Email Address</label>
                            <input type="email" id="profile-email" name="email" value="<?php echo ax_h($currentUserEmailRaw); ?>" maxlength="100" required>
                        </div>

                        <div class="form-group">
                            <label for="profile-image">Profile Image</label>
                            <input type="file" id="profile-image" name="profile_image" accept="image/png,image/jpeg,image/gif,image/webp">
                        </div>
                    </div>

                    <div class="profile-security-section">
                        <div class="profile-security-heading">
                            <h3>Change Password</h3>
                            <p>Leave these fields blank if you only want to update your profile information.</p>
                        </div>

                        <div class="form-group">
                            <label for="current-password">Current Password</label>
                            <div class="password-field-wrap">
                                <input type="password" id="current-password" name="current_password" autocomplete="current-password" minlength="8">
                                <button type="button" class="password-toggle-btn" data-toggle-password="current-password" aria-label="Show current password">
                                    <i class="ph ph-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-grid two-columns">
                            <div class="form-group">
                                <label for="new-password">New Password</label>
                                <div class="password-field-wrap">
                                    <input type="password" id="new-password" name="new_password" autocomplete="new-password" minlength="8">
                                    <button type="button" class="password-toggle-btn" data-toggle-password="new-password" aria-label="Show new password">
                                        <i class="ph ph-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="confirm-new-password">Confirm New Password</label>
                                <div class="password-field-wrap">
                                    <input type="password" id="confirm-new-password" name="confirm_new_password" autocomplete="new-password" minlength="8">
                                    <button type="button" class="password-toggle-btn" data-toggle-password="confirm-new-password" aria-label="Show confirm new password">
                                        <i class="ph ph-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="profile-message" id="profile-message" role="status"></div>

                <div class="profile-modal-actions">
                    <button type="button" class="btn btn-outline" id="cancel-profile-modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="save-profile-btn">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
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
    <script src="script.js?v=password-visibility-1"></script>
</body>
</html>