<?php
require_once 'config.php';
require_once 'security.php';
require_once 'encryption.php';
requireLogin();
$csrf_token = generateCSRFToken();

$stmt = $pdo->prepare("SELECT * FROM passwords WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$passwords = $stmt->fetchAll();

/* ── Icon map: name keyword → [FA-class, hex-color] ── */
$iconMap = [
    // Work
    'microsoft 365' => ['fab fa-microsoft',   '#0078D4'],
    'google workspace'=> ['fab fa-google',    '#4285F4'],
    'slack'         => ['fab fa-slack',        '#4A154B'],
    'teams'         => ['fab fa-microsoft',    '#6264A7'],
    'zoom'          => ['fas fa-video',        '#2D8CFF'],
    'asana'         => ['fas fa-circle-nodes', '#F06A6A'],
    'trello'        => ['fab fa-trello',       '#0052CC'],
    'jira'          => ['fab fa-jira',         '#0052CC'],
    'github'        => ['fab fa-github',       '#24292F'],
    'gitlab'        => ['fab fa-gitlab',       '#FC6D26'],
    'salesforce'    => ['fab fa-salesforce',   '#00A1E0'],
    'hubspot'       => ['fab fa-hubspot',      '#FF7A59'],
    'dropbox'       => ['fab fa-dropbox',      '#0061FF'],
    'shopify'       => ['fab fa-shopify',      '#96BF48'],
    'wordpress'     => ['fab fa-wordpress',    '#21759B'],
    'figma'         => ['fab fa-figma',        '#F24E1E'],
    'notion'        => ['fas fa-n',            '#000000'],
    // Personal
    'gmail'         => ['fab fa-google',       '#EA4335'],
    'outlook'       => ['fab fa-microsoft',    '#0078D4'],
    'yahoo mail'    => ['fab fa-yahoo',        '#720E9E'],
    'protonmail'    => ['fas fa-envelope-open-text','#6D4AFF'],
    'icloud'        => ['fab fa-apple',        '#3F8AE0'],
    'google drive'  => ['fab fa-google-drive', '#4285F4'],
    'apple id'      => ['fab fa-apple',        '#555555'],
    'epic games'    => ['fas fa-gamepad',      '#2F2F2F'],
    'steam'         => ['fab fa-steam',        '#1B2838'],
    'nintendo'      => ['fas fa-gamepad',      '#E4000F'],
    'playstation'   => ['fab fa-playstation',  '#003087'],
    'canva'         => ['fas fa-palette',      '#00C4CC'],
    'pinterest'     => ['fab fa-pinterest',    '#E60023'],
    'reddit'        => ['fab fa-reddit',       '#FF4500'],
    // Finance
    'bdo'           => ['fas fa-university',   '#003087'],
    'bpi'           => ['fas fa-landmark',     '#003087'],
    'metrobank'     => ['fas fa-university',   '#003087'],
    'security bank' => ['fas fa-shield-halved','#C8102E'],
    'rcbc'          => ['fas fa-chart-line',   '#0033A0'],
    'unionbank'     => ['fas fa-building-columns','#F5A800'],
    'chinabank'     => ['fas fa-yin-yang',     '#C8102E'],
    'eastwest'      => ['fas fa-globe-asia',   '#1B4596'],
    'psbank'        => ['fas fa-hand-holding-dollar','#003087'],
    'landbank'      => ['fas fa-leaf',         '#006B3F'],
    'pnb'           => ['fas fa-landmark',     '#003087'],
    'gcash'         => ['fas fa-mobile-screen-button','#007DFF'],
    'maya'          => ['fas fa-mobile-screen-button','#2ECAD5'],
    'paypal'        => ['fab fa-paypal',       '#003087'],
    'coins.ph'      => ['fas fa-coins',        '#F7B731'],
    'binance'       => ['fab fa-bitcoin',      '#F0B90B'],
    // Social
    'facebook'      => ['fab fa-facebook',     '#1877F2'],
    'instagram'     => ['fab fa-instagram',    '#E1306C'],
    'twitter'       => ['fab fa-x-twitter',    '#000000'],
    'tiktok'        => ['fab fa-tiktok',       '#010101'],
    'linkedin'      => ['fab fa-linkedin',     '#0A66C2'],
    'youtube'       => ['fab fa-youtube',      '#FF0000'],
    'snapchat'      => ['fab fa-snapchat',     '#FFFC00'],
    'telegram'      => ['fab fa-telegram',     '#26A5E4'],
    'whatsapp'      => ['fab fa-whatsapp',     '#25D366'],
    'discord'       => ['fab fa-discord',      '#5865F2'],
    'twitch'        => ['fab fa-twitch',       '#9146FF'],
    'viber'         => ['fab fa-viber',        '#7360F2'],
    'messenger'     => ['fab fa-facebook-messenger','#0084FF'],
];

function getSiteData($name) {
    global $iconMap;
    $lower = strtolower(trim($name));
    if (isset($iconMap[$lower])) return $iconMap[$lower];
    foreach ($iconMap as $key => [$cls, $color]) {
        if (strpos($lower, $key) !== false) return [$cls, $color];
    }
    return ['fas fa-key', '#64748B'];
}

$sitesByCategory = [
    'Work' => [
        ['name'=>'Microsoft 365','icon'=>'fab fa-microsoft','color'=>'#0078D4','url'=>'https://www.office.com'],
        ['name'=>'Google Workspace','icon'=>'fab fa-google','color'=>'#4285F4','url'=>'https://workspace.google.com'],
        ['name'=>'Slack','icon'=>'fab fa-slack','color'=>'#4A154B','url'=>'https://slack.com'],
        ['name'=>'Teams','icon'=>'fab fa-microsoft','color'=>'#6264A7','url'=>'https://teams.microsoft.com'],
        ['name'=>'Zoom','icon'=>'fas fa-video','color'=>'#2D8CFF','url'=>'https://zoom.us'],
        ['name'=>'Asana','icon'=>'fas fa-list-check','color'=>'#F06A6A','url'=>'https://asana.com'],
        ['name'=>'Trello','icon'=>'fab fa-trello','color'=>'#0052CC','url'=>'https://trello.com'],
        ['name'=>'Jira','icon'=>'fab fa-jira','color'=>'#0052CC','url'=>'https://www.atlassian.com/software/jira'],
        ['name'=>'GitHub','icon'=>'fab fa-github','color'=>'#24292F','url'=>'https://github.com'],
        ['name'=>'GitLab','icon'=>'fab fa-gitlab','color'=>'#FC6D26','url'=>'https://gitlab.com'],
        ['name'=>'Salesforce','icon'=>'fab fa-salesforce','color'=>'#00A1E0','url'=>'https://www.salesforce.com'],
        ['name'=>'HubSpot','icon'=>'fab fa-hubspot','color'=>'#FF7A59','url'=>'https://www.hubspot.com'],
        ['name'=>'Dropbox','icon'=>'fab fa-dropbox','color'=>'#0061FF','url'=>'https://www.dropbox.com'],
        ['name'=>'Shopify','icon'=>'fab fa-shopify','color'=>'#96BF48','url'=>'https://www.shopify.com'],
        ['name'=>'WordPress','icon'=>'fab fa-wordpress','color'=>'#21759B','url'=>'https://wordpress.com'],
        ['name'=>'Figma','icon'=>'fab fa-figma','color'=>'#F24E1E','url'=>'https://www.figma.com'],
        ['name'=>'Notion','icon'=>'fas fa-book-open','color'=>'#000000','url'=>'https://www.notion.so'],
    ],
    'Personal' => [
        ['name'=>'Gmail','icon'=>'fab fa-google','color'=>'#EA4335','url'=>'https://mail.google.com'],
        ['name'=>'Outlook','icon'=>'fab fa-microsoft','color'=>'#0078D4','url'=>'https://outlook.live.com'],
        ['name'=>'Yahoo Mail','icon'=>'fab fa-yahoo','color'=>'#720E9E','url'=>'https://mail.yahoo.com'],
        ['name'=>'ProtonMail','icon'=>'fas fa-envelope-open-text','color'=>'#6D4AFF','url'=>'https://protonmail.com'],
        ['name'=>'iCloud','icon'=>'fab fa-apple','color'=>'#3F8AE0','url'=>'https://www.icloud.com'],
        ['name'=>'Google Drive','icon'=>'fab fa-google-drive','color'=>'#4285F4','url'=>'https://drive.google.com'],
        ['name'=>'Apple ID','icon'=>'fab fa-apple','color'=>'#555555','url'=>'https://appleid.apple.com'],
        ['name'=>'Epic Games','icon'=>'fas fa-gamepad','color'=>'#2F2F2F','url'=>'https://www.epicgames.com'],
        ['name'=>'Steam','icon'=>'fab fa-steam','color'=>'#1B2838','url'=>'https://store.steampowered.com'],
        ['name'=>'Nintendo','icon'=>'fas fa-gamepad','color'=>'#E4000F','url'=>'https://accounts.nintendo.com'],
        ['name'=>'PlayStation','icon'=>'fab fa-playstation','color'=>'#003087','url'=>'https://www.playstation.com'],
        ['name'=>'Canva','icon'=>'fas fa-palette','color'=>'#00C4CC','url'=>'https://www.canva.com'],
        ['name'=>'Pinterest','icon'=>'fab fa-pinterest','color'=>'#E60023','url'=>'https://www.pinterest.com'],
        ['name'=>'Reddit','icon'=>'fab fa-reddit','color'=>'#FF4500','url'=>'https://www.reddit.com'],
    ],
    'Finance' => [
        ['name'=>'BDO Unibank','icon'=>'fas fa-university','color'=>'#003087','url'=>'https://www.bdo.com.ph'],
        ['name'=>'BPI','icon'=>'fas fa-landmark','color'=>'#003087','url'=>'https://www.bpi.com.ph'],
        ['name'=>'Metrobank','icon'=>'fas fa-university','color'=>'#003087','url'=>'https://www.metrobank.com.ph'],
        ['name'=>'Security Bank','icon'=>'fas fa-shield-halved','color'=>'#C8102E','url'=>'https://www.securitybank.com'],
        ['name'=>'RCBC','icon'=>'fas fa-chart-line','color'=>'#0033A0','url'=>'https://www.rcbc.com'],
        ['name'=>'UnionBank','icon'=>'fas fa-building-columns','color'=>'#F5A800','url'=>'https://www.unionbankph.com'],
        ['name'=>'Chinabank','icon'=>'fas fa-yin-yang','color'=>'#C8102E','url'=>'https://www.chinabank.ph'],
        ['name'=>'EastWest Bank','icon'=>'fas fa-globe-asia','color'=>'#1B4596','url'=>'https://www.eastwestbanker.com'],
        ['name'=>'PSBank','icon'=>'fas fa-hand-holding-dollar','color'=>'#003087','url'=>'https://www.psbank.com.ph'],
        ['name'=>'Landbank','icon'=>'fas fa-leaf','color'=>'#006B3F','url'=>'https://www.landbank.com'],
        ['name'=>'PNB','icon'=>'fas fa-landmark','color'=>'#003087','url'=>'https://www.pnb.com.ph'],
        ['name'=>'GCash','icon'=>'fas fa-mobile-screen-button','color'=>'#007DFF','url'=>'https://www.gcash.com'],
        ['name'=>'Maya','icon'=>'fas fa-mobile-screen-button','color'=>'#2ECAD5','url'=>'https://www.maya.ph'],
        ['name'=>'PayPal','icon'=>'fab fa-paypal','color'=>'#003087','url'=>'https://www.paypal.com'],
        ['name'=>'Coins.ph','icon'=>'fas fa-coins','color'=>'#F7B731','url'=>'https://coins.ph'],
        ['name'=>'Binance','icon'=>'fab fa-bitcoin','color'=>'#F0B90B','url'=>'https://www.binance.com'],
    ],
    'Social' => [
        ['name'=>'Facebook','icon'=>'fab fa-facebook','color'=>'#1877F2','url'=>'https://www.facebook.com'],
        ['name'=>'Instagram','icon'=>'fab fa-instagram','color'=>'#E1306C','url'=>'https://www.instagram.com'],
        ['name'=>'Twitter / X','icon'=>'fab fa-x-twitter','color'=>'#000000','url'=>'https://twitter.com'],
        ['name'=>'TikTok','icon'=>'fab fa-tiktok','color'=>'#010101','url'=>'https://www.tiktok.com'],
        ['name'=>'LinkedIn','icon'=>'fab fa-linkedin','color'=>'#0A66C2','url'=>'https://www.linkedin.com'],
        ['name'=>'YouTube','icon'=>'fab fa-youtube','color'=>'#FF0000','url'=>'https://www.youtube.com'],
        ['name'=>'Snapchat','icon'=>'fab fa-snapchat','color'=>'#FFFC00','url'=>'https://www.snapchat.com'],
        ['name'=>'Telegram','icon'=>'fab fa-telegram','color'=>'#26A5E4','url'=>'https://telegram.org'],
        ['name'=>'WhatsApp','icon'=>'fab fa-whatsapp','color'=>'#25D366','url'=>'https://web.whatsapp.com'],
        ['name'=>'Discord','icon'=>'fab fa-discord','color'=>'#5865F2','url'=>'https://discord.com'],
        ['name'=>'Twitch','icon'=>'fab fa-twitch','color'=>'#9146FF','url'=>'https://www.twitch.tv'],
        ['name'=>'Viber','icon'=>'fab fa-viber','color'=>'#7360F2','url'=>'https://www.viber.com'],
        ['name'=>'Messenger','icon'=>'fab fa-facebook-messenger','color'=>'#0084FF','url'=>'https://www.messenger.com'],
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vault — <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-w: 260px;
            --accent: #10B981;
            --accent-dark: #059669;
            --accent-light: #D1FAE5;
            --navy: #0F172A;
            --navy-mid: #1E293B;
            --slate: #64748B;
            --border: #E2E8F0;
            --surface: #F8FAFC;
            --white: #ffffff;
            --text: #0F172A;
            --text-muted: #64748B;
            --danger: #EF4444;
            --info: #3B82F6;
            --warn: #F59E0B;
            --radius: 14px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
            --shadow-md: 0 4px 16px rgba(0,0,0,.08);
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background: var(--surface); font-family:'Plus Jakarta Sans',sans-serif; color:var(--text); }

        .sidebar {
            position: fixed; top:0; left:0; height:100vh; width:var(--sidebar-w);
            background: var(--navy); display:flex; flex-direction:column;
            z-index: 1000; transition: transform .3s cubic-bezier(.4,0,.2,1);
            box-shadow: 4px 0 24px rgba(0,0,0,.15);
        }
        .sidebar-overlay {
            display:none; position:fixed; inset:0; background:rgba(0,0,0,.5);
            z-index:999; backdrop-filter:blur(2px);
        }
        .sidebar-logo {
            padding: 1.5rem 1.25rem 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,.07);
            display:flex; align-items:center; gap:10px;
        }
        .logo-icon {
            width:38px; height:38px; background:linear-gradient(135deg,var(--accent),var(--accent-dark));
            border-radius:10px; display:flex; align-items:center; justify-content:center;
            flex-shrink:0;
        }
        .logo-icon i { color:#fff; font-size:1rem; }
        .logo-text h3 { color:#fff; font-size:1.05rem; font-weight:800; letter-spacing:-.3px; line-height:1.1; }
        .logo-text span { color:var(--slate); font-size:.7rem; font-weight:500; }

        .sidebar-nav { flex:1; overflow-y:auto; padding:.75rem 0; }
        .sidebar-nav::-webkit-scrollbar { width:0; }
        .nav-section-label {
            padding:.5rem 1.25rem .25rem;
            color:rgba(255,255,255,.25); font-size:.65rem; font-weight:700;
            letter-spacing:1px; text-transform:uppercase;
        }
        .nav-item {
            display:flex; align-items:center; gap:10px;
            padding:.7rem 1.25rem; margin:.1rem .75rem; border-radius:10px;
            color:rgba(255,255,255,.55); text-decoration:none;
            font-size:.84rem; font-weight:500; transition:all .2s;
            position:relative;
        }
        .nav-item i { width:18px; font-size:.9rem; text-align:center; flex-shrink:0; }
        .nav-item:hover { background:rgba(255,255,255,.06); color:rgba(255,255,255,.9); }
        .nav-item.active {
            background:rgba(16,185,129,.12); color:var(--accent);
            box-shadow: inset 3px 0 0 var(--accent);
            margin-left:.75rem;
        }
        .nav-item .nav-badge {
            margin-left:auto; background:var(--accent); color:#fff;
            font-size:.6rem; font-weight:700; padding:2px 7px; border-radius:20px;
        }
        .sidebar-divider { border-color:rgba(255,255,255,.07); margin:.5rem 1rem; }
        .sidebar-footer {
            padding:1rem 1.25rem;
            border-top:1px solid rgba(255,255,255,.07);
        }
        .sidebar-user {
            display:flex; align-items:center; gap:10px;
        }
        .user-avatar {
            width:34px; height:34px; background:linear-gradient(135deg,var(--accent),var(--accent-dark));
            border-radius:50%; display:flex; align-items:center; justify-content:center;
            color:#fff; font-size:.8rem; font-weight:700; flex-shrink:0;
        }
        .user-info p { color:rgba(255,255,255,.85); font-size:.78rem; font-weight:600; }
        .user-info span { color:var(--slate); font-size:.68rem; }

        .main-wrap {
            margin-left: var(--sidebar-w);
            min-height: 100vh; padding:1.5rem;
            transition: margin-left .3s cubic-bezier(.4,0,.2,1);
        }

        .topbar {
            background:var(--white); border-radius:var(--radius); padding:.9rem 1.25rem;
            margin-bottom:1.25rem; display:flex; justify-content:space-between; align-items:center;
            border:1px solid var(--border); box-shadow:var(--shadow-sm);
        }
        .topbar-left { display:flex; align-items:center; gap:.75rem; }
        .mobile-menu-btn {
            display:none; background:none; border:1px solid var(--border);
            border-radius:9px; padding:.4rem .55rem; cursor:pointer; color:var(--slate);
            font-size:1rem; transition:all .2s;
        }
        .mobile-menu-btn:hover { background:var(--surface); color:var(--text); }
        .page-title h1 { font-size:1.3rem; font-weight:800; color:var(--text); letter-spacing:-.3px; }
        .page-title p { font-size:.75rem; color:var(--text-muted); margin-top:1px; }
        .btn-add {
            background:var(--accent); color:#fff; border:none; border-radius:10px;
            padding:.55rem 1.1rem; font-size:.82rem; font-weight:700;
            display:flex; align-items:center; gap:.4rem; cursor:pointer;
            transition:all .2s; white-space:nowrap;
        }
        .btn-add:hover { background:var(--accent-dark); transform:translateY(-1px); box-shadow:0 4px 12px rgba(16,185,129,.3); }

        .filter-bar {
            background:var(--white); border-radius:var(--radius); padding:1rem 1.25rem;
            margin-bottom:1.25rem; border:1px solid var(--border); box-shadow:var(--shadow-sm);
        }
        .filter-bar .row { align-items:center; }
        .search-wrap { position:relative; }
        .search-wrap i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--slate); font-size:.85rem; }
        .search-wrap input { padding-left:36px; border-radius:10px; border:1px solid var(--border); font-size:.83rem; width:100%; height:40px; }
        .search-wrap input:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px rgba(16,185,129,.1); }
        .form-select { border-radius:10px; border:1px solid var(--border); font-size:.83rem; height:40px; }
        .form-select:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(16,185,129,.1); }
        .count-pill {
            background:var(--surface); border:1px solid var(--border); border-radius:20px;
            padding:4px 14px; font-size:.75rem; font-weight:600; color:var(--slate);
            display:inline-flex; align-items:center; gap:5px;
        }
        .count-pill i { color:var(--accent); }

        .vault-card {
            background:var(--white); border-radius:var(--radius);
            border:1px solid var(--border); box-shadow:var(--shadow-sm); overflow:hidden;
        }
        .vault-header {
            padding:.85rem 1.25rem; background:var(--surface);
            border-bottom:1px solid var(--border);
            display:flex; justify-content:space-between; align-items:center;
        }
        .vault-header h3 { font-size:.9rem; font-weight:700; color:var(--text); margin:0; }
        .vault-header h3 i { color:var(--accent); margin-right:7px; }
        .vault-scroll {
            max-height: calc(100vh - 280px); overflow-y:auto; padding:1.25rem;
        }
        .vault-scroll::-webkit-scrollbar { width:5px; }
        .vault-scroll::-webkit-scrollbar-track { background:transparent; }
        .vault-scroll::-webkit-scrollbar-thumb { background:var(--accent); border-radius:10px; }

        .cred-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; }

        .cred-card {
            background:var(--white); border:1px solid var(--border); border-radius:16px;
            padding:1.1rem; transition:all .25s cubic-bezier(.4,0,.2,1);
            position:relative; overflow:hidden;
            cursor: pointer;
        }
        .cred-card::after {
            content:''; position:absolute; inset:0; border-radius:16px;
            box-shadow: 0 0 0 1.5px var(--accent); opacity:0; transition:opacity .2s;
            pointer-events: none;
        }
        .cred-card:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,.09); }
        .cred-card:hover::after { opacity:1; }

        .cred-card .accent-bar {
            position:absolute; left:0; top:0; bottom:0; width:4px; border-radius:16px 0 0 16px;
        }

        .card-top { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:.75rem; }

        .brand-icon {
            width:46px; height:46px; border-radius:13px;
            display:flex; align-items:center; justify-content:center;
            font-size:1.35rem; flex-shrink:0; transition:transform .2s;
        }
        .cred-card:hover .brand-icon { transform:scale(1.08); }

        .cat-pill {
            background:var(--surface); border:1px solid var(--border);
            border-radius:20px; padding:3px 9px; font-size:.6rem; font-weight:700;
            color:var(--text); display:inline-flex; align-items:center; gap:4px;
        }
        .cat-pill i { font-size:.55rem; color:var(--accent); }

        .cred-name { font-weight:800; font-size:.92rem; color:var(--text); margin-bottom:.2rem; letter-spacing:-.2px; }
        .cred-url { font-size:.63rem; color:var(--text-muted); display:flex; align-items:center; gap:4px; margin-bottom:.7rem; word-break:break-all; }

        .info-chip {
            background:var(--surface); border-radius:10px; padding:.5rem .65rem; margin-bottom:.55rem;
        }
        .chip-label { font-size:.58rem; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:var(--slate); margin-bottom:.15rem; }
        .chip-value { font-size:.78rem; color:var(--text); font-weight:500; word-break:break-all; }

        .pwd-chip {
            background:var(--surface); border-radius:10px; padding:.5rem .65rem;
            margin-bottom:.65rem; display:flex; justify-content:space-between; align-items:center;
        }
        .pwd-mask { font-family:monospace; font-size:.8rem; color:var(--text); }
        .pwd-clear { font-family:monospace; font-size:.8rem; color:var(--text); display:none; }

        .strength-tag { font-size:.6rem; font-weight:700; padding:3px 9px; border-radius:20px; display:inline-flex; align-items:center; gap:4px; }

        .card-actions { display:flex; gap:.45rem; }
        .act-btn {
            flex:1; height:32px; border-radius:9px; border:none; font-size:.67rem; font-weight:600;
            display:flex; align-items:center; justify-content:center; gap:4px; cursor:pointer;
            transition:all .18s;
        }
        .act-copy { background:rgba(16,185,129,.1); color:var(--accent); border:1px solid rgba(16,185,129,.15); }
        .act-copy:hover { background:var(--accent); color:#fff; }
        .act-view { background:rgba(99,102,241,.1); color:#6366F1; border:1px solid rgba(99,102,241,.15); }
        .act-view:hover { background:#6366F1; color:#fff; }
        .act-show { background:var(--surface); color:var(--slate); border:1px solid var(--border); }
        .act-show:hover { background:var(--navy); color:#fff; border-color:var(--navy); }
        .act-edit { background:rgba(59,130,246,.1); color:var(--info); border:1px solid rgba(59,130,246,.15); }
        .act-edit:hover { background:var(--info); color:#fff; }
        .act-del { background:rgba(239,68,68,.1); color:var(--danger); border:1px solid rgba(239,68,68,.15); }
        .act-del:hover { background:var(--danger); color:#fff; }

        .empty-state { grid-column:1/-1; text-align:center; padding:3.5rem 1rem; }
        .empty-icon { font-size:3rem; color:#CBD5E1; margin-bottom:1rem; }
        .empty-state h4 { color:var(--slate); font-weight:700; margin-bottom:.5rem; }
        .empty-state p { color:var(--text-muted); font-size:.85rem; }

        .modal-content { border-radius:20px; border:none; overflow:hidden; }
        .modal-header { background:var(--navy); color:#fff; padding:1.1rem 1.5rem; border:none; }
        .modal-header .modal-title { font-weight:800; font-size:1.05rem; letter-spacing:-.2px; }
        .modal-header .modal-title i { color:var(--accent); margin-right:8px; }
        .modal-body { padding:1.5rem; background:var(--surface); }
        .modal-footer { padding:.9rem 1.5rem; background:var(--white); border-top:1px solid var(--border); }

        .cat-picker { display:grid; grid-template-columns:repeat(4,1fr); gap:.6rem; margin-bottom:1.2rem; }
        .cat-opt {
            padding:.75rem .5rem; border-radius:12px; border:1.5px solid var(--border);
            background:var(--white); cursor:pointer; text-align:center;
            transition:all .2s; font-size:.75rem; font-weight:600; color:var(--slate);
        }
        .cat-opt i { display:block; font-size:1.3rem; margin-bottom:.35rem; color:var(--slate); }
        .cat-opt:hover { border-color:var(--accent); color:var(--accent); }
        .cat-opt:hover i { color:var(--accent); }
        .cat-opt.selected { background:var(--accent); border-color:var(--accent); color:#fff; }
        .cat-opt.selected i { color:#fff; }

        .site-picker {
            max-height:260px; overflow-y:auto; border:1px solid var(--border);
            border-radius:12px; padding:.4rem; background:var(--white);
        }
        .site-picker::-webkit-scrollbar { width:4px; }
        .site-picker::-webkit-scrollbar-thumb { background:var(--accent); border-radius:4px; }
        .site-opt {
            display:flex; align-items:center; gap:10px; padding:.6rem .85rem;
            border-radius:9px; cursor:pointer; font-size:.82rem; font-weight:500; transition:all .15s;
        }
        .site-opt:hover { background:var(--surface); }
        .site-opt.selected { background:var(--accent); color:#fff; }
        .site-opt.selected .site-opt-icon { color:#fff; }
        .site-opt-icon { width:22px; font-size:1rem; text-align:center; flex-shrink:0; }

        .form-control, .form-select {
            border-radius:10px; border:1px solid var(--border);
            padding:.55rem .85rem; font-size:.83rem; font-family:inherit;
        }
        .form-control:focus, .form-select:focus {
            border-color:var(--accent); box-shadow:0 0 0 3px rgba(16,185,129,.1); outline:none;
        }
        .label-sm { font-size:.78rem; font-weight:700; color:var(--text); margin-bottom:.4rem; display:block; }
        .label-sm i { color:var(--accent); margin-right:5px; }

        .btn-primary { background:var(--accent); border:none; border-radius:10px; padding:.55rem 1.25rem; font-size:.83rem; font-weight:700; color:#fff; cursor:pointer; transition:all .2s; }
        .btn-primary:hover { background:var(--accent-dark); }
        .btn-secondary { background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:.55rem 1.25rem; font-size:.83rem; font-weight:700; color:var(--slate); cursor:pointer; }

        .view-body { display:flex; gap:1.25rem; }
        .view-left {
            width:200px; flex-shrink:0; background:linear-gradient(160deg,var(--navy),var(--navy-mid));
            border-radius:14px; padding:1.5rem 1rem; text-align:center; color:#fff;
        }
        .view-icon { width:64px; height:64px; border-radius:18px; display:flex; align-items:center; justify-content:center; font-size:2rem; margin:0 auto .9rem; }
        .view-site-name { font-weight:800; font-size:1rem; letter-spacing:-.2px; margin-bottom:.3rem; }
        .view-cat { font-size:.7rem; color:rgba(255,255,255,.5); display:flex; align-items:center; justify-content:center; gap:4px; }
        .view-right { flex:1; }
        .view-row { display:flex; padding:.65rem 0; border-bottom:1px solid var(--border); }
        .view-row:last-child { border-bottom:none; }
        .view-lbl { width:100px; font-size:.76rem; font-weight:700; color:var(--slate); flex-shrink:0; }
        .view-val { flex:1; font-size:.82rem; color:var(--text); word-break:break-all; }
        .pwd-input-grp { display:flex; gap:.4rem; }
        .pwd-input-grp input { flex:1; border-radius:9px; border:1px solid var(--border); padding:6px 10px; font-size:.8rem; font-family:monospace; }
        .pwd-input-grp button { border-radius:9px; border:1px solid var(--border); background:var(--white); padding:6px 10px; cursor:pointer; transition:all .15s; }
        .pwd-input-grp button:hover { background:var(--accent); color:#fff; border-color:var(--accent); }

        .toast-msg {
            position:fixed; top:20px; right:20px; padding:10px 20px; border-radius:12px;
            color:#fff; font-size:.83rem; font-weight:600; z-index:9999;
            display:none; animation:slideInToast .3s ease; box-shadow:var(--shadow-md);
        }
        @keyframes slideInToast { from{transform:translateX(100%);opacity:0} to{transform:translateX(0);opacity:1} }

        @media (max-width:1200px) { .cred-grid { grid-template-columns:repeat(2,1fr); } }
        @media (max-width:768px) {
            .sidebar { transform:translateX(-100%); }
            .sidebar.open { transform:translateX(0); }
            .sidebar-overlay.open { display:block; }
            .main-wrap { margin-left:0; padding:1rem; }
            .mobile-menu-btn { display:flex; align-items:center; }
            .cred-grid { grid-template-columns:1fr; }
            .vault-scroll { max-height:calc(100vh - 240px); }
            .cat-picker { grid-template-columns:repeat(2,1fr); }
            .view-body { flex-direction:column; }
            .view-left { width:100%; }
            .topbar { padding:.75rem 1rem; }
        }
        @media (max-width:480px) {
            .btn-add span { display:none; }
            .filter-bar .col-md-4 { display:none; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon"><i class="fas fa-shield-halved"></i></div>
        <div class="logo-text">
            <h3>PM System</h3>
            <span>Enterprise Password Manager</span>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>
        <a href="dashboard.php" class="nav-item"><i class="fas fa-gauge-high"></i> Dashboard</a>
        <a href="vault.php" class="nav-item active"><i class="fas fa-lock"></i> Password Vault <span class="nav-badge"><?php echo count($passwords); ?></span></a>
        <div class="nav-section-label" style="margin-top:.5rem;">Manage</div>
        <a href="settings.php" class="nav-item"><i class="fas fa-gear"></i> Settings</a>
        <a href="logs.php" class="nav-item"><i class="fas fa-clock-rotate-left"></i> Activity Logs</a>
        <hr class="sidebar-divider">
        <a href="logout.php" class="nav-item" style="color:rgba(239,68,68,.7);"><i class="fas fa-right-from-bracket"></i> Logout</a>
    </nav>
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 2)); ?></div>
            <div class="user-info">
                <p><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></p>
                <span>Vault Manager</span>
            </div>
        </div>
    </div>
</aside>

<div class="main-wrap" id="mainWrap">
    <div class="topbar">
        <div class="topbar-left">
            <button class="mobile-menu-btn" onclick="openSidebar()" aria-label="Open menu"><i class="fas fa-bars"></i></button>
            <div class="page-title">
                <h1><i class="fas fa-vault" style="color:var(--accent);margin-right:8px;font-size:1.1rem;"></i>Password Vault</h1>
                <p>Click any credential card to view full details</p>
            </div>
        </div>
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> <span>Add Credential</span></button>
    </div>

    <div class="filter-bar">
        <div class="row g-2">
            <div class="col-12 col-md-5">
                <div class="search-wrap"><i class="fas fa-magnifying-glass"></i><input type="text" id="searchInput" placeholder="Search credentials…"></div>
            </div>
            <div class="col-6 col-md-3">
                <select id="categoryFilter" class="form-select">
                    <option value="">All Categories</option>
                    <option value="Work">💼 Work</option>
                    <option value="Personal">👤 Personal</option>
                    <option value="Finance">💰 Finance</option>
                    <option value="Social">📱 Social</option>
                </select>
            </div>
            <div class="col-6 col-md-4 d-flex align-items-center justify-content-end">
                <div class="count-pill"><i class="fas fa-database"></i> <span id="resultCount"><?php echo count($passwords); ?></span> entries</div>
            </div>
        </div>
    </div>

    <div class="vault-card">
        <div class="vault-header"><h3><i class="fas fa-key"></i>Saved Credentials</h3></div>
        <div class="vault-scroll">
            <div class="cred-grid" id="vaultContainer">
                <?php foreach($passwords as $item):
                    $decryptedPwd = Encryption::decrypt($item['encrypted_password'], $_SESSION['master_key']);
                    $strength = checkPasswordStrength($decryptedPwd);
                    $sc = $strength === 'Strong' ? ['#10B981','#D1FAE5'] : ($strength === 'Medium' ? ['#F59E0B','#FEF3C7'] : ['#EF4444','#FEE2E2']);
                    [$iconClass, $iconColor] = getSiteData($item['name']);
                    $iconBg = $iconColor . '1A';
                    $catMeta = [
                        'Work'     => ['fa-briefcase',   '#3B82F6'],
                        'Personal' => ['fa-user',         '#8B5CF6'],
                        'Finance'  => ['fa-chart-line',   '#10B981'],
                        'Social'   => ['fa-hashtag',      '#F59E0B'],
                    ];
                    [$catIcon, $catAccent] = $catMeta[$item['category']] ?? ['fa-folder','#64748B'];
                ?>
                <div class="cred-card" data-id="<?php echo $item['id']; ?>">
                    <div class="accent-bar" style="background:<?php echo $iconColor; ?>;"></div>
                    <div class="card-top">
                        <div class="brand-icon" style="background:<?php echo $iconBg; ?>;"><i class="<?php echo $iconClass; ?>" style="color:<?php echo $iconColor; ?>;"></i></div>
                        <div class="cat-pill"><i class="fas <?php echo $catIcon; ?>" style="color:<?php echo $catAccent; ?>;"></i><?php echo $item['category']; ?></div>
                    </div>
                    <div class="cred-name"><?php echo sanitizeOutput($item['name']); ?></div>
                    <?php if($item['url']): ?>
                    <div class="cred-url"><i class="fas fa-link"></i><span><?php echo sanitizeOutput($item['url']); ?></span></div>
                    <?php endif; ?>
                    <div class="info-chip"><div class="chip-label"><i class="fas fa-user" style="margin-right:3px;color:var(--accent);"></i>Username</div><div class="chip-value"><?php echo sanitizeOutput($item['username']); ?></div></div>
                    <div class="pwd-chip"><div><span class="pwd-mask">••••••••••••</span><span class="pwd-clear"><?php echo sanitizeOutput($decryptedPwd); ?></span></div><span class="strength-tag" style="background:<?php echo $sc[1]; ?>;color:<?php echo $sc[0]; ?>;"><i class="fas <?php echo $strength==='Strong'?'fa-circle-check':($strength==='Medium'?'fa-circle-half-stroke':'fa-circle-exclamation'); ?>"></i><?php echo $strength; ?></span></div>
                    <div class="card-actions">
                        <button class="act-btn act-view" data-action="view" data-id="<?php echo $item['id']; ?>"><i class="fas fa-eye"></i> View</button>
                        <button class="act-btn act-copy" data-action="copy" data-id="<?php echo $item['id']; ?>"><i class="fas fa-copy"></i> Copy</button>
                        <button class="act-btn act-edit" data-action="edit" data-id="<?php echo $item['id']; ?>"><i class="fas fa-pen"></i> Edit</button>
                        <button class="act-btn act-del" data-action="delete" data-id="<?php echo $item['id']; ?>"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(empty($passwords)): ?>
                <div class="empty-state"><div class="empty-icon"><i class="fas fa-vault"></i></div><h4>Your vault is empty</h4><p>Click <strong>Add Credential</strong> above to store your first password securely.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="fas fa-plus-circle"></i> Add New Credential</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form id="addForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <label class="label-sm"><i class="fas fa-tag"></i>Step 1 — Select Category</label>
                    <div class="cat-picker" id="addCatPicker">
                        <div class="cat-opt" data-cat="Work"><i class="fas fa-briefcase"></i>Work</div>
                        <div class="cat-opt" data-cat="Personal"><i class="fas fa-user"></i>Personal</div>
                        <div class="cat-opt" data-cat="Finance"><i class="fas fa-chart-line"></i>Finance</div>
                        <div class="cat-opt" data-cat="Social"><i class="fas fa-hashtag"></i>Social</div>
                    </div>
                    <input type="hidden" name="category" id="addSelCat" required>
                    <div id="addSiteWrap" style="display:none;margin-bottom:1rem;"><label class="label-sm"><i class="fas fa-globe"></i>Step 2 — Select Site / App</label><div class="site-picker" id="addSitePicker"></div><input type="hidden" name="name" id="addSelSite" required><input type="hidden" name="url" id="addSelUrl"></div>
                    <div id="addCredsWrap" style="display:none;"><div class="mb-3"><label class="label-sm"><i class="fas fa-user"></i>Username / Email</label><input type="text" name="username" id="addUsername" class="form-control" required></div><div class="mb-3"><label class="label-sm"><i class="fas fa-lock"></i>Password</label><div class="input-group"><input type="password" name="password" id="addPwd" class="form-control" required><button type="button" class="btn btn-outline-secondary" id="addGenPwd"><i class="fas fa-wand-magic-sparkles"></i></button></div><div id="addStrength" class="mt-1" style="font-size:.75rem;"></div></div><div class="mb-1"><label class="label-sm"><i class="fas fa-note-sticky"></i>Notes</label><textarea name="notes" class="form-control" rows="2" placeholder="Optional notes…"></textarea></div></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn-primary" id="addSubmitBtn" disabled>Save Credential</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="fas fa-pen-to-square"></i> Edit Credential</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form id="editForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="id" id="editId">
                    <label class="label-sm"><i class="fas fa-tag"></i>Step 1 — Select Category</label>
                    <div class="cat-picker" id="editCatPicker">
                        <div class="cat-opt" data-cat="Work"><i class="fas fa-briefcase"></i>Work</div>
                        <div class="cat-opt" data-cat="Personal"><i class="fas fa-user"></i>Personal</div>
                        <div class="cat-opt" data-cat="Finance"><i class="fas fa-chart-line"></i>Finance</div>
                        <div class="cat-opt" data-cat="Social"><i class="fas fa-hashtag"></i>Social</div>
                    </div>
                    <input type="hidden" name="category" id="editSelCat" required>
                    <div id="editSiteWrap" style="display:none;margin-bottom:1rem;"><label class="label-sm"><i class="fas fa-globe"></i>Step 2 — Select Site / App</label><div class="site-picker" id="editSitePicker"></div><input type="hidden" name="name" id="editSelSite" required><input type="hidden" name="url" id="editSelUrl"></div>
                    <div id="editCredsWrap" style="display:none;"><div class="mb-3"><label class="label-sm"><i class="fas fa-user"></i>Username / Email</label><input type="text" name="username" id="editUsername" class="form-control" required></div><div class="mb-3"><label class="label-sm"><i class="fas fa-lock"></i>Password</label><div class="input-group"><input type="password" name="password" id="editPwd" class="form-control" required><button type="button" class="btn btn-outline-secondary" id="editGenPwd"><i class="fas fa-wand-magic-sparkles"></i></button></div><div id="editStrength" class="mt-1" style="font-size:.75rem;"></div></div><div class="mb-1"><label class="label-sm"><i class="fas fa-note-sticky"></i>Notes</label><textarea name="notes" id="editNotes" class="form-control" rows="2"></textarea></div></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn-primary" id="editSubmitBtn" disabled>Update Credential</button></div>
            </form>
        </div>
    </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:720px;">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="fas fa-circle-info"></i> Credential Details</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="viewBody"></div>
            <div class="modal-footer"><button type="button" class="btn-secondary" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

<div id="toastMsg" class="toast-msg"></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sitesByCat = <?php echo json_encode($sitesByCategory); ?>;

// CREDENTIALS DATA stored server-side for each ID
const CREDS = <?php
    $stmt2 = $pdo->prepare("SELECT * FROM passwords WHERE user_id = ? ORDER BY created_at DESC");
    $stmt2->execute([$_SESSION['user_id']]);
    $allCreds = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    $credData = [];
    foreach ($allCreds as $row) {
        $decPwd = Encryption::decrypt($row['encrypted_password'], $_SESSION['master_key']);
        [$ic, $icCol] = getSiteData($row['name']);
        $str = checkPasswordStrength($decPwd);
        $credData[$row['id']] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'url' => $row['url'] ?? '',
            'username' => $row['username'],
            'password' => $decPwd,
            'notes' => $row['notes'] ?? '',
            'category' => $row['category'],
            'strength' => $str,
            'icon' => $ic,
            'iconColor' => $icCol,
        ];
    }
    echo json_encode($credData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>;

function openSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('sidebarOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('open');
    document.body.style.overflow = '';
}
function toast(msg, ok=true) {
    const t = $('#toastMsg');
    t.css('background', ok ? '#10B981' : '#EF4444').text(msg).fadeIn(250);
    setTimeout(() => t.fadeOut(300), 2800);
}
function escHtml(str) { if (!str) return ''; const div = document.createElement('div'); div.textContent = str; return div.innerHTML; }

// View credential by ID
function viewCredential(id) {
    const c = CREDS[id];
    if (!c) return;
    const col = c.iconColor || '#64748B';
    const sc = c.strength === 'Strong' ? ['#10B981','#D1FAE5'] : (c.strength === 'Medium' ? ['#F59E0B','#FEF3C7'] : ['#EF4444','#FEE2E2']);
    const catIconMap = {Work:'fa-briefcase', Personal:'fa-user', Finance:'fa-chart-line', Social:'fa-hashtag'};
    const catIcon = catIconMap[c.category] || 'fa-folder';
    $('#viewBody').html(`
        <div class="view-body"><div class="view-left"><div class="view-icon" style="background:${col}22;"><i class="${c.icon}" style="color:${col};font-size:2rem;"></i></div><div class="view-site-name">${escHtml(c.name)}</div><div class="view-cat"><i class="fas ${catIcon}"></i> ${escHtml(c.category)}</div><div style="margin-top:.85rem;"><span style="background:${sc[1]};color:${sc[0]};font-size:.68rem;font-weight:700;padding:4px 12px;border-radius:20px;">${c.strength}</span></div></div><div class="view-right"><div class="view-row"><div class="view-lbl"><i class="fas fa-link" style="color:var(--accent);margin-right:4px;"></i>URL</div><div class="view-val">${c.url ? `<a href="${escHtml(c.url)}" target="_blank" style="color:var(--accent);">${escHtml(c.url)}</a>` : '<span style="color:var(--text-muted);">Not provided</span>'}</div></div><div class="view-row"><div class="view-lbl"><i class="fas fa-user" style="color:var(--accent);margin-right:4px;"></i>Username</div><div class="view-val">${escHtml(c.username)}</div></div><div class="view-row"><div class="view-lbl"><i class="fas fa-lock" style="color:var(--accent);margin-right:4px;"></i>Password</div><div class="view-val"><div class="pwd-input-grp"><input type="password" id="vpwd" value="" readonly style="background:#F8FAFC;"><button type="button" onclick="var i=document.getElementById('vpwd');i.type=i.type==='password'?'text':'password';"><i class="fas fa-eye"></i></button><button type="button" id="vpwd-copy-btn"><i class="fas fa-copy"></i></button></div></div></div><div class="view-row"><div class="view-lbl"><i class="fas fa-note-sticky" style="color:var(--accent);margin-right:4px;"></i>Notes</div><div class="view-val">${escHtml(c.notes) || 'No notes provided'}</div></div></div></div>
    `);
    const pwdField = document.getElementById('vpwd');
    if (pwdField) pwdField.value = c.password;
    const copyBtn = document.getElementById('vpwd-copy-btn');
    if (copyBtn) copyBtn.onclick = function() { navigator.clipboard.writeText(c.password).then(() => toast('Copied!')); };
    bootstrap.Modal.getOrCreateInstance(document.getElementById('viewModal')).show();
}

function copyPassword(id) {
    const c = CREDS[id];
    if (c) navigator.clipboard.writeText(c.password).then(() => toast('Password copied!'));
}

function editCredential(id) {
    const c = CREDS[id];
    if (!c) return;
    $('#editId').val(c.id);
    $('#editSelCat').val(c.category);
    $('#editSelSite').val(c.name);
    $('#editSelUrl').val(c.url || '');
    $('#editUsername').val(c.username);
    $('#editPwd').val(c.password);
    $('#editNotes').val(c.notes || '');
    const [lbl, col] = strengthCheck(c.password);
    $('#editStrength').html(`Strength: <span style="color:${col};font-weight:700;">${lbl}</span>`);
    $('#editCatPicker .cat-opt').removeClass('selected').each(function() { if ($(this).data('cat') === c.category) $(this).addClass('selected'); });
    $('#editSitePicker').html(buildSitePicker('editSitePicker', c.category, c.name));
    $('#editSiteWrap').show();
    $('#editCredsWrap').show();
    $('#editSubmitBtn').prop('disabled', false);
    bindSiteOpts('#editSitePicker', '#editSelSite', '#editSelUrl', '#editCredsWrap', '#editSubmitBtn');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('editModal')).show();
}

function deleteCredential(id) {
    const c = CREDS[id];
    if (!c) return;
    if (!confirm('Permanently delete "' + c.name + '"?')) return;
    $.post('ajax_delete.php', { id: c.id }, 'json')
        .done(r => { if (r.success) { toast('Deleted!'); setTimeout(() => location.reload(), 900); } else toast(r.message || 'Delete failed', false); })
        .fail(() => toast('Delete failed', false));
}

// Helper functions
function strengthCheck(pwd) {
    let s = 0;
    if (pwd.length >= 8) s++;
    if (pwd.length >= 12) s++;
    if (/[A-Z]/.test(pwd)) s++;
    if (/[a-z]/.test(pwd)) s++;
    if (/\d/.test(pwd)) s++;
    if (/[^A-Za-z0-9]/.test(pwd)) s++;
    return s <= 2 ? ['Weak', '#EF4444'] : s <= 4 ? ['Medium', '#F59E0B'] : ['Strong', '#10B981'];
}
function renderStrength(id, pwd) {
    const [lbl, col] = strengthCheck(pwd);
    $(`#${id}`).html(`Strength: <span style="color:${col};font-weight:700;">${lbl}</span>`);
}
function genPassword() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
    return Array.from({length:16}, () => chars[Math.floor(Math.random() * chars.length)]).join('');
}
function buildSitePicker(containerId, cat, selectedName) {
    const sites = sitesByCat[cat] || [];
    return sites.map(s => {
        const sel = s.name === selectedName ? 'selected' : '';
        return `<div class="site-opt ${sel}" data-name="${s.name}" data-url="${s.url}" data-icon="${s.icon}" data-color="${s.color}"><i class="site-opt-icon ${s.icon}" style="color:${sel?'#fff':s.color};"></i><span>${s.name}</span></div>`;
    }).join('');
}
function bindSiteOpts(picker, siteInput, urlInput, credsWrap, submitBtn) {
    $(`${picker} .site-opt`).off('click').on('click', function() {
        $(`${picker} .site-opt`).removeClass('selected').each(function() { $(this).find('.site-opt-icon').css('color', $(this).data('color')); });
        $(this).addClass('selected').find('.site-opt-icon').css('color','#fff');
        $(siteInput).val($(this).data('name'));
        $(urlInput).val($(this).data('url'));
        $(credsWrap).show();
        $(submitBtn).prop('disabled', false);
    });
}

// BUTTON EVENT HANDLERS - Using event delegation to ensure clicks work
$(document).on('click', '.act-view', function(e) {
    e.preventDefault();
    e.stopPropagation();
    const id = $(this).data('id');
    if (id) viewCredential(id);
});
$(document).on('click', '.act-copy', function(e) {
    e.preventDefault();
    e.stopPropagation();
    const id = $(this).data('id');
    if (id) copyPassword(id);
});
$(document).on('click', '.act-edit', function(e) {
    e.preventDefault();
    e.stopPropagation();
    const id = $(this).data('id');
    if (id) editCredential(id);
});
$(document).on('click', '.act-del', function(e) {
    e.preventDefault();
    e.stopPropagation();
    const id = $(this).data('id');
    if (id) deleteCredential(id);
});
// Card click for view (when clicking anywhere on card except buttons)
$(document).on('click', '.cred-card', function(e) {
    if ($(e.target).closest('.act-btn').length) return;
    const id = $(this).data('id');
    if (id) viewCredential(id);
});

// ADD MODAL handlers
$('#addCatPicker .cat-opt').click(function() {
    $('#addCatPicker .cat-opt').removeClass('selected');
    $(this).addClass('selected');
    const cat = $(this).data('cat');
    $('#addSelCat').val(cat);
    $('#addSitePicker').html(buildSitePicker('addSitePicker', cat, ''));
    $('#addSiteWrap').show();
    $('#addCredsWrap').hide();
    $('#addSubmitBtn').prop('disabled', true);
    bindSiteOpts('#addSitePicker', '#addSelSite', '#addSelUrl', '#addCredsWrap', '#addSubmitBtn');
});
$('#editCatPicker .cat-opt').on('click', function() {
    $('#editCatPicker .cat-opt').removeClass('selected');
    $(this).addClass('selected');
    const cat = $(this).data('cat');
    $('#editSelCat').val(cat);
    $('#editSitePicker').html(buildSitePicker('editSitePicker', cat, ''));
    $('#editSelSite').val('');
    $('#editSelUrl').val('');
    $('#editSiteWrap').show();
    $('#editCredsWrap').hide();
    $('#editSubmitBtn').prop('disabled', true);
    bindSiteOpts('#editSitePicker', '#editSelSite', '#editSelUrl', '#editCredsWrap', '#editSubmitBtn');
});
$('#addPwd, #editPwd').on('input', function() { renderStrength(this.id==='addPwd'?'addStrength':'editStrength', this.value); });
$('#addGenPwd').click(() => { const p = genPassword(); $('#addPwd').val(p); renderStrength('addStrength', p); });
$('#editGenPwd').click(() => { const p = genPassword(); $('#editPwd').val(p); renderStrength('editStrength', p); });

// Form submits
$('#addForm').submit(function(e) {
    e.preventDefault();
    $.post('ajax_add.php', $(this).serialize()).done(r => { try { r = typeof r === 'string' ? JSON.parse(r) : r; } catch(e){} if(r.success){ toast('Credential saved!'); setTimeout(()=>location.reload(),900); } else toast(r.message||'Error',false); }).fail(()=>toast('Save failed',false));
});
$('#editForm').submit(function(e) {
    e.preventDefault();
    $.post('ajax_edit.php', $(this).serialize()).done(r => { try { r = typeof r === 'string' ? JSON.parse(r) : r; } catch(e){} if(r.success){ toast('Updated!'); setTimeout(()=>location.reload(),900); } else toast(r.message||'Error',false); }).fail(()=>toast('Update failed',false));
});

// Search/filter
$('#searchInput, #categoryFilter').on('input change', function() {
    const q = $('#searchInput').val().toLowerCase();
    const cat = $('#categoryFilter').val();
    let n = 0;
    $('.cred-card').each(function() {
        const nm = $(this).find('.cred-name').text().toLowerCase();
        const c = $(this).data('category');
        const show = (!q || nm.includes(q)) && (!cat || c === cat);
        $(this).toggle(show);
        if(show) n++;
    });
    $('#resultCount').text(n);
    $('.no-results-msg').remove();
    if (n === 0 && $('.cred-card').length) $('#vaultContainer').append('<div class="empty-state no-results-msg" style="grid-column:1/-1;"><div class="empty-icon"><i class="fas fa-magnifying-glass"></i></div><h4>No matches</h4><p>Try a different search term or category.</p></div>');
});

// Modal reset
$('#addModal').on('hidden.bs.modal', function() { $('#addCatPicker .cat-opt').removeClass('selected'); $('#addSiteWrap, #addCredsWrap').hide(); $('#addSubmitBtn').prop('disabled', true); $(this).find('form')[0].reset(); $('#addStrength').html(''); });
$('#editModal').on('hidden.bs.modal', function() { $('#editCatPicker .cat-opt').removeClass('selected'); $('#editSiteWrap, #editCredsWrap').hide(); $('#editSubmitBtn').prop('disabled', true); $('#editStrength').html(''); $(this).find('form')[0].reset(); });

// Set category data attribute on cards for filtering
$('.cred-card').each(function() {
    const card = $(this);
    const cat = card.find('.cat-pill').text().trim();
    card.attr('data-category', cat);
});
</script>
</body>
</html>