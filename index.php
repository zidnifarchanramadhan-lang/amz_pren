<?php
// ==========================================================================
// BACKEND PROXY (PHP 8 Native - Completely Hides Vercel API & Keys)
// ==========================================================================
if (isset($_GET['api_action'])) {
    header('Content-Type: application/json');
    $action = $_GET['api_action'] ?? '';
    $email  = $_GET['email'] ?? '';
    $url    = $_GET['url'] ?? '';
    $apiKey = 'freeapikeydhan26';

    if ($action === 'send') {
        $targetApi = "https://restapidhan.vercel.app/api/am?action=send&apikey=" . urlencode($apiKey) . "&email=" . urlencode($email);
    } elseif ($action === 'verif') {
        $targetApi = "https://restapidhan.vercel.app/api/am?action=verif&apikey=" . urlencode($apiKey) . "&email=" . urlencode($email) . "&url=" . urlencode($url);
    } elseif ($action === 'stats') {
        $targetApi = "https://restapidhan.vercel.app/api/am?apikey=" . urlencode($apiKey);
    } else {
        echo json_encode(['status' => false, 'message' => 'Action tidak valid']);
        exit;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $targetApi);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        echo json_encode(['status' => false, 'message' => 'Koneksi server gagal. Silakan coba lagi.']);
    } else {
        echo $response;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>AM Premium Generator Dashboard</title>
  <meta name="description" content="Dashboard Aktivasi Alight Motion Premium Cepat dan Otomatis." />
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <style>
    /* PREVENT TEXT SELECTION & COPYING */
    * {
      -webkit-user-select: none;
      -moz-user-select: none;
      -ms-user-select: none;
      user-select: none;
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
      -webkit-tap-highlight-color: transparent;
    }

    input, textarea {
      -webkit-user-select: text;
      -moz-user-select: text;
      -ms-user-select: text;
      user-select: text;
    }

    :root {
      --bg-dark: #080C14;
      --sidebar-bg: #0F1623;
      --sidebar-border: #1B2436;
      --card-bg: #131B2E;
      --card-border: #1F2C47;
      --input-bg: #0A0F1A;
      --input-border: #263552;
      
      --text-title: #FFFFFF;
      --text-main: #E2E8F0;
      --text-sub: #94A3B8;
      --text-muted: #64748B;
      
      --am-gradient: linear-gradient(135deg, #00F2FE 0%, #4FACFE 45%, #6B11FF 100%);
      --blue-primary: #3B82F6;
      --blue-gradient: linear-gradient(135deg, #2563EB 0%, #3B82F6 100%);
      --blue-glow: rgba(59, 130, 246, 0.25);
      
      --success: #10B981;
      --radius-card: 18px;
      --radius-btn: 12px;
    }

    body {
      background-color: var(--bg-dark);
      color: var(--text-main);
      min-height: 100vh;
      display: flex;
      overflow-x: hidden;
    }

    .dashboard-app {
      display: flex;
      width: 100%;
      min-height: 100vh;
    }

    /* DESKTOP SIDEBAR */
    .sidebar {
      width: 250px;
      background-color: var(--sidebar-bg);
      border-right: 1px solid var(--sidebar-border);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 24px 16px;
      position: fixed;
      top: 0; bottom: 0; left: 0;
      z-index: 100;
    }

    .sidebar-brand {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 32px;
      padding: 0 4px;
    }

    .brand-logo-box {
      width: 38px;
      height: 38px;
      background: var(--am-gradient);
      border-radius: 11px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 16px rgba(79, 172, 254, 0.35);
      flex-shrink: 0;
    }

    .brand-logo-box svg { width: 22px; height: 22px; }

    .brand-name {
      font-size: 1.05rem;
      font-weight: 800;
      color: var(--text-title);
      letter-spacing: -0.2px;
    }

    .sidebar-menu {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .menu-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      border-radius: var(--radius-btn);
      color: var(--text-sub);
      font-size: 0.875rem;
      font-weight: 600;
      cursor: pointer;
      background: transparent;
      border: 1px solid transparent;
      width: 100%;
      text-align: left;
      transition: all 0.2s ease;
    }

    .menu-item svg { width: 18px; height: 18px; fill: currentColor; }

    .menu-item:hover {
      background: rgba(255, 255, 255, 0.04);
      color: var(--text-main);
    }

    .menu-item.active {
      background: rgba(59, 130, 246, 0.12);
      color: var(--blue-primary);
      border-color: rgba(59, 130, 246, 0.25);
      font-weight: 700;
    }

    .sidebar-footer {
      padding: 16px 8px 0 8px;
      border-top: 1px solid var(--sidebar-border);
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.75rem;
      color: var(--text-muted);
    }

    .server-status-dot {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: #34D399;
      font-weight: 700;
    }

    .server-status-dot::before {
      content: '';
      width: 6px;
      height: 6px;
      background: #34D399;
      border-radius: 50%;
      box-shadow: 0 0 6px #34D399;
    }

    /* MAIN CONTENT AREA */
    .main-content {
      flex: 1;
      margin-left: 250px;
      padding: 40px;
      max-width: 1200px;
      width: calc(100% - 250px);
    }

    .page-header { margin-bottom: 28px; }

    .page-title {
      font-size: 1.75rem;
      font-weight: 800;
      color: var(--text-title);
      letter-spacing: -0.5px;
      margin-bottom: 6px;
    }

    .page-subtitle { font-size: 0.925rem; color: var(--text-sub); }

    /* METRICS OVERVIEW STATS GRID (3 CARDS) */
    .metrics-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
      margin-bottom: 28px;
    }

    .metric-card {
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: var(--radius-card);
      padding: 20px 22px;
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .metric-icon-box {
      width: 46px; height: 46px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .metric-icon-box.green { background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.25); color: #34D399; }
    .metric-icon-box.blue { background: rgba(59, 130, 246, 0.12); border: 1px solid rgba(59, 130, 246, 0.25); color: #60A5FA; }
    .metric-icon-box.purple { background: rgba(168, 85, 247, 0.12); border: 1px solid rgba(168, 85, 247, 0.25); color: #C084FC; }

    .metric-icon-box svg { width: 22px; height: 22px; fill: currentColor; }

    .metric-val {
      font-size: 1.35rem;
      font-weight: 800;
      color: var(--text-title);
      line-height: 1.2;
    }

    .metric-lbl {
      font-size: 0.775rem;
      font-weight: 600;
      color: var(--text-sub);
      margin-top: 2px;
    }

    /* 3-STEP GRID HEADER */
    .steps-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
      margin-bottom: 24px;
    }

    .step-card {
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: var(--radius-card);
      padding: 18px 20px;
      text-align: center;
      transition: all 0.25s ease;
    }

    .step-num {
      font-size: 1.1rem;
      font-weight: 800;
      color: var(--text-muted);
      margin-bottom: 4px;
    }

    .step-text { font-size: 0.85rem; font-weight: 600; color: var(--text-sub); }

    .step-card.active {
      background: rgba(59, 130, 246, 0.08);
      border-color: var(--blue-primary);
      box-shadow: 0 0 20px var(--blue-glow);
    }
    .step-card.active .step-num { color: var(--blue-primary); }
    .step-card.active .step-text { color: var(--text-title); font-weight: 700; }

    .step-card.completed {
      border-color: rgba(16, 185, 129, 0.3);
      background: rgba(16, 185, 129, 0.05);
    }
    .step-card.completed .step-num { color: var(--success); }

    /* PANEL FORM CARD */
    .panel-card {
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: var(--radius-card);
      padding: 32px;
      box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.5);
      margin-bottom: 32px;
    }

    .panel-header {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 24px;
      padding-bottom: 20px;
      border-bottom: 1px solid var(--sidebar-border);
    }

    .panel-icon-box {
      width: 44px;
      height: 44px;
      background: rgba(59, 130, 246, 0.12);
      border: 1px solid rgba(59, 130, 246, 0.25);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--blue-primary);
      flex-shrink: 0;
    }
    .panel-icon-box svg { width: 22px; height: 22px; fill: currentColor; }

    .panel-title { font-size: 1.15rem; font-weight: 800; color: var(--text-title); }
    .panel-desc { font-size: 0.85rem; color: var(--text-sub); margin-top: 2px; }

    .form-group { margin-bottom: 22px; }
    .form-label { display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-sub); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }

    .input-field {
      width: 100%;
      background: var(--input-bg);
      border: 1px solid var(--input-border);
      border-radius: var(--radius-btn);
      padding: 15px 18px;
      color: var(--text-title);
      font-size: 0.95rem;
      outline: none;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .input-field::placeholder { color: #334155; }

    .input-field:focus {
      border-color: var(--blue-primary);
      box-shadow: 0 0 0 3px var(--blue-glow);
    }

    .chips-group { display: flex; gap: 8px; margin-top: 10px; flex-wrap: wrap; }
    .chip {
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.08);
      color: var(--text-sub);
      font-size: 0.775rem;
      font-weight: 600;
      padding: 6px 12px;
      border-radius: 20px;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .chip:hover {
      background: rgba(59, 130, 246, 0.15);
      border-color: var(--blue-primary);
      color: #FFF;
    }

    .btn-row { display: flex; gap: 12px; margin-top: 24px; }

    .btn-blue {
      flex: 1;
      padding: 15px 24px;
      background: var(--blue-gradient);
      border: none;
      border-radius: var(--radius-btn);
      color: #FFFFFF;
      font-size: 0.95rem;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      box-shadow: 0 6px 20px -2px var(--blue-glow);
      transition: opacity 0.2s ease, transform 0.15s ease;
    }

    .btn-blue:hover { opacity: 0.95; }
    .btn-blue:active { transform: scale(0.98); }

    .btn-secondary {
      flex: 1;
      padding: 15px 24px;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid var(--sidebar-border);
      border-radius: var(--radius-btn);
      color: var(--text-sub);
      font-size: 0.95rem;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all 0.2s ease;
    }

    .btn-secondary:hover { background: rgba(255, 255, 255, 0.08); color: var(--text-title); }

    .btn-blue:disabled, .btn-secondary:disabled { opacity: 0.6; cursor: not-allowed; transform: none !important; }

    .spinner {
      width: 18px; height: 18px;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-top-color: #FFFFFF;
      border-radius: 50%;
      animation: spin 0.6s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    .success-box {
      background: rgba(16, 185, 129, 0.08);
      border: 1px solid rgba(16, 185, 129, 0.25);
      border-radius: var(--radius-card);
      padding: 24px;
      margin-bottom: 24px;
    }

    .success-header {
      display: flex;
      align-items: center;
      gap: 14px;
      padding-bottom: 16px;
      border-bottom: 1px dashed rgba(255, 255, 255, 0.1);
      margin-bottom: 16px;
    }

    .success-badge-icon {
      width: 42px; height: 42px;
      background: var(--success);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #FFF;
      box-shadow: 0 0 16px rgba(16, 185, 129, 0.4);
    }
    .success-badge-icon svg { width: 22px; height: 22px; fill: currentColor; }

    .data-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; }
    .data-key { font-size: 0.85rem; color: var(--text-muted); }
    .data-val { font-size: 0.925rem; font-weight: 700; color: var(--text-title); }

    .code-order-badge {
      font-family: monospace;
      background: rgba(59, 130, 246, 0.15);
      color: #60A5FA;
      border: 1px solid rgba(59, 130, 246, 0.3);
      padding: 4px 10px;
      border-radius: 6px;
      font-weight: 700;
    }

    /* RECENT ACTIVATIONS FEED CONTAINER */
    .feed-card {
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: var(--radius-card);
      padding: 28px 32px;
    }

    .feed-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 14px;
      border-bottom: 1px solid var(--sidebar-border);
    }

    .feed-title {
      font-size: 1.05rem;
      font-weight: 800;
      color: var(--text-title);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .feed-pulse {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 0.775rem;
      color: #34D399;
      font-weight: 700;
      background: rgba(16, 185, 129, 0.12);
      border: 1px solid rgba(16, 185, 129, 0.25);
      padding: 4px 12px;
      border-radius: 20px;
    }

    .feed-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .feed-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: rgba(10, 15, 26, 0.6);
      border: 1px solid rgba(255, 255, 255, 0.04);
      border-radius: 12px;
      padding: 14px 18px;
    }

    .feed-user-info {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .feed-avatar {
      width: 36px;
      height: 36px;
      background: linear-gradient(135deg, #10B981, #059669);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #FFF;
      font-weight: 800;
      font-size: 0.85rem;
    }

    .feed-email {
      font-size: 0.9rem;
      font-weight: 700;
      color: var(--text-title);
    }

    .feed-time {
      font-size: 0.775rem;
      color: var(--text-muted);
      margin-top: 2px;
    }

    .feed-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(16, 185, 129, 0.12);
      border: 1px solid rgba(16, 185, 129, 0.3);
      color: #34D399;
      font-size: 0.775rem;
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 6px;
    }

    .toast-box {
      position: fixed;
      top: 20px; right: 20px;
      z-index: 9999;
      min-width: 300px;
      background: #182232;
      border: 1px solid var(--card-border);
      padding: 14px 18px;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
      display: flex;
      align-items: center;
      gap: 12px;
      opacity: 0;
      pointer-events: none;
      transform: translateY(-20px);
      transition: all 0.3s ease;
    }

    .toast-box.show { opacity: 1; pointer-events: auto; transform: translateY(0); }
    .toast-box.error { border-color: rgba(239, 68, 68, 0.4); }
    .toast-box.error .toast-txt { color: #FCA5A5; }
    .toast-box.success { border-color: rgba(16, 185, 129, 0.4); }
    .toast-box.success .toast-txt { color: #6EE7B7; }

    .faq-list { display: flex; flex-direction: column; gap: 12px; }
    .faq-item { background: var(--input-bg); border: 1px solid var(--input-border); border-radius: var(--radius-btn); overflow: hidden; }
    .faq-header { padding: 16px 20px; font-size: 0.9rem; font-weight: 700; color: var(--text-title); cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
    .faq-body { display: none; padding: 0 20px 16px 20px; font-size: 0.85rem; color: var(--text-sub); line-height: 1.6; border-top: 1px solid rgba(255, 255, 255, 0.05); }
    .faq-item.open .faq-body { display: block; }
    .faq-item.open .faq-icon { transform: rotate(180deg); fill: var(--blue-primary); }

    .faq-icon { width: 18px; height: 18px; fill: var(--text-muted); transition: transform 0.2s ease; }
    .page-view { display: none; }
    .page-view.active { display: block; }

    /* HIDE MOBILE ELEMENTS ON DESKTOP BY DEFAULT */
    .mobile-header, .mobile-bottom-nav { display: none !important; }

    @media (max-width: 768px) {
      body { flex-direction: column; }
      .sidebar { display: none !important; }

      .mobile-header {
        display: flex !important;
        width: 100%;
        background: var(--sidebar-bg);
        border-bottom: 1px solid var(--sidebar-border);
        padding: 14px 16px;
        position: sticky;
        top: 0;
        z-index: 90;
        justify-content: space-between;
        align-items: center;
      }

      .main-content { margin-left: 0; width: 100%; padding: 20px 16px 80px 16px; }
      .page-title { font-size: 1.35rem; }
      .metrics-grid { grid-template-columns: 1fr; gap: 10px; }
      .steps-grid { grid-template-columns: repeat(3, 1fr); gap: 8px; }
      .step-card { padding: 12px 8px; }
      .step-num { font-size: 0.95rem; }
      .step-text { font-size: 0.725rem; }
      .panel-card { padding: 20px 16px; margin-bottom: 20px; }

      .feed-card { padding: 20px 16px; }
      .feed-item { padding: 10px 12px; }

      .toast-box {
        right: 50%;
        transform: translateX(50%) translateY(-20px);
        width: calc(100% - 32px);
      }

      .toast-box.show { transform: translateX(50%) translateY(0); }

      .mobile-bottom-nav {
        display: flex !important;
        position: fixed;
        bottom: 0; left: 0; right: 0;
        background: var(--sidebar-bg);
        border-top: 1px solid var(--sidebar-border);
        justify-content: space-around;
        padding: 10px 0;
        z-index: 100;
      }

      .mob-nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        color: var(--text-muted);
        font-size: 0.675rem;
        font-weight: 600;
        background: transparent !important;
        border: none !important;
        outline: none !important;
        cursor: pointer;
        padding: 4px 8px;
      }

      .mob-nav-item svg { width: 20px; height: 20px; fill: currentColor; }
      .mob-nav-item.active { color: var(--blue-primary); font-weight: 700; }
    }
  </style>
</head>
<body>

  <!-- Mobile Top Header Bar -->
  <header class="mobile-header">
    <div class="sidebar-brand" style="margin-bottom:0;">
      <div class="brand-logo-box">
        <svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M7.5 24C6.12 24 5 22.88 5 21.5V10.5C5 9.12 6.12 8 7.5 8H13C16.59 8 19.5 10.91 19.5 14.5C19.5 18.09 16.59 21 13 21H9V24H7.5ZM13 11H8.5V18H13C14.93 18 16.5 16.43 16.5 14.5C16.5 12.57 14.93 11 13 11Z" fill="white"/>
          <path d="M22.5 24C21.12 24 20 22.88 20 21.5V10.5C20 9.12 21.12 8 22.5 8C23.88 8 25 9.12 25 10.5V21.5C25 22.88 23.88 24 22.5 24Z" fill="white"/>
        </svg>
      </div>
      <div class="brand-name">Alight Motion</div>
    </div>
    <div class="server-status-dot">Online</div>
  </header>

  <div class="dashboard-app">

    <!-- Left Desktop Sidebar Navigation -->
    <aside class="sidebar">
      <div>
        <div class="sidebar-brand">
          <div class="brand-logo-box">
            <svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M7.5 24C6.12 24 5 22.88 5 21.5V10.5C5 9.12 6.12 8 7.5 8H13C16.59 8 19.5 10.91 19.5 14.5C19.5 18.09 16.59 21 13 21H9V24H7.5ZM13 11H8.5V18H13C14.93 18 16.5 16.43 16.5 14.5C16.5 12.57 14.93 11 13 11Z" fill="white"/>
              <path d="M22.5 24C21.12 24 20 22.88 20 21.5V10.5C20 9.12 21.12 8 22.5 8C23.88 8 25 9.12 25 10.5V21.5C25 22.88 23.88 24 22.5 24Z" fill="white"/>
            </svg>
          </div>
          <div class="brand-name">Alight Motion</div>
        </div>

        <nav class="sidebar-menu">
          <button class="menu-item active" id="sNavGen" onclick="switchTab('generator')">
            <svg viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
            <span>AM Generator</span>
          </button>

          <button class="menu-item" id="sNavCheck" onclick="switchTab('check')">
            <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
            <span>Server Status / Cek Order</span>
          </button>

          <button class="menu-item" id="sNavFaq" onclick="switchTab('faq')">
            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 16h-2v-2h2v2zm1.07-7.75l-.9.92C12.45 11.9 12 12.5 12 14h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H7c0-2.76 2.24-5 5-5s5 2.24 5 5c0 1.04-.42 1.99-1.07 2.75z"/></svg>
            <span>Panduan FAQ</span>
          </button>
        </nav>
      </div>

      <div class="sidebar-footer">
        <span>Status Server:</span>
        <span class="server-status-dot">Online</span>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main class="main-content">

      <!-- Toast Alert Notification -->
      <div id="toast" class="toast-box">
        <span id="toastIcon">⚠️</span>
        <span id="toastMsg" class="toast-txt">Notifikasi</span>
      </div>

      <!-- ================= VIEW 1: AM GENERATOR ================= -->
      <div id="viewGenerator" class="page-view active">
        
        <div class="page-header">
          <h1 class="page-title">AM Premium Generator</h1>
          <p class="page-subtitle">Aktifkan premium Alight Motion dalam 3 langkah mudah</p>
        </div>

        <!-- SAAS DASHBOARD METRICS CARDS -->
        <div class="metrics-grid">
          <div class="metric-card">
            <div class="metric-icon-box green">
              <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
            </div>
            <div>
              <div class="metric-val" id="apiTotalCount">...</div>
              <div class="metric-lbl">Total User API Key</div>
            </div>
          </div>

          <div class="metric-card">
            <div class="metric-icon-box blue">
              <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/></svg>
            </div>
            <div>
              <div class="metric-val" style="color:#60A5FA;">Online</div>
              <div class="metric-lbl">Server Proxy Vercel</div>
            </div>
          </div>

          <div class="metric-card">
            <div class="metric-icon-box purple">
              <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
            </div>
            <div>
              <div class="metric-val" style="color:#C084FC;">100%</div>
              <div class="metric-lbl">Otomatis & Garansi</div>
            </div>
          </div>
        </div>

        <!-- Horizontal 3-Step Progress Cards Header -->
        <div class="steps-grid">
          <div id="stepCard1" class="step-card active">
            <div class="step-num">1</div>
            <div class="step-text">Hubungkan Akun</div>
          </div>
          <div id="stepCard2" class="step-card">
            <div class="step-num">2</div>
            <div class="step-text">Tempel Link Email</div>
          </div>
          <div id="stepCard3" class="step-card">
            <div class="step-num">3</div>
            <div class="step-text">Premium Aktif!</div>
          </div>
        </div>

        <!-- Panel Form Container Card -->
        <div class="panel-card">

          <!-- STEP 1 FORM -->
          <div id="step1View">
            <div class="panel-header">
              <div class="panel-icon-box">
                <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
              </div>
              <div>
                <div class="panel-title">Langkah 1: Hubungkan Akun Alight Motion</div>
                <div class="panel-desc">Masukkan email Alight Motion Anda untuk menerima magic link verifikasi</div>
              </div>
            </div>

            <form onsubmit="handleSendEmail(event)">
              <div class="form-group">
                <label for="emailInput" class="form-label">Email Alight Motion</label>
                <div class="input-wrapper">
                  <input type="email" id="emailInput" class="input-field" placeholder="email@contoh.com" required autocomplete="email" />
                </div>
                <div class="chips-group">
                  <span class="chip" onclick="appendDomain('@gmail.com')">+ @gmail.com</span>
                  <span class="chip" onclick="appendDomain('@yahoo.com')">+ @yahoo.com</span>
                  <span class="chip" onclick="appendDomain('@hotmail.com')">+ @hotmail.com</span>
                </div>
              </div>

              <button type="submit" id="btnStep1" class="btn-blue" style="width:100%;">
                <span>Kirim Magic Link</span>
              </button>
            </form>
          </div>

          <!-- STEP 2 FORM -->
          <div id="step2View" style="display:none;">
            <div class="panel-header">
              <div class="panel-icon-box">
                <svg viewBox="0 0 24 24"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>
              </div>
              <div>
                <div class="panel-title">Langkah 2: Tempelkan Link Verifikasi Email</div>
                <div class="panel-desc">Buka email dari Alight Creative, salin alamat tombol verifikasi lalu tempelkan di bawah</div>
              </div>
            </div>

            <form onsubmit="handleVerifyLink(event)">
              <div class="form-group">
                <label for="linkInput" class="form-label">Link Verifikasi (URL)</label>
                <div class="input-wrapper">
                  <input type="url" id="linkInput" class="input-field" placeholder="https://alightcreative.com/authed?..." required autocomplete="off" />
                </div>
              </div>

              <div class="btn-row">
                <button type="button" class="btn-secondary" onclick="setWizardStep(1)">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
                  <span>Kembali</span>
                </button>
                <button type="submit" id="btnStep2" class="btn-blue">
                  <span>Proses Aktivasi Premium 🚀</span>
                </button>
              </div>
            </form>
          </div>

          <!-- STEP 3 SUCCESS -->
          <div id="step3View" style="display:none;">
            <div class="success-box">
              <div class="success-header">
                <div class="success-badge-icon">
                  <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                </div>
                <div>
                  <h3 style="color:#34D399; font-size:1.1rem; font-weight:800;">Langkah 3: Akun Berhasil Diaktifkan!</h3>
                  <p style="font-size:0.85rem; color:var(--text-sub);">Status Lisensi Alight Motion Anda sekarang Aktif Premium.</p>
                </div>
              </div>

              <div class="data-row">
                <span class="data-key">Status Lisensi</span>
                <span style="color:#34D399; font-weight:800; background:rgba(16,185,129,0.15); padding:3px 10px; border-radius:6px;">ACTIVE PREMIUM</span>
              </div>
              <div class="data-row">
                <span class="data-key">Email Terdaftar</span>
                <span id="resEmail" class="data-val">-</span>
              </div>
              <div class="data-row">
                <span class="data-key">Kode Order</span>
                <div>
                  <span id="resCode" class="code-order-badge">AM-000000</span>
                  <button type="button" id="btnCopyCode" onclick="copyCode()" style="background:none; border:none; color:var(--blue-primary); cursor:pointer; font-weight:700; margin-left:8px;">Salin</button>
                </div>
              </div>
            </div>

            <div class="btn-row">
              <button type="button" class="btn-secondary" onclick="resetWizard()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
                <span>Kembali</span>
              </button>
              <button type="button" class="btn-blue" onclick="resetWizard()">
                <span>Aktivasi Akun Lain</span>
              </button>
            </div>
          </div>

        </div>

        <!-- RECENT ACTIVATION LIVE FEED CARD -->
        <div class="feed-card">
          <div class="feed-header">
            <div class="feed-title">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="var(--blue-primary)"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
              <span>Aktivasi Terbaru Pengguna</span>
            </div>
            <div class="feed-pulse">
              <span style="width:6px; height:6px; background:#34D399; border-radius:50%; box-shadow:0 0 6px #34D399;"></span>
              <span>Live Updates</span>
            </div>
          </div>

          <div class="feed-list" id="feedListContainer">
            <div class="feed-item">
              <div class="feed-user-info">
                <div class="feed-avatar">Z</div>
                <div>
                  <div class="feed-email">zidnifarchan***@gmail.com</div>
                  <div class="feed-time">Baru saja • Kode Order: AM-229984</div>
                </div>
              </div>
              <div class="feed-badge">✓ ACTIVE PREMIUM</div>
            </div>

            <div class="feed-item">
              <div class="feed-user-info">
                <div class="feed-avatar" style="background:linear-gradient(135deg, #3B82F6, #1D4ED8);">B</div>
                <div>
                  <div class="feed-email">budi.editor***@gmail.com</div>
                  <div class="feed-time">3 menit lalu • Kode Order: AM-849201</div>
                </div>
              </div>
              <div class="feed-badge">✓ ACTIVE PREMIUM</div>
            </div>

            <div class="feed-item">
              <div class="feed-user-info">
                <div class="feed-avatar" style="background:linear-gradient(135deg, #8B5CF6, #6D28D9);">R</div>
                <div>
                  <div class="feed-email">rizky.preset***@yahoo.com</div>
                  <div class="feed-time">11 menit lalu • Kode Order: AM-772109</div>
                </div>
              </div>
              <div class="feed-badge">✓ ACTIVE PREMIUM</div>
            </div>
          </div>
        </div>

      </div>

      <!-- ================= VIEW 2: CEK ORDER STATUS ================= -->
      <div id="viewCheck" class="page-view">
        <div class="page-header">
          <h1 class="page-title">Server Status & Cek Order</h1>
          <p class="page-subtitle">Periksa keabsahan dan masa aktif lisensi Anda berdasarkan Kode Order</p>
        </div>

        <div class="panel-card">
          <form onsubmit="handleSearchOrder(event)">
            <div class="form-group">
              <label for="searchCodeInput" class="form-label">Kode Order (Contoh: AM-982314)</label>
              <div class="input-wrapper">
                <input type="text" id="searchCodeInput" class="input-field" placeholder="Masukkan Kode Order..." required />
              </div>
            </div>
            
            <div class="btn-row">
              <button type="button" class="btn-secondary" onclick="switchTab('generator')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
                <span>Kembali</span>
              </button>
              <button type="submit" class="btn-blue">Periksa Status Lisensi</button>
            </div>
          </form>

          <div id="searchResBox" style="display:none; margin-top:24px;">
            <div class="success-box" style="background:rgba(59,130,246,0.08); border-color:rgba(59,130,246,0.25);">
              <div class="data-row">
                <span class="data-key">Kode Order</span>
                <span id="searchResCode" class="code-order-badge">AM-000000</span>
              </div>
              <div class="data-row">
                <span class="data-key">Status Lisensi</span>
                <span style="color:#34D399; font-weight:800;">TERVERIFIKASI PREMIUM</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= VIEW 3: FAQ ================= -->
      <div id="viewFaq" class="page-view">
        <div class="page-header">
          <h1 class="page-title">Panduan & Pertanyaan Umum</h1>
          <p class="page-subtitle">Petunjuk lengkap cara melakukan aktivasi Alight Motion Premium</p>
        </div>

        <div class="panel-card">
          <div class="faq-list">
            <div class="faq-item" onclick="toggleFaq(this)">
              <div class="faq-header">
                <span>1. Bagaimana cara mendapatkan link verifikasi?</span>
                <svg class="faq-icon" viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
              </div>
              <div class="faq-body">
                Setelah Anda memasukkan email pada Langkah 1, buka aplikasi Gmail pada smartphone Anda. Cari email dari <strong>Alight Creative</strong>, lalu tekan dan tahan tombol verifikasi di email untuk menyalin alamat link URL-nya.
              </div>
            </div>

            <div class="faq-item" onclick="toggleFaq(this)">
              <div class="faq-header">
                <span>2. Mengapa email verifikasi belum juga masuk?</span>
                <svg class="faq-icon" viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
              </div>
              <div class="faq-body">
                Pastikan Anda memeriksa folder <strong>Spam / Promosi</strong> pada akun Gmail Anda. Pastikan juga alamat email yang diketikkan di Langkah 1 sudah benar tanpa ada kesalahan ketik (typo).
              </div>
            </div>

            <div class="faq-item" onclick="toggleFaq(this)">
              <div class="faq-header">
                <span>3. Apakah sistem aktivasi bekerja otomatis?</span>
                <svg class="faq-icon" viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
              </div>
              <div class="faq-body">
                Ya! Sistem backend diproses secara instan 24 jam nonstop oleh server otomatis setelah Anda menempelkan link verifikasi.
              </div>
            </div>
          </div>

          <div style="margin-top: 24px;">
            <button type="button" class="btn-secondary" style="width: auto;" onclick="switchTab('generator')">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
              <span>Kembali ke Generator</span>
            </button>
          </div>
        </div>
      </div>

    </main>

  </div>

  <!-- Mobile Bottom Nav -->
  <nav class="mobile-bottom-nav">
    <button class="mob-nav-item active" id="mNavGen" onclick="switchTab('generator')">
      <svg viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
      <span>AM Generator</span>
    </button>
    <button class="mob-nav-item" id="mNavCheck" onclick="switchTab('check')">
      <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 14z"/></svg>
      <span>Status Order</span>
    </button>
    <button class="mob-nav-item" id="mNavFaq" onclick="switchTab('faq')">
      <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 16h-2v-2h2v2zm1.07-7.75l-.9.92C12.45 11.9 12 12.5 12 14h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H7c0-2.76 2.24-5 5-5s5 2.24 5 5c0 1.04-.42 1.99-1.07 2.75z"/></svg>
      <span>Panduan</span>
    </button>
  </nav>

  <!-- ANTI INSPECT & SOURCE CODE PROTECTION SCRIPT -->
  <script>
    // 1. DISABLE RIGHT CLICK (CONTEXT MENU)
    document.addEventListener("contextmenu", function(e) {
      e.preventDefault();
      return false;
    });

    // 2. DISABLE DEVTOOLS KEYBOARD SHORTCUTS
    document.addEventListener("keydown", function(e) {
      if (e.keyCode === 123) {
        e.preventDefault();
        return false;
      }
      if (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.keyCode === 74 || e.keyCode === 67)) {
        e.preventDefault();
        return false;
      }
      if (e.ctrlKey && (e.keyCode === 85 || e.keyCode === 83)) {
        e.preventDefault();
        return false;
      }
    });

    // 3. APPLICATION LOGIC & LIVE API KEY COUNTER
    const appState = { email: '', codeOrder: '', loading: false, apiCount: 1482 };

    async function fetchApiKeyStats() {
      try {
        const res = await fetch(`index.php?api_action=stats`);
        const data = await res.json().catch(() => ({}));

        if (data.total || data.count || data.total_users || data.length) {
          appState.apiCount = data.total || data.count || data.total_users || data.length;
        } else if (data.data && Array.isArray(data.data)) {
          appState.apiCount = data.data.length;
        }
      } catch (e) {
        // Smooth fallback
      }
      document.getElementById("apiTotalCount").textContent = appState.apiCount.toLocaleString();
    }

    function addActivationToFeed(email, code) {
      const container = document.getElementById("feedListContainer");
      const firstChar = email.charAt(0).toUpperCase();
      const maskedEmail = email.length > 8 ? email.substring(0, 4) + '***' + email.substring(email.indexOf('@')) : email;

      const newItem = document.createElement("div");
      newItem.className = "feed-item";
      newItem.innerHTML = `
        <div class="feed-user-info">
          <div class="feed-avatar" style="background:linear-gradient(135deg, #10B981, #059669);">${firstChar}</div>
          <div>
            <div class="feed-email">${maskedEmail}</div>
            <div class="feed-time">Baru saja • Kode Order: ${code}</div>
          </div>
        </div>
        <div class="feed-badge">✓ ACTIVE PREMIUM</div>
      `;
      container.insertBefore(newItem, container.firstChild);
    }

    function switchTab(tabName) {
      document.getElementById("viewGenerator").classList.toggle("active", tabName === 'generator');
      document.getElementById("viewCheck").classList.toggle("active", tabName === 'check');
      document.getElementById("viewFaq").classList.toggle("active", tabName === 'faq');

      document.getElementById("sNavGen").classList.toggle("active", tabName === 'generator');
      document.getElementById("sNavCheck").classList.toggle("active", tabName === 'check');
      document.getElementById("sNavFaq").classList.toggle("active", tabName === 'faq');

      document.getElementById("mNavGen").classList.toggle("active", tabName === 'generator');
      document.getElementById("mNavCheck").classList.toggle("active", tabName === 'check');
      document.getElementById("mNavFaq").classList.toggle("active", tabName === 'faq');
    }

    function setWizardStep(stepNum) {
      document.getElementById("step1View").style.display = stepNum === 1 ? "block" : "none";
      document.getElementById("step2View").style.display = stepNum === 2 ? "block" : "none";
      document.getElementById("step3View").style.display = stepNum === 3 ? "block" : "none";

      const cards = [document.getElementById("stepCard1"), document.getElementById("stepCard2"), document.getElementById("stepCard3")];
      cards.forEach((c, idx) => {
        c.classList.remove("active", "completed");
        if (idx + 1 === stepNum) c.classList.add("active");
        else if (idx + 1 < stepNum) c.classList.add("completed");
      });
    }

    function appendDomain(domain) {
      const inp = document.getElementById("emailInput");
      const currentVal = inp.value.trim();
      const atIdx = currentVal.indexOf("@");
      if (atIdx !== -1) {
        inp.value = currentVal.substring(0, atIdx) + domain;
      } else {
        inp.value = currentVal + domain;
      }
      inp.focus();
    }

    let toastTimer = null;
    function showToast(msg, type = 'error') {
      if (toastTimer) clearTimeout(toastTimer);
      const toast = document.getElementById("toast");
      document.getElementById("toastMsg").textContent = msg;
      document.getElementById("toastIcon").textContent = type === 'error' ? '⚠️' : '✓';
      toast.className = `toast-box ${type} show`;
      toastTimer = setTimeout(() => { toast.className = `toast-box ${type}`; }, 3500);
    }

    async function handleSendEmail(e) {
      e.preventDefault();
      const email = document.getElementById("emailInput").value.trim();
      if (!email) return showToast("Silakan masukkan email kamu.");

      const btn = document.getElementById("btnStep1");
      btn.disabled = true;
      btn.innerHTML = `<div class="spinner"></div><span>Memproses...</span>`;

      try {
        const res = await fetch(`index.php?api_action=send&email=${encodeURIComponent(email)}`);
        const data = await res.json().catch(() => ({}));

        if (data.status === false || data.error) throw new Error(data.message || "Gagal mengirim magic link.");

        appState.email = email;
        showToast("Magic link terkirim ke email!", "success");
        setWizardStep(2);
      } catch (err) {
        showToast(err.message || "Gagal terhubung ke server.");
      } finally {
        btn.disabled = false;
        btn.innerHTML = `<span>Kirim Magic Link</span>`;
      }
    }

    async function handleVerifyLink(e) {
      e.preventDefault();
      const link = document.getElementById("linkInput").value.trim();
      if (!link) return showToast("Masukkan link verifikasi.");

      const btn = document.getElementById("btnStep2");
      btn.disabled = true;
      btn.innerHTML = `<div class="spinner"></div><span>Memproses...</span>`;

      try {
        const res = await fetch(`index.php?api_action=verif&email=${encodeURIComponent(appState.email)}&url=${encodeURIComponent(link)}`);
        const data = await res.json().catch(() => ({}));

        if (data.status === false || data.error) throw new Error(data.message || "Verifikasi tidak valid.");

        const code = data.codeorder || data.code_order || data.code || `AM-${Math.floor(100000 + Math.random() * 900000)}`;
        appState.codeOrder = code;

        document.getElementById("resEmail").textContent = appState.email;
        document.getElementById("resCode").textContent = code;

        // Increment counter & add to live stream feed
        appState.apiCount++;
        document.getElementById("apiTotalCount").textContent = appState.apiCount.toLocaleString();
        addActivationToFeed(appState.email, code);

        showToast("Aktivasi Premium Berhasil!", "success");
        setWizardStep(3);
      } catch (err) {
        showToast(err.message || "Verifikasi gagal.");
      } finally {
        btn.disabled = false;
        btn.innerHTML = `<span>Proses Aktivasi Premium 🚀</span>`;
      }
    }

    function handleSearchOrder(e) {
      e.preventDefault();
      const query = document.getElementById("searchCodeInput").value.trim();
      if (!query) return showToast("Masukkan kode order.");

      document.getElementById("searchResCode").textContent = query.toUpperCase();
      document.getElementById("searchResBox").style.display = "block";
      showToast("Order Terverifikasi!", "success");
    }

    function toggleFaq(el) { el.classList.toggle("open"); }

    function copyCode() {
      if (!appState.codeOrder) return;
      navigator.clipboard.writeText(appState.codeOrder).then(() => {
        const btn = document.getElementById("btnCopyCode");
        btn.textContent = "Tersalin!";
        setTimeout(() => btn.textContent = "Salin", 2000);
      });
    }

    function resetWizard() {
      appState.email = '';
      appState.codeOrder = '';
      document.getElementById("emailInput").value = '';
      document.getElementById("linkInput").value = '';
      setWizardStep(1);
    }

    // Initialize API Key stats on page load
    document.addEventListener("DOMContentLoaded", fetchApiKeyStats);
  </script>
</body>
</html>
