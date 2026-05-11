<?php
require_once 'config.php';
require_once 'security.php';
require_once 'encryption.php';
requireLogin();
$csrf_token = generateCSRFToken();

// Get all passwords
$stmt = $pdo->prepare("SELECT * FROM passwords WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$passwords = $stmt->fetchAll();

// Predefined sites by category
$sitesByCategory = [
    'Work' => [
        ['name' => 'Microsoft 365', 'icon' => 'fab fa-microsoft', 'url' => 'https://www.office.com'],
        ['name' => 'Google Workspace', 'icon' => 'fab fa-google', 'url' => 'https://workspace.google.com'],
        ['name' => 'Slack', 'icon' => 'fab fa-slack', 'url' => 'https://slack.com'],
        ['name' => 'Teams', 'icon' => 'fab fa-microsoft', 'url' => 'https://teams.microsoft.com'],
        ['name' => 'Zoom', 'icon' => 'fab fa-zoom', 'url' => 'https://zoom.us'],
        ['name' => 'Asana', 'icon' => 'fas fa-tasks', 'url' => 'https://asana.com'],
        ['name' => 'Trello', 'icon' => 'fab fa-trello', 'url' => 'https://trello.com'],
        ['name' => 'Jira', 'icon' => 'fab fa-jira', 'url' => 'https://www.atlassian.com/software/jira'],
        ['name' => 'GitHub', 'icon' => 'fab fa-github', 'url' => 'https://github.com'],
        ['name' => 'GitLab', 'icon' => 'fab fa-gitlab', 'url' => 'https://gitlab.com'],
        ['name' => 'Salesforce', 'icon' => 'fab fa-salesforce', 'url' => 'https://www.salesforce.com'],
        ['name' => 'HubSpot', 'icon' => 'fab fa-hubspot', 'url' => 'https://www.hubspot.com'],
        ['name' => 'Dropbox', 'icon' => 'fab fa-dropbox', 'url' => 'https://www.dropbox.com'],
        ['name' => 'Shopify', 'icon' => 'fab fa-shopify', 'url' => 'https://www.shopify.com'],
        ['name' => 'WordPress', 'icon' => 'fab fa-wordpress', 'url' => 'https://wordpress.com'],
        ['name' => 'Figma', 'icon' => 'fab fa-figma', 'url' => 'https://www.figma.com'],
        ['name' => 'Notion', 'icon' => 'fas fa-brain', 'url' => 'https://www.notion.so'],
    ],
    'Personal' => [
        ['name' => 'Gmail', 'icon' => 'fab fa-google', 'url' => 'https://mail.google.com'],
        ['name' => 'Outlook', 'icon' => 'fab fa-microsoft', 'url' => 'https://outlook.live.com'],
        ['name' => 'Yahoo Mail', 'icon' => 'fab fa-yahoo', 'url' => 'https://mail.yahoo.com'],
        ['name' => 'ProtonMail', 'icon' => 'fas fa-envelope', 'url' => 'https://protonmail.com'],
        ['name' => 'iCloud', 'icon' => 'fab fa-apple', 'url' => 'https://www.icloud.com'],
        ['name' => 'Google Drive', 'icon' => 'fab fa-google', 'url' => 'https://drive.google.com'],
        ['name' => 'Apple ID', 'icon' => 'fab fa-apple', 'url' => 'https://appleid.apple.com'],
        ['name' => 'Epic Games', 'icon' => 'fab fa-epic-games', 'url' => 'https://www.epicgames.com'],
        ['name' => 'Steam', 'icon' => 'fab fa-steam', 'url' => 'https://store.steampowered.com'],
        ['name' => 'Nintendo', 'icon' => 'fab fa-nintendo-switch', 'url' => 'https://accounts.nintendo.com'],
        ['name' => 'PlayStation', 'icon' => 'fab fa-playstation', 'url' => 'https://www.playstation.com'],
        ['name' => 'Canva', 'icon' => 'fab fa-canva', 'url' => 'https://www.canva.com'],
        ['name' => 'Pinterest', 'icon' => 'fab fa-pinterest', 'url' => 'https://www.pinterest.com'],
        ['name' => 'Reddit', 'icon' => 'fab fa-reddit', 'url' => 'https://www.reddit.com'],
    ],
    'Finance' => [
        ['name' => 'BDO Unibank', 'icon' => 'fas fa-university', 'url' => 'https://www.bdo.com.ph'],
        ['name' => 'BPI', 'icon' => 'fas fa-university', 'url' => 'https://www.bpi.com.ph'],
        ['name' => 'Metrobank', 'icon' => 'fas fa-university', 'url' => 'https://www.metrobank.com.ph'],
        ['name' => 'Security Bank', 'icon' => 'fas fa-shield-alt', 'url' => 'https://www.securitybank.com'],
        ['name' => 'RCBC', 'icon' => 'fas fa-chart-line', 'url' => 'https://www.rcbc.com'],
        ['name' => 'UnionBank', 'icon' => 'fas fa-building', 'url' => 'https://www.unionbankph.com'],
        ['name' => 'Chinabank', 'icon' => 'fas fa-dragon', 'url' => 'https://www.chinabank.ph'],
        ['name' => 'EastWest Bank', 'icon' => 'fas fa-globe-asia', 'url' => 'https://www.eastwestbanker.com'],
        ['name' => 'PSBank', 'icon' => 'fas fa-hand-holding-usd', 'url' => 'https://www.psbank.com.ph'],
        ['name' => 'Landbank', 'icon' => 'fas fa-landmark', 'url' => 'https://www.landbank.com'],
        ['name' => 'PNB', 'icon' => 'fas fa-landmark', 'url' => 'https://www.pnb.com.ph'],
        ['name' => 'GCash', 'icon' => 'fas fa-mobile-alt', 'url' => 'https://www.gcash.com'],
        ['name' => 'Maya', 'icon' => 'fas fa-mobile-alt', 'url' => 'https://www.maya.ph'],
        ['name' => 'PayPal', 'icon' => 'fab fa-paypal', 'url' => 'https://www.paypal.com'],
        ['name' => 'Coins.ph', 'icon' => 'fas fa-coins', 'url' => 'https://coins.ph'],
        ['name' => 'Binance', 'icon' => 'fab fa-bitcoin', 'url' => 'https://www.binance.com'],
    ],
    'Social' => [
        ['name' => 'Facebook', 'icon' => 'fab fa-facebook', 'url' => 'https://www.facebook.com'],
        ['name' => 'Instagram', 'icon' => 'fab fa-instagram', 'url' => 'https://www.instagram.com'],
        ['name' => 'Twitter', 'icon' => 'fab fa-twitter', 'url' => 'https://twitter.com'],
        ['name' => 'TikTok', 'icon' => 'fab fa-tiktok', 'url' => 'https://www.tiktok.com'],
        ['name' => 'LinkedIn', 'icon' => 'fab fa-linkedin', 'url' => 'https://www.linkedin.com'],
        ['name' => 'YouTube', 'icon' => 'fab fa-youtube', 'url' => 'https://www.youtube.com'],
        ['name' => 'Snapchat', 'icon' => 'fab fa-snapchat', 'url' => 'https://www.snapchat.com'],
        ['name' => 'Telegram', 'icon' => 'fab fa-telegram', 'url' => 'https://telegram.org'],
        ['name' => 'WhatsApp', 'icon' => 'fab fa-whatsapp', 'url' => 'https://web.whatsapp.com'],
        ['name' => 'Discord', 'icon' => 'fab fa-discord', 'url' => 'https://discord.com'],
        ['name' => 'Twitch', 'icon' => 'fab fa-twitch', 'url' => 'https://www.twitch.tv'],
        ['name' => 'Viber', 'icon' => 'fab fa-viber', 'url' => 'https://www.viber.com'],
        ['name' => 'Messenger', 'icon' => 'fab fa-facebook-messenger', 'url' => 'https://www.messenger.com'],
    ]
];

function getSiteIcon($name) {
    $icons = [
        'facebook' => 'fab fa-facebook', 'instagram' => 'fab fa-instagram', 'twitter' => 'fab fa-twitter',
        'google' => 'fab fa-google', 'gmail' => 'fab fa-google', 'youtube' => 'fab fa-youtube',
        'bdo' => 'fas fa-university', 'bpi' => 'fas fa-university', 'metrobank' => 'fas fa-university',
        'gcash' => 'fas fa-mobile-alt', 'maya' => 'fas fa-mobile-alt', 'paypal' => 'fab fa-paypal',
        'github' => 'fab fa-github', 'slack' => 'fab fa-slack', 'zoom' => 'fab fa-zoom',
        'teams' => 'fab fa-microsoft', 'outlook' => 'fab fa-microsoft', 'onedrive' => 'fab fa-microsoft',
    ];
    $nameLower = strtolower($name);
    foreach($icons as $key => $icon) {
        if(strpos($nameLower, $key) !== false) return $icon;
    }
    return 'fas fa-key';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vault - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #F8FAFC; font-family: 'Inter', sans-serif; }
        
        /* OLD SIDEBAR STYLES - RESTORED */
        .sidebar {
            width: 280px;
            background: #0F172A;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .sidebar-header h3 { color: #10B981; font-weight: 700; font-size: 1.4rem; }
        .sidebar-header p { color: #64748B; font-size: 0.75rem; }
        .sidebar-menu { padding: 1.5rem 0; }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 0.875rem 1.5rem;
            color: #94A3B8;
            text-decoration: none;
            gap: 12px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .sidebar-menu a i { width: 20px; }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(16,185,129,0.08);
            color: #10B981;
            border-left: 3px solid #10B981;
        }
        .sidebar-menu hr { margin: 1rem 1.5rem; border-color: rgba(255,255,255,0.08); }
        
        .main-content { margin-left: 280px; padding: 2rem; }
        
        .top-nav {
            background: white;
            border-radius: 16px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #E2E8F0;
        }
        .page-title h1 { color: #0F172A; font-size: 1.5rem; font-weight: 700; margin: 0; }
        .page-title p { color: #64748B; font-size: 0.85rem; margin: 0; }
        
        .filters-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid #E2E8F0;
        }
        
        /* MAIN CONTAINER CARD - Scrollable */
        .main-container-card {
            background: white;
            border-radius: 24px;
            border: 1px solid #E2E8F0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .container-header {
            padding: 1rem 1.5rem;
            background: #F8FAFC;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .container-header h3 {
            font-size: 0.95rem;
            font-weight: 600;
            color: #0F172A;
            margin: 0;
        }
        
        .container-header h3 i {
            color: #10B981;
            margin-right: 8px;
        }
        
        .credential-stats {
            font-size: 0.75rem;
            color: #64748B;
            background: white;
            padding: 4px 12px;
            border-radius: 30px;
            border: 1px solid #E2E8F0;
        }
        
        /* Scrollable Content Area */
        .scrollable-content {
            max-height: calc(100vh - 300px);
            overflow-y: auto;
            padding: 1.5rem;
        }
        
        /* Custom Scrollbar */
        .scrollable-content::-webkit-scrollbar {
            width: 6px;
        }
        
        .scrollable-content::-webkit-scrollbar-track {
            background: #F1F5F9;
            border-radius: 10px;
        }
        
        .scrollable-content::-webkit-scrollbar-thumb {
            background: #10B981;
            border-radius: 10px;
        }
        
        .scrollable-content::-webkit-scrollbar-thumb:hover {
            background: #059669;
        }
        
        /* Premium 3-Column Grid Layout */
        .credentials-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.25rem;
        }
        
        /* Premium Credential Card Design */
        .credential-card {
            background: white;
            border-radius: 20px;
            padding: 1.25rem;
            border: 1px solid #E2E8F0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        
        .credential-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #10B981, #34D399, #10B981);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .credential-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px -12px rgba(0,0,0,0.15);
            border-color: #10B981;
        }
        
        .credential-card:hover::before {
            opacity: 1;
        }
        
        /* Card Header */
        .card-header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }
        
        .site-icon-wrapper {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #10B98115, #05966910);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .site-icon-wrapper i {
            font-size: 1.5rem;
            color: #10B981;
        }
        
        .category-badge {
            background: #F1F5F9;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 0.65rem;
            font-weight: 600;
            color: #0F172A;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .category-badge i {
            font-size: 0.65rem;
            color: #10B981;
        }
        
        .credential-name {
            font-weight: 700;
            color: #0F172A;
            margin-bottom: 0.35rem;
            font-size: 1rem;
        }
        
        .credential-url {
            font-size: 0.65rem;
            color: #94A3B8;
            margin-bottom: 0.75rem;
            word-break: break-all;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .info-row {
            background: #F8FAFC;
            border-radius: 14px;
            padding: 0.6rem;
            margin-bottom: 0.75rem;
        }
        
        .info-label {
            font-size: 0.6rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #64748B;
            letter-spacing: 0.5px;
            margin-bottom: 0.2rem;
        }
        
        .info-value {
            font-size: 0.8rem;
            color: #0F172A;
            font-weight: 500;
            word-break: break-all;
        }
        
        .password-field {
            background: #F8FAFC;
            border-radius: 14px;
            padding: 0.6rem;
            margin-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .password-display, .actual-password {
            font-family: monospace;
            font-size: 0.75rem;
            color: #0F172A;
        }
        
        .strength-badge {
            padding: 2px 8px;
            border-radius: 30px;
            font-size: 0.6rem;
            font-weight: 700;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.25rem;
        }
        
        .btn-icon {
            flex: 1;
            height: 34px;
            border-radius: 12px;
            border: none;
            transition: all 0.2s;
            cursor: pointer;
            font-size: 0.7rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        
        .btn-copy { background: #10B98115; color: #10B981; border: 1px solid #10B98120; }
        .btn-copy:hover { background: #10B981; color: white; transform: translateY(-2px); }
        
        .btn-edit { background: #3B82F615; color: #3B82F6; border: 1px solid #3B82F620; }
        .btn-edit:hover { background: #3B82F6; color: white; transform: translateY(-2px); }
        
        .btn-delete { background: #EF444415; color: #EF4444; border: 1px solid #EF444420; }
        .btn-delete:hover { background: #EF4444; color: white; transform: translateY(-2px); }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .credentials-grid { grid-template-columns: repeat(2, 1fr); }
        }
        
        @media (max-width: 768px) {
            .sidebar { left: -280px; }
            .main-content { margin-left: 0; }
            .credentials-grid { grid-template-columns: 1fr; }
            .scrollable-content { max-height: calc(100vh - 200px); }
        }
        
        /* Modal Styles */
        .category-picker {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .category-option {
            flex: 1;
            padding: 1rem;
            border-radius: 16px;
            border: 2px solid #E2E8F0;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        .category-option i {
            font-size: 1.5rem;
            display: block;
            margin-bottom: 0.5rem;
        }
        .category-option:hover {
            border-color: #10B981;
            background: #10B98110;
        }
        .category-option.selected {
            background: #10B981;
            color: white;
            border-color: #10B981;
        }
        .category-option.selected i { color: white; }
        
        .site-picker {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            padding: 0.5rem;
            background: white;
        }
        .site-option {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .site-option:hover { background: #F1F5F9; }
        .site-option.selected { background: #10B981; color: white; }
        .site-option.selected i { color: white; }
        .site-option i { width: 24px; font-size: 1.1rem; color: #10B981; }
        
        .modal-content {
            border-radius: 20px;
            border: none;
            overflow: hidden;
        }
        .modal-header {
            background: #0F172A;
            color: white;
            padding: 1.25rem 1.5rem;
            border: none;
        }
        .modal-header .modal-title { font-weight: 700; font-size: 1.2rem; }
        .modal-header .modal-title i { color: #10B981; margin-right: 8px; }
        .modal-body { padding: 1.75rem; background: #F8FAFC; }
        .modal-footer {
            padding: 1rem 1.75rem;
            background: white;
            border-top: 1px solid #E2E8F0;
        }
        
        .modal-dialog-landscape {
            max-width: 900px;
            width: 90%;
            margin: 1.75rem auto;
        }
        .landscape-container { display: flex; gap: 1.5rem; }
        .landscape-left {
            flex: 1;
            background: linear-gradient(135deg, #0F172A, #1E293B);
            border-radius: 20px;
            padding: 1.5rem;
            color: white;
            text-align: center;
        }
        .landscape-right { flex: 1.5; }
        .landscape-icon { font-size: 4rem; color: #10B981; margin-bottom: 1rem; }
        .detail-row {
            display: flex;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #E2E8F0;
        }
        .detail-label-landscape { width: 100px; font-weight: 600; color: #0F172A; }
        .detail-value-landscape { flex: 1; color: #64748B; word-break: break-all; }
        .password-input-group { display: flex; gap: 0.5rem; }
        .password-input-group input {
            flex: 1;
            border-radius: 12px;
            border: 1px solid #E2E8F0;
            padding: 8px 12px;
        }
        .password-input-group button {
            padding: 8px 12px;
            border-radius: 12px;
            border: 1px solid #E2E8F0;
            background: white;
            cursor: pointer;
        }
        .password-input-group button:hover { background: #10B981; color: white; }
        
        .form-control, .form-select {
            border-radius: 12px;
            border: 1px solid #E2E8F0;
            padding: 10px 14px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #10B981;
            box-shadow: 0 0 0 3px rgba(16,185,129,0.1);
            outline: none;
        }
        .btn-primary {
            background: #10B981;
            border: none;
            border-radius: 12px;
            padding: 10px 24px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-primary:hover { background: #059669; transform: translateY(-1px); }
        .btn-secondary {
            background: #F1F5F9;
            border: none;
            border-radius: 12px;
            padding: 10px 24px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10B981;
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            z-index: 9999;
            display: none;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <!-- OLD SIDEBAR - RESTORED -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-shield-alt"></i> PM System</h3>
            <p>Enterprise Password Management</p>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="vault.php" class="active"><i class="fas fa-lock"></i> Password Vault</a>
            <a href="settings.php"><i class="fas fa-cog"></i>Settings</a>
            <a href="logs.php"><i class="fas fa-history"></i> Activity Logs</a>
            <hr>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="top-nav">
            <div class="page-title">
                <h1>Password Vault</h1>
                <p>Click any credential to view full details</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus"></i> Add New Credential
            </button>
        </div>
        
        <div class="filters-card">
            <div class="row g-3">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-search"></i></span>
                        <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Search by name...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="categoryFilter" class="form-select">
                        <option value="">All Categories</option>
                        <option value="Work">💼 Work</option>
                        <option value="Personal">👤 Personal</option>
                        <option value="Finance">💰 Finance</option>
                        <option value="Social">📱 Social</option>
                    </select>
                </div>
                <div class="col-md-4 text-end">
                    <span class="text-muted" id="resultCount"><?php echo count($passwords); ?> credentials</span>
                </div>
            </div>
        </div>
        
        <!-- MAIN CONTAINER CARD WITH SCROLLABLE CONTENT -->
        <div class="main-container-card">
            <div class="container-header">
                <h3>
                    <i class="fas fa-lock"></i> Saved Credentials
                </h3>
                <div class="credential-stats">
                    <i class="fas fa-database"></i> <?php echo count($passwords); ?> entries
                </div>
            </div>
            
            <div class="scrollable-content">
                <div class="credentials-grid" id="vaultContainer">
                    <?php foreach($passwords as $item): 
                        $decryptedPwd = Encryption::decrypt($item['encrypted_password'], $_SESSION['master_key']);
                        $strength = checkPasswordStrength($decryptedPwd);
                        $strengthColor = $strength == 'Strong' ? '#10B981' : ($strength == 'Medium' ? '#F59E0B' : '#EF4444');
                        $strengthBg = $strength == 'Strong' ? '#10B98115' : ($strength == 'Medium' ? '#F59E0B15' : '#EF444415');
                        $siteIcon = !empty($item['icon']) ? $item['icon'] : getSiteIcon($item['name']);
                        
                        $catIcon = $item['category'] == 'Work' ? 'fa-briefcase' : ($item['category'] == 'Personal' ? 'fa-user' : ($item['category'] == 'Finance' ? 'fa-chart-line' : 'fa-hashtag'));
                    ?>
                    <div class="credential-card" data-id="<?php echo $item['id']; ?>"
                         data-name="<?php echo htmlspecialchars($item['name']); ?>"
                         data-url="<?php echo htmlspecialchars($item['url']); ?>"
                         data-username="<?php echo htmlspecialchars($item['username']); ?>"
                         data-password="<?php echo htmlspecialchars($decryptedPwd); ?>"
                         data-notes="<?php echo htmlspecialchars($item['notes']); ?>"
                         data-category="<?php echo $item['category']; ?>"
                         data-strength="<?php echo $strength; ?>"
                         data-icon="<?php echo $siteIcon; ?>">
                        
                        <div class="card-header-row">
                            <div class="site-icon-wrapper">
                                <i class="<?php echo $siteIcon; ?>"></i>
                            </div>
                            <div class="category-badge">
                                <i class="fas <?php echo $catIcon; ?>"></i>
                                <?php echo $item['category']; ?>
                            </div>
                        </div>
                        
                        <div class="credential-name"><?php echo sanitizeOutput($item['name']); ?></div>
                        
                        <?php if($item['url']): ?>
                        <div class="credential-url">
                            <i class="fas fa-link"></i>
                            <span><?php echo sanitizeOutput($item['url']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="info-row">
                            <div class="info-label"><i class="fas fa-user"></i> USERNAME</div>
                            <div class="info-value"><?php echo sanitizeOutput($item['username']); ?></div>
                        </div>
                        
                        <div class="password-field">
                            <div>
                                <span class="password-display">••••••••••••••••</span>
                                <span class="actual-password" style="display:none;"><?php echo sanitizeOutput($decryptedPwd); ?></span>
                            </div>
                            <span class="strength-badge" style="background: <?php echo $strengthBg; ?>; color: <?php echo $strengthColor; ?>;">
                                <i class="fas <?php echo $strength == 'Strong' ? 'fa-check-circle' : ($strength == 'Medium' ? 'fa-chart-line' : 'fa-exclamation-triangle'); ?>"></i> <?php echo $strength; ?>
                            </span>
                        </div>
                        
                        <div class="action-buttons">
                            <button class="btn-icon btn-copy" onclick="event.stopPropagation()">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                            <button class="btn-icon" onclick="event.stopPropagation(); togglePasswordDisplay(this)">
                                <i class="fas fa-eye"></i> Show
                            </button>
                            <button class="btn-icon btn-edit" onclick="event.stopPropagation()" 
                                    data-id="<?php echo $item['id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($item['name']); ?>" 
                                    data-url="<?php echo htmlspecialchars($item['url']); ?>" 
                                    data-username="<?php echo htmlspecialchars($item['username']); ?>" 
                                    data-password="<?php echo htmlspecialchars($decryptedPwd); ?>" 
                                    data-notes="<?php echo htmlspecialchars($item['notes']); ?>" 
                                    data-category="<?php echo $item['category']; ?>"
                                    data-icon="<?php echo $siteIcon; ?>"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editModal">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn-icon btn-delete delete-btn" onclick="event.stopPropagation()" data-id="<?php echo $item['id']; ?>">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if(empty($passwords)): ?>
                    <div class="text-center py-5" style="grid-column: 1 / -1;">
                        <i class="fas fa-lock" style="font-size: 3rem; color: #CBD5E1;"></i>
                        <h4 class="mt-3 text-muted">No passwords yet</h4>
                        <p>Click "Add New Credential" to get started</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Add New Credential</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="addForm">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-tag"></i> Step 1: Select Category *</label>
                            <div class="category-picker" id="addCategoryPicker">
                                <div class="category-option" data-cat="Work"><i class="fas fa-briefcase"></i> Work</div>
                                <div class="category-option" data-cat="Personal"><i class="fas fa-user"></i> Personal</div>
                                <div class="category-option" data-cat="Finance"><i class="fas fa-chart-line"></i> Finance</div>
                                <div class="category-option" data-cat="Social"><i class="fas fa-hashtag"></i> Social</div>
                            </div>
                            <input type="hidden" name="category" id="addSelectedCategory" required>
                        </div>
                        
                        <div class="mb-3" id="addSitePickerContainer" style="display: none;">
                            <label class="form-label"><i class="fas fa-globe"></i> Step 2: Select Site/App *</label>
                            <div class="site-picker" id="addSitePicker"></div>
                            <input type="hidden" name="name" id="addSelectedSite" required>
                            <input type="hidden" name="url" id="addSelectedSiteUrl">
                            <small class="text-muted mt-2 d-block">Click on a site to select it</small>
                        </div>
                        
                        <div id="addCredentialsContainer" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-user"></i> Username/Email *</label>
                                <input type="text" name="username" id="addUsername" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-lock"></i> Password *</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="newPassword" class="form-control" required>
                                    <button type="button" class="btn btn-outline-secondary" id="generatePwd"><i class="fas fa-sync-alt"></i> Generate</button>
                                </div>
                                <div id="passwordStrength" class="mt-2 small"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-sticky-note"></i> Notes</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="addSubmitBtn" disabled>Save Credential</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Credential</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="editForm">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-tag"></i> Step 1: Select Category *</label>
                            <div class="category-picker" id="editCategoryPicker">
                                <div class="category-option" data-cat="Work"><i class="fas fa-briefcase"></i> Work</div>
                                <div class="category-option" data-cat="Personal"><i class="fas fa-user"></i> Personal</div>
                                <div class="category-option" data-cat="Finance"><i class="fas fa-chart-line"></i> Finance</div>
                                <div class="category-option" data-cat="Social"><i class="fas fa-hashtag"></i> Social</div>
                            </div>
                            <input type="hidden" name="category" id="editSelectedCategory" required>
                        </div>
                        
                        <div class="mb-3" id="editSitePickerContainer" style="display: none;">
                            <label class="form-label"><i class="fas fa-globe"></i> Step 2: Select Site/App *</label>
                            <div class="site-picker" id="editSitePicker"></div>
                            <input type="hidden" name="name" id="editSelectedSite" required>
                            <input type="hidden" name="url" id="editSelectedSiteUrl">
                            <small class="text-muted mt-2 d-block">Click on a site to select it</small>
                        </div>
                        
                        <div id="editCredentialsContainer" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-user"></i> Username/Email *</label>
                                <input type="text" name="username" id="editUsername" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-lock"></i> Password *</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="editPassword" class="form-control" required>
                                    <button type="button" class="btn btn-outline-secondary" id="editGeneratePwd"><i class="fas fa-sync-alt"></i> Generate</button>
                                </div>
                                <div id="editPasswordStrength" class="mt-2 small"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-sticky-note"></i> Notes</label>
                                <textarea name="notes" id="editNotes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editSubmitBtn" disabled>Update Credential</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- View Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-landscape">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-info-circle"></i> Credential Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewModalBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <div id="toast" class="toast-notification"></div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const sitesByCategory = <?php echo json_encode($sitesByCategory); ?>;
        
        function showToast(message, type = 'success') {
            const toast = $('#toast');
            toast.css('background', type === 'success' ? '#10B981' : '#EF4444');
            toast.text(message);
            toast.fadeIn(300);
            setTimeout(() => toast.fadeOut(300), 3000);
        }
        
        function togglePasswordDisplay(btn) {
            const card = $(btn).closest('.credential-card');
            const display = card.find('.password-display');
            const actual = card.find('.actual-password');
            
            if(display.is(':visible')) {
                display.hide();
                actual.show();
                $(btn).html('<i class="fas fa-eye-slash"></i> Hide');
            } else {
                display.show();
                actual.hide();
                $(btn).html('<i class="fas fa-eye"></i> Show');
            }
        }
        
        // Add Modal
        $('#addCategoryPicker .category-option').click(function() {
            $('#addCategoryPicker .category-option').removeClass('selected');
            $(this).addClass('selected');
            const category = $(this).data('cat');
            $('#addSelectedCategory').val(category);
            
            const sites = sitesByCategory[category] || [];
            let html = '';
            sites.forEach(site => {
                html += `<div class="site-option" data-name="${site.name}" data-url="${site.url}" data-icon="${site.icon}">
                            <i class="${site.icon}"></i>
                            <span>${site.name}</span>
                        </div>`;
            });
            $('#addSitePicker').html(html);
            $('#addSitePickerContainer').show();
            $('#addCredentialsContainer').hide();
            $('#addSubmitBtn').prop('disabled', true);
            
            $('.site-option').off('click').on('click', function() {
                $('.site-option').removeClass('selected');
                $(this).addClass('selected');
                $('#addSelectedSite').val($(this).data('name'));
                $('#addSelectedSiteUrl').val($(this).data('url'));
                $('#addCredentialsContainer').show();
                $('#addSubmitBtn').prop('disabled', false);
            });
        });
        
        // Edit Modal
        $('.btn-edit').click(function() {
            const category = $(this).data('category');
            const siteName = $(this).data('name');
            const siteUrl = $(this).data('url');
            
            $('#edit_id').val($(this).data('id'));
            $('#editSelectedCategory').val(category);
            $('#editSelectedSite').val(siteName);
            $('#editSelectedSiteUrl').val(siteUrl);
            $('#editUsername').val($(this).data('username'));
            $('#editPassword').val($(this).data('password'));
            $('#editNotes').val($(this).data('notes'));
            
            $('#editCategoryPicker .category-option').removeClass('selected');
            $('#editCategoryPicker .category-option').each(function() {
                if($(this).data('cat') === category) $(this).addClass('selected');
            });
            
            const sites = sitesByCategory[category] || [];
            let html = '';
            sites.forEach(site => {
                const isSelected = (site.name === siteName) ? 'selected' : '';
                html += `<div class="site-option ${isSelected}" data-name="${site.name}" data-url="${site.url}" data-icon="${site.icon}">
                            <i class="${site.icon}"></i>
                            <span>${site.name}</span>
                        </div>`;
            });
            $('#editSitePicker').html(html);
            $('#editSitePickerContainer').show();
            $('#editCredentialsContainer').show();
            $('#editSubmitBtn').prop('disabled', false);
            checkEditStrength($(this).data('password'));
            
            $('.site-option').off('click').on('click', function() {
                $('.site-option').removeClass('selected');
                $(this).addClass('selected');
                $('#editSelectedSite').val($(this).data('name'));
                $('#editSelectedSiteUrl').val($(this).data('url'));
                $('#editSubmitBtn').prop('disabled', false);
            });
        });
        
        $('#editCategoryPicker .category-option').click(function() {
            $('#editCategoryPicker .category-option').removeClass('selected');
            $(this).addClass('selected');
            const newCategory = $(this).data('cat');
            $('#editSelectedCategory').val(newCategory);
            
            const sites = sitesByCategory[newCategory] || [];
            let html = '';
            sites.forEach(site => {
                html += `<div class="site-option" data-name="${site.name}" data-url="${site.url}" data-icon="${site.icon}">
                            <i class="${site.icon}"></i>
                            <span>${site.name}</span>
                        </div>`;
            });
            $('#editSitePicker').html(html);
            $('#editSelectedSite').val('');
            $('#editSelectedSiteUrl').val('');
            $('#editSubmitBtn').prop('disabled', true);
            $('#editCredentialsContainer').hide();
            
            $('.site-option').off('click').on('click', function() {
                $('.site-option').removeClass('selected');
                $(this).addClass('selected');
                $('#editSelectedSite').val($(this).data('name'));
                $('#editSelectedSiteUrl').val($(this).data('url'));
                $('#editSubmitBtn').prop('disabled', false);
                $('#editCredentialsContainer').show();
            });
        });
        
        function viewCredential(card) {
            const name = card.data('name');
            const url = card.data('url');
            const username = card.data('username');
            const password = card.data('password');
            const notes = card.data('notes') || 'No notes provided';
            const category = card.data('category');
            const strength = card.data('strength');
            const icon = card.data('icon') || 'fas fa-key';
            
            let strengthColor = strength === 'Strong' ? '#10B981' : (strength === 'Medium' ? '#F59E0B' : '#EF4444');
            let categoryIcon = {Work:'fa-briefcase', Personal:'fa-user', Finance:'fa-chart-line', Social:'fa-hashtag'}[category] || 'fa-folder';
            
            const html = `
                <div class="landscape-container">
                    <div class="landscape-left">
                        <div class="landscape-icon"><i class="${icon}"></i></div>
                        <div class="landscape-title">${escapeHtml(name)}</div>
                        <div class="mt-2"><i class="fas ${categoryIcon}"></i> ${category}</div>
                        <div class="mt-3"><span style="background: ${strengthColor}20; color: ${strengthColor}; padding: 4px 12px; border-radius: 20px;">${strength}</span></div>
                    </div>
                    <div class="landscape-right">
                        <div class="detail-row">
                            <div class="detail-label-landscape">URL</div>
                            <div class="detail-value-landscape">${url ? `<a href="${escapeHtml(url)}" target="_blank">${escapeHtml(url)}</a>` : 'Not provided'}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label-landscape">Username</div>
                            <div class="detail-value-landscape">${escapeHtml(username)}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label-landscape">Password</div>
                            <div class="detail-value-landscape">
                                <div class="password-input-group">
                                    <input type="password" id="viewPasswordInput" value="${escapeHtml(password)}" readonly>
                                    <button onclick="toggleViewPassword()"><i class="fas fa-eye"></i></button>
                                    <button onclick="copyViewPassword()"><i class="fas fa-copy"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label-landscape">Notes</div>
                            <div class="detail-value-landscape">${escapeHtml(notes)}</div>
                        </div>
                    </div>
                </div>
            `;
            $('#viewModalBody').html(html);
            $('#viewModal').modal('show');
            window.currentViewPassword = password;
        }
        
        function toggleViewPassword() {
            const input = $('#viewPasswordInput');
            const btn = $('.password-input-group button').first();
            if(input.attr('type') === 'password') {
                input.attr('type', 'text');
                btn.html('<i class="fas fa-eye-slash"></i>');
            } else {
                input.attr('type', 'password');
                btn.html('<i class="fas fa-eye"></i>');
            }
        }
        
        function copyViewPassword() {
            navigator.clipboard.writeText(window.currentViewPassword);
            showToast('Password copied to clipboard!');
        }
        
        function escapeHtml(text) {
            if(!text) return '';
            return String(text).replace(/[&<>]/g, m => m === '&' ? '&amp;' : (m === '<' ? '&lt;' : '&gt;'));
        }
        
        $('.credential-card').click(function(e) {
            if($(e.target).closest('.action-buttons').length) return;
            viewCredential($(this));
        });
        
        $('.btn-copy').click(function(e) {
            e.stopPropagation();
            const pwd = $(this).closest('.credential-card').find('.actual-password').text();
            navigator.clipboard.writeText(pwd);
            const originalText = $(this).html();
            $(this).html('<i class="fas fa-check"></i> Copied!');
            setTimeout(() => $(this).html(originalText), 2000);
            showToast('Password copied!');
        });
        
        function generatePassword() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
            let pwd = '';
            for(let i=0;i<16;i++) pwd += chars.charAt(Math.floor(Math.random() * chars.length));
            return pwd;
        }
        
        $('#generatePwd').click(function() { $('#newPassword').val(generatePassword()); checkStrength($('#newPassword').val()); });
        $('#editGeneratePwd').click(function() { $('#editPassword').val(generatePassword()); checkEditStrength($('#editPassword').val()); });
        
        function checkStrength(pwd) {
            let score = 0;
            if(pwd.length >= 8) score++;
            if(pwd.length >= 12) score++;
            if(/[A-Z]/.test(pwd)) score++;
            if(/[a-z]/.test(pwd)) score++;
            if(/[0-9]/.test(pwd)) score++;
            if(/[^A-Za-z0-9]/.test(pwd)) score++;
            let strength = score <= 2 ? 'Weak' : (score <= 4 ? 'Medium' : 'Strong');
            let color = strength === 'Strong' ? '#10B981' : (strength === 'Medium' ? '#F59E0B' : '#EF4444');
            $('#passwordStrength').html(`Strength: <span style="color: ${color};">${strength}</span>`);
        }
        
        function checkEditStrength(pwd) {
            let score = 0;
            if(pwd.length >= 8) score++;
            if(pwd.length >= 12) score++;
            if(/[A-Z]/.test(pwd)) score++;
            if(/[a-z]/.test(pwd)) score++;
            if(/[0-9]/.test(pwd)) score++;
            if(/[^A-Za-z0-9]/.test(pwd)) score++;
            let strength = score <= 2 ? 'Weak' : (score <= 4 ? 'Medium' : 'Strong');
            let color = strength === 'Strong' ? '#10B981' : (strength === 'Medium' ? '#F59E0B' : '#EF4444');
            $('#editPasswordStrength').html(`Strength: <span style="color: ${color};">${strength}</span>`);
        }
        
        $('#newPassword, #editPassword').on('keyup', function() {
            if($(this).attr('id') === 'newPassword') checkStrength($(this).val());
            else checkEditStrength($(this).val());
        });
        
        $('#searchInput, #categoryFilter').on('keyup change', function() {
            const searchVal = $('#searchInput').val().toLowerCase();
            const categoryVal = $('#categoryFilter').val();
            let visible = 0;
            $('.credential-card').each(function() {
                const name = $(this).find('.credential-name').text().toLowerCase();
                const category = $(this).data('category');
                if((!searchVal || name.includes(searchVal)) && (!categoryVal || category === categoryVal)) {
                    $(this).show();
                    visible++;
                } else {
                    $(this).hide();
                }
            });
            $('#resultCount').text(visible + ' credentials');
            if(visible === 0 && $('.credential-card').length > 0) {
                if($('.no-results').length === 0) {
                    $('.credentials-grid').append('<div class="text-center py-5 no-results" style="grid-column: 1 / -1;"><i class="fas fa-search" style="font-size: 3rem; color: #CBD5E1;"></i><h4 class="mt-3 text-muted">No matching credentials</h4><p>Try a different search term</p></div>');
                }
            } else { $('.no-results').remove(); }
        });
        
        $('#addForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: 'ajax_add.php', type: 'POST', data: $(this).serialize(), dataType: 'json',
                success: function(response) {
                    if(response.success) { showToast('Credential added!'); setTimeout(() => location.reload(), 1000); }
                    else showToast('Error: ' + response.message, 'error');
                },
                error: function() { showToast('Error saving', 'error'); }
            });
        });
        
        $('#editForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: 'ajax_edit.php', type: 'POST', data: $(this).serialize(), dataType: 'json',
                success: function(response) {
                    if(response.success) { showToast('Credential updated!'); setTimeout(() => location.reload(), 1000); }
                    else showToast('Error: ' + response.message, 'error');
                },
                error: function() { showToast('Error updating', 'error'); }
            });
        });
        
        $('.delete-btn').click(function(e) {
            e.stopPropagation();
            if(confirm('Delete this credential permanently?')) {
                $.ajax({
                    url: 'ajax_delete.php', type: 'POST', data: {id: $(this).data('id')}, dataType: 'json',
                    success: function(response) {
                        if(response.success) { showToast('Deleted!'); setTimeout(() => location.reload(), 1000); }
                        else showToast('Error deleting', 'error');
                    }
                });
            }
        });
        
        $('#addModal, #editModal').on('hidden.bs.modal', function() {
            $(this).find('.category-option').removeClass('selected');
            $(this).find('#addSitePickerContainer, #editSitePickerContainer').hide();
            $(this).find('#addCredentialsContainer, #editCredentialsContainer').hide();
            $(this).find('#addSubmitBtn, #editSubmitBtn').prop('disabled', true);
        });
    </script>
</body>
</html>