<?php
// ==========================================================================
// BACKEND PROXY & SQLITE DATABASE INTEGRATION
// ==========================================================================
$dbFile = __DIR__ . '/database.sqlite';
$pdo = null;

try {
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE TABLE IF NOT EXISTS activations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL,
        code_order TEXT NOT NULL,
        status TEXT DEFAULT 'ACTIVE PREMIUM',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Seed database if empty with sample initial activations
    $checkStmt = $pdo->query("SELECT COUNT(*) FROM activations");
    if ((int)$checkStmt->fetchColumn() === 0) {
        $initialData = [
            ['email' => 'siti.editpro@hotmail.com', 'code_order' => 'AM-993821', 'created_at' => date('Y-m-d H:i:s', strtotime('-5 hours'))],
            ['email' => 'andrian.motion@gmail.com', 'code_order' => 'AM-510492', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))],
            ['email' => 'rizky.preset88@yahoo.com', 'code_order' => 'AM-772109', 'created_at' => date('Y-m-d H:i:s', strtotime('-11 minutes'))],
            ['email' => 'budi.editor26@gmail.com', 'code_order' => 'AM-849201', 'created_at' => date('Y-m-d H:i:s', strtotime('-3 minutes'))],
            ['email' => 'zidnifarchanramadhan@gmail.com', 'code_order' => 'AM-229984', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 minute'))]
        ];
        $insertSeed = $pdo->prepare("INSERT INTO activations (email, code_order, created_at) VALUES (?, ?, ?)");
        foreach ($initialData as $seed) {
            $insertSeed->execute([$seed['email'], $seed['code_order'], $seed['created_at']]);
        }
    }
} catch (Exception $e) {
    $pdo = null;
}

if (isset($_GET['api_action'])) {
    header('Content-Type: application/json');
    $action = $_GET['api_action'] ?? '';
    $email  = trim($_GET['email'] ?? '');
    $url    = trim($_GET['url'] ?? '');
    $code   = trim($_GET['code'] ?? '');
    $apiKey = 'freeapikeydhan26';

    if ($action === 'stats') {
        $count = 1482; // Default baseline offset
        $recent = [];
        if ($pdo) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM activations");
            $dbCount = (int)$stmt->fetchColumn();
            // Total combines database records
            $count = $dbCount;
            
            $stmtRecent = $pdo->query("SELECT email, code_order, created_at FROM activations ORDER BY id DESC LIMIT 5");
            $recent = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);
        }
        echo json_encode([
            'status' => true,
            'total'  => $count,
            'recent' => $recent
        ]);
        exit;
    }

    if ($action === 'search') {
        if (empty($code)) {
            echo json_encode(['status' => false, 'message' => 'Kode Order wajib diisi']);
            exit;
        }
        $formattedCode = str_starts_with(strtoupper($code), 'AM-') ? strtoupper($code) : 'AM-' . strtoupper($code);
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT email, code_order, status, created_at FROM activations WHERE UPPER(code_order) = ? OR UPPER(code_order) = ? LIMIT 1");
            $stmt->execute([strtoupper($code), $formattedCode]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                echo json_encode([
                    'status' => true,
                    'found'  => true,
                    'data'   => $row
                ]);
                exit;
            }
        }
        if (preg_match('/^AM-\d{6}$/i', $code)) {
            echo json_encode([
                'status' => true,
                'found'  => true,
                'data'   => [
                    'email'      => 'Terverifikasi (Sistem)',
                    'code_order' => $formattedCode,
                    'status'     => 'ACTIVE PREMIUM',
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            echo json_encode(['status' => false, 'message' => 'Kode Order tidak ditemukan di database.']);
        }
        exit;
    }

    if ($action === 'send') {
        $targetApi = "https://restapidhan.vercel.app/api/am?action=send&apikey=" . urlencode($apiKey) . "&email=" . urlencode($email);
    } elseif ($action === 'verif') {
        $targetApi = "https://restapidhan.vercel.app/api/am?action=verif&apikey=" . urlencode($apiKey) . "&email=" . urlencode($email) . "&url=" . urlencode($url);
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
        $data = json_decode($response, true);
        if ($action === 'verif' && is_array($data) && ($data['status'] ?? false) !== false && !isset($data['error'])) {
            $codeOrder = $data['codeorder'] ?? $data['code_order'] ?? $data['code'] ?? ('AM-' . rand(100000, 999999));
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO activations (email, code_order) VALUES (?, ?)");
                    $stmt->execute([$email, $codeOrder]);
                } catch (Exception $e) {}
                
                $stmtCount = $pdo->query("SELECT COUNT(*) FROM activations");
                $data['db_total'] = (int)$stmtCount->fetchColumn();
                $data['codeorder'] = $codeOrder;
            }
            $response = json_encode($data);
        }
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

    /* ALERT GREEN BANNER */
    .alert-banner-green {
      background: rgba(16, 185, 129, 0.1);
      border: 1px solid rgba(16, 185, 129, 0.3);
      color: #34D399;
      border-radius: var(--radius-card);
      padding: 14px 20px;
      font-size: 0.9rem;
      font-weight: 600;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .icon-box-purple {
      background: rgba(139, 92, 246, 0.12) !important;
      border-color: rgba(139, 92, 246, 0.3) !important;
      color: #A78BFA !important;
    }

    /* TOP RIGHT SPONSOR AD WIDGET */
    .sponsor-ad-widget {
      position: absolute;
      top: 20px;
      right: 20px;
      background: rgba(15, 23, 42, 0.95);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      padding: 10px 14px;
      display: flex;
      align-items: center;
      gap: 12px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.5);
      z-index: 10;
    }
    .sponsor-ad-widget img {
      width: 44px;
      height: 44px;
      border-radius: 8px;
      object-fit: cover;
    }
    .sponsor-ad-title { font-size: 0.85rem; font-weight: 700; color: #FFF; margin-bottom: 2px; }
    .sponsor-ad-sub { font-size: 0.75rem; color: var(--blue-primary); cursor: pointer; text-decoration: underline; }
    .sponsor-ad-badge {
      position: absolute;
      top: -6px; right: -6px;
      background: #EF4444; color: #FFF;
      font-size: 0.65rem; font-weight: 800;
      width: 18px; height: 18px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
    }

    /* 5 SUB-AD STATUS CARDS GRID */
    .ad-sub-grid {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 12px;
      margin: 24px 0;
    }
    .ad-sub-box {
      background: var(--input-bg);
      border: 1px solid var(--card-border);
      border-radius: 12px;
      padding: 16px 8px;
      text-align: center;
      transition: all 0.25s ease;
    }
    .ad-sub-box.active {
      background: rgba(59, 130, 246, 0.1);
      border-color: var(--blue-primary);
      box-shadow: 0 0 15px var(--blue-glow);
    }
    .ad-sub-box.completed {
      background: rgba(16, 185, 129, 0.1);
      border-color: rgba(16, 185, 129, 0.4);
    }
    .ad-sub-num {
      font-size: 1.1rem;
      font-weight: 800;
      color: var(--text-title);
      margin-bottom: 4px;
    }
    .ad-sub-lbl {
      font-size: 0.75rem;
      font-weight: 600;
      color: var(--text-muted);
    }
    .ad-sub-box.active .ad-sub-lbl { color: var(--blue-primary); font-weight: 700; }
    .ad-sub-box.completed .ad-sub-lbl { color: #34D399; font-weight: 700; }

    /* AD MODAL DIALOG */
    .modal-overlay {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0, 0, 0, 0.8);
      backdrop-filter: blur(8px);
      z-index: 99999;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s ease;
    }
    .modal-overlay.show {
      opacity: 1;
      pointer-events: auto;
    }
    .modal-card {
      background: #111827;
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 20px;
      width: 100%;
      max-width: 440px;
      padding: 32px 24px;
      text-align: center;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.9);
      transform: translateY(20px);
      transition: transform 0.3s ease;
    }
    .modal-overlay.show .modal-card {
      transform: translateY(0);
    }
    .modal-warn-icon {
      width: 56px; height: 56px;
      border-radius: 50%;
      background: rgba(245, 158, 11, 0.12);
      border: 2px solid #F59E0B;
      color: #F59E0B;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.5rem; font-weight: 900;
      margin: 0 auto 16px auto;
      box-shadow: 0 0 20px rgba(245, 158, 11, 0.2);
    }
    .modal-check-icon {
      width: 56px; height: 56px;
      border-radius: 50%;
      background: rgba(59, 130, 246, 0.12);
      border: 2px solid #3B82F6;
      color: #3B82F6;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.5rem; font-weight: 900;
      margin: 0 auto 16px auto;
      box-shadow: 0 0 20px rgba(59, 130, 246, 0.2);
    }
    .modal-title { font-size: 1.3rem; font-weight: 800; color: #FFF; margin-bottom: 8px; }
    .modal-subtitle { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px; }
    .modal-instruction-box {
      background: rgba(30, 41, 59, 0.8);
      border: 1px solid rgba(255, 255, 255, 0.06);
      border-radius: 14px;
      padding: 18px;
      text-align: left;
      margin-bottom: 16px;
    }
    .modal-instruction-header { font-size: 0.875rem; font-weight: 800; color: #FFF; margin-bottom: 10px; }
    .modal-instruction-list { font-size: 0.825rem; color: #94A3B8; line-height: 1.7; padding-left: 0; list-style: none; margin: 0; }
    .modal-subtext { font-size: 0.8rem; color: var(--text-muted); font-family: monospace; margin-bottom: 24px; }
    .modal-success-icon {
      width: 56px; height: 56px;
      border-radius: 50%;
      background: rgba(16, 185, 129, 0.15);
      border: 2px solid #10B981;
      color: #10B981;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.5rem; font-weight: 900;
      margin: 0 auto 16px auto;
      box-shadow: 0 0 20px rgba(16, 185, 129, 0.3);
    }
    .modal-progress-bar-container {
      width: 100%;
      height: 6px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 4px;
      overflow: hidden;
      margin-top: 20px;
    }
    .modal-progress-bar-fill {
      height: 100%;
      background: linear-gradient(90deg, #A855F7 0%, #3B82F6 100%);
      border-radius: 4px;
      transition: width 0.3s ease;
    }
    .modal-progress-footer { font-size: 0.775rem; color: var(--text-muted); font-weight: 700; margin-top: 8px; font-family: monospace; }

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
          <button class="menu-item" id="sNavDash" onclick="switchTab('generator')">
            <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8v-10h-8v10zm0-18v6h8V3h-8z"/></svg>
            <span>Dashboard</span>
          </button>

          <button class="menu-item" id="sNavCheck" onclick="switchTab('check')">
            <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
            <span>Server Status</span>
          </button>

          <button class="menu-item active" id="sNavGen" onclick="switchTab('generator')">
            <svg viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
            <span>AM Generator</span>
          </button>

          <button class="menu-item" id="sNavGen2" onclick="switchTab('generator')">
            <svg viewBox="0 0 24 24"><path d="M12 2l2.4 7.4H22l-6 4.6 2.3 7.2-6.3-4.6-6.3 4.6 2.3-7.2-6-4.6h7.6z"/></svg>
            <span>AM Generator v2</span>
          </button>

          <button class="menu-item" id="sNavFaq" onclick="switchTab('faq')">
            <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
            <span>Temp Mail</span>
          </button>
        </nav>
      </div>

      <div class="sidebar-footer">
        <span>Status Server:</span>
        <span class="server-status-dot">Online</span>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main class="main-content" style="position:relative;">

      <!-- Top Right Sponsor Ad Widget (Adsterra) -->
      <div class="sponsor-ad-widget" id="sponsorWidget">
        <div class="sponsor-ad-badge">1</div>
        <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=100&auto=format&fit=crop&q=80" alt="Adsterra Ad" />
        <div>
          <div class="sponsor-ad-title">The Best Times to Quit Your Job</div>
          <div style="display:flex; gap:10px; align-items:center; margin-top:2px;">
            <span class="sponsor-ad-sub" onclick="window.open(ADSTERRA_URL,'_blank')">Learn More</span>
            <span style="font-size:0.75rem; color:#64748B; cursor:pointer;" onclick="document.getElementById('sponsorWidget').style.display='none'">Hide</span>
          </div>
        </div>
      </div>

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
            <div class="step-num" id="stepNum1">1</div>
            <div class="step-text" id="stepTxt1">Hubungkan Akun</div>
          </div>
          <div id="stepCard2" class="step-card">
            <div class="step-num" id="stepNum2">2</div>
            <div class="step-text" id="stepTxt2">Tonton 5 Iklan</div>
          </div>
          <div id="stepCard3" class="step-card">
            <div class="step-num" id="stepNum3">3</div>
            <div class="step-text" id="stepTxt3">Premium Aktif!</div>
          </div>
        </div>

        <!-- Panel Form Container Card -->
        <div class="panel-card" style="position:relative;">

          <!-- STEP 1A FORM: ENTER EMAIL -->
          <div id="step1AView">
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

          <!-- STEP 1B FORM: VERIFY MAGIC LINK -->
          <div id="step1BView" style="display:none;">
            <div class="alert-banner-green">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
              <span>Magic link terkirim! Cek email Anda (termasuk spam).</span>
            </div>

            <div class="panel-header">
              <div class="panel-icon-box icon-box-purple">
                <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
              </div>
              <div>
                <div class="panel-title">Verifikasi Magic Link</div>
                <div class="panel-desc">Cek email Anda (termasuk folder spam), copy link yang dikirim, paste di bawah</div>
              </div>
            </div>

            <form onsubmit="handleVerifyLink(event)">
              <div class="form-group">
                <label for="linkInput" class="form-label" style="font-family:monospace; font-size:0.85rem; letter-spacing:0;">Magic Link dari Email</label>
                <div class="input-wrapper">
                  <input type="url" id="linkInput" class="input-field" placeholder="https://alightcreative.com?oobCode=..." required autocomplete="off" />
                </div>
              </div>

              <div class="btn-row">
                <button type="button" class="btn-secondary" onclick="setWizardStep('1A')">
                  <span>Kembali</span>
                </button>
                <button type="submit" id="btnStep1B" class="btn-blue">
                  <span>Verifikasi & Tautkan</span>
                </button>
              </div>
            </form>
          </div>

          <!-- STEP 2 FORM: WATCH 5 ADS -->
          <div id="step2View" style="display:none;">
            <div class="alert-banner-green">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
              <span>Akun Firebase berhasil ditautkan! Sekarang selesaikan 5 iklan.</span>
            </div>

            <div class="panel-header">
              <div class="panel-icon-box">
                <svg viewBox="0 0 24 24"><path d="M21 3H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h5v2h8v-2h5c1.1 0 1.99-.9 1.99-2L23 5c0-1.1-.9-2-2-2zm0 14H3V5h18v12z"/></svg>
              </div>
              <div>
                <div class="panel-title">Langkah 2: Selesaikan 5 Iklan</div>
                <div class="panel-desc">Tonton 5 iklan untuk mengaktifkan premium</div>
              </div>
            </div>

            <!-- 5 SUB-AD STATUS CARDS -->
            <div class="ad-sub-grid">
              <div class="ad-sub-box active" id="subAdBox1">
                <div class="ad-sub-num" id="subAdNum1">1</div>
                <div class="ad-sub-lbl" id="subAdLbl1">Next</div>
              </div>
              <div class="ad-sub-box" id="subAdBox2">
                <div class="ad-sub-num" id="subAdNum2">2</div>
                <div class="ad-sub-lbl" id="subAdLbl2">Locked</div>
              </div>
              <div class="ad-sub-box" id="subAdBox3">
                <div class="ad-sub-num" id="subAdNum3">3</div>
                <div class="ad-sub-lbl" id="subAdLbl3">Locked</div>
              </div>
              <div class="ad-sub-box" id="subAdBox4">
                <div class="ad-sub-num" id="subAdNum4">4</div>
                <div class="ad-sub-lbl" id="subAdLbl4">Locked</div>
              </div>
              <div class="ad-sub-box" id="subAdBox5">
                <div class="ad-sub-num" id="subAdNum5">5</div>
                <div class="ad-sub-lbl" id="subAdLbl5">Locked</div>
              </div>
            </div>

            <button type="button" id="btnMainAdTask" class="btn-blue" style="width:100%; padding:18px;" onclick="triggerAdModal()">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M21 3H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h5v2h8v-2h5c1.1 0 1.99-.9 1.99-2L23 5c0-1.1-.9-2-2-2zm0 14H3V5h18v12z"/></svg>
              <span id="btnMainAdText">Tonton Iklan #1</span>
            </button>
          </div>

          <!-- STEP 3 FORM & RESULT -->
          <div id="step3View" style="display:none;">
            <!-- Pre-activation Box (Before clicking activate button) -->
            <div id="step3PendingBox">
              <div class="alert-banner-green">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                <span>5/5 Iklan selesai! Siap untuk mengaktifkan lisensi Premium.</span>
              </div>

              <div class="panel-header">
                <div class="panel-icon-box" style="background:rgba(16, 185, 129, 0.12); border-color:rgba(16, 185, 129, 0.3); color:#34D399;">
                  <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
                </div>
                <div>
                  <div class="panel-title">Langkah 3: Aktifkan Premium</div>
                  <div class="panel-desc">Klik tombol di bawah untuk mengaktifkan akun Alight Motion Anda secara otomatis</div>
                </div>
              </div>

              <div style="background:var(--input-bg); border:1px solid var(--card-border); border-radius:12px; padding:16px; margin-bottom:24px;">
                <div class="data-row">
                  <span class="data-key">Email Terhubung</span>
                  <span id="step3PreEmail" class="data-val" style="color:var(--text-title); font-weight:700;">-</span>
                </div>
                <div class="data-row">
                  <span class="data-key">Status Syarat</span>
                  <span style="color:#34D399; font-weight:800;">✓ 5/5 Iklan Selesai</span>
                </div>
              </div>

              <button type="button" id="btnActivateFinal" class="btn-blue" style="width:100%; padding:18px; font-weight:800; background:linear-gradient(135deg, #10B981, #059669); box-shadow:0 6px 20px rgba(16,185,129,0.3);" onclick="processFinalActivation()">
                <span>🚀 Aktifkan Premium Sekarang</span>
              </button>
            </div>

            <!-- Post-activation Result Box (Shown after clicking activate button) -->
            <div id="step3SuccessBox" style="display:none;">
              <div class="success-box">
                <div class="success-header">
                  <div class="success-badge-icon">
                    <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                  </div>
                  <div>
                    <h3 style="color:#34D399; font-size:1.1rem; font-weight:800;">Akun Berhasil Diaktifkan! 🎉</h3>
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

        </div>

        <!-- AD WATCHING MODAL DIALOG (Screenshots 4 & 5) -->
        <div id="adModal" class="modal-overlay">
          <div class="modal-card">
            <div id="adModalIcon" class="modal-warn-icon">!</div>
            
            <div class="modal-title" id="adModalTitle">Iklan 1 / 5</div>
            <div class="modal-subtitle" id="adModalSubtitle" style="display:none;">Klik tombol di bawah setelah kamu kembali dari halaman mitra.</div>
            
            <div id="adModalInstructions" class="modal-instruction-box">
              <div class="modal-instruction-header">Kamu akan diarahkan ke halaman mitra. <span style="color:#F59E0B;">Jangan panik!</span></div>
              <ul class="modal-instruction-list">
                <li>• Setelah halaman mitra terbuka, <strong>tutup tab</strong> tersebut</li>
                <li>• Kembali ke halaman ini</li>
                <li>• Klik <strong>"Ya, Saya Sudah Kembali"</strong></li>
              </ul>
            </div>

            <div id="adModalSubtext" class="modal-subtext">Iklan ini membantu kami tetap gratis. Terima kasih! 🙏</div>

            <button type="button" id="btnModalAction" class="btn-blue" style="width:100%; padding:16px; font-weight:800;" onclick="handleAdModalButtonClick()">
              <span>Buka Iklan</span>
            </button>

            <div class="modal-progress-bar-container">
              <div class="modal-progress-bar-fill" id="adModalBarFill" style="width: 0%;"></div>
            </div>
            <div class="modal-progress-footer" id="adModalProgress">Progress: 0/5</div>
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

  <!-- SUPABASE CLIENT SDK CDN -->
  <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>

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
    const API_KEY = "freeapikeydhan26";
    const BASE_API = "https://restapidhan.vercel.app/api/am";
    const ADSTERRA_URL = "https://www.effectivecpmnetwork.com/wskt458y07?key=66553d3bb5d5f17dd927dcc9e7577999";
    const appState = { email: '', codeOrder: '', loading: false, apiCount: 1482 };

    // SUPABASE CLOUD DATABASE CONFIGURATION
    const SUPABASE_URL = "https://ycfiwrbwgzkrnvdasman.supabase.co"; 
    const SUPABASE_ANON_KEY = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InljZml3cmJ3Z3prcm52ZGFzbWFuIiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODYwMTg3MTQsImV4cCI6MjEwMTU5NDcxNH0.pLY_sg-wpe13nTiCIOdw3Elc2iG1icUEizkeVIjW3Jg";
    let supabaseClient = null;

    if (typeof window.supabase !== 'undefined' && SUPABASE_URL && SUPABASE_ANON_KEY) {
      try {
        supabaseClient = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);
      } catch (e) {}
    }

    async function callApi(action, params = {}) {
      // 1. Supabase Cloud Database Query Handler (Cloudflare Pages 24/7 Storage)
      if (supabaseClient) {
        try {
          if (action === 'stats') {
            const { count, error } = await supabaseClient
              .from('activations')
              .select('*', { count: 'exact', head: true });
            
            const { data: recent } = await supabaseClient
              .from('activations')
              .select('email, code_order, created_at')
              .order('id', { ascending: false })
              .limit(5);

            if (!error) {
              return {
                status: true,
                total: count || 0,
                recent: recent || []
              };
            }
          }

          if (action === 'search') {
            const code = params.code || '';
            const formattedCode = code.toUpperCase().startsWith('AM-') ? code.toUpperCase() : 'AM-' + code.toUpperCase();
            
            const { data, error } = await supabaseClient
              .from('activations')
              .select('*')
              .or(`code_order.eq.${code.toUpperCase()},code_order.eq.${formattedCode}`)
              .limit(1);

            if (!error && data && data.length > 0) {
              return { status: true, found: true, data: data[0] };
            }
          }
        } catch (e) {}
      }

      // 2. Try local PHP proxy if running on local PHP environment
      const isStaticEnv = window.location.protocol === 'file:' || 
                          window.location.hostname.includes('pages.dev') || 
                          window.location.hostname.includes('github.io') ||
                          window.location.hostname.includes('vercel.app');

      if (!isStaticEnv) {
        try {
          const query = new URLSearchParams({ api_action: action, ...params }).toString();
          const res = await fetch(`index.php?${query}`);
          const contentType = res.headers.get("content-type") || "";
          if (res.ok && contentType.includes("application/json")) {
            const data = await res.json();
            if (data && data.status !== undefined) return data;
          }
        } catch (e) {}
      }

      // 3. Direct Vercel REST API fallback for Cloudflare Pages / Static Hosting
      try {
        const queryParams = new URLSearchParams({ action, apikey: API_KEY, ...params }).toString();
        const res = await fetch(`${BASE_API}?${queryParams}`);
        const data = await res.json().catch(() => ({ status: false, message: "Respon server tidak valid." }));

        // Automatically save successful verifications to Supabase Cloud Database!
        if (action === 'verif' && data && data.status !== false && !data.error && supabaseClient) {
          const codeOrder = data.codeorder || data.code_order || data.code || ('AM-' + Math.floor(100000 + Math.random() * 900000));
          try {
            await supabaseClient.from('activations').insert([
              { email: params.email, code_order: codeOrder, status: 'ACTIVE PREMIUM' }
            ]);
            const { count } = await supabaseClient.from('activations').select('*', { count: 'exact', head: true });
            if (count) data.db_total = count;
          } catch (err) {}
        }

        return data;
      } catch (err) {
        return { status: false, message: "Gagal terhubung ke server API." };
      }
    }

    function formatRelativeTime(dateStr) {
      if (!dateStr) return "Baru saja";
      const date = new Date(dateStr.replace(' ', 'T'));
      if (isNaN(date.getTime())) return dateStr;
      
      const now = new Date();
      const diffSec = Math.floor((now - date) / 1000);
      
      if (diffSec < 60) return "Baru saja";
      const diffMin = Math.floor(diffSec / 60);
      if (diffMin < 60) return `${diffMin} menit lalu`;
      const diffHour = Math.floor(diffMin / 60);
      if (diffHour < 24) return `${diffHour} jam lalu`;
      const diffDay = Math.floor(diffHour / 24);
      return `${diffDay} hari lalu`;
    }

    function getAvatarGradient(char) {
      const gradients = [
        'linear-gradient(135deg, #10B981, #059669)',
        'linear-gradient(135deg, #3B82F6, #1D4ED8)',
        'linear-gradient(135deg, #8B5CF6, #6D28D9)',
        'linear-gradient(135deg, #EC4899, #BE185D)',
        'linear-gradient(135deg, #F59E0B, #D97706)'
      ];
      const code = char.charCodeAt(0) || 0;
      return gradients[code % gradients.length];
    }

    async function fetchApiKeyStats() {
      try {
        const data = await callApi('stats');
        if (data && data.status && typeof data.total !== 'undefined') {
          appState.apiCount = data.total;
        }
        if (data && data.recent && Array.isArray(data.recent) && data.recent.length > 0) {
          const container = document.getElementById("feedListContainer");
          container.innerHTML = "";
          data.recent.reverse().forEach(item => {
            addActivationToFeed(item.email, item.code_order, item.created_at);
          });
        }
      } catch (e) {
        // Smooth fallback
      }
      document.getElementById("apiTotalCount").textContent = appState.apiCount.toLocaleString();
    }

    function addActivationToFeed(email, code, timeStr = null) {
      const container = document.getElementById("feedListContainer");
      const firstChar = email ? email.charAt(0).toUpperCase() : 'A';
      const maskedEmail = (email && email.length > 8) ? email.substring(0, 4) + '***' + email.substring(email.indexOf('@')) : email;
      const displayTime = formatRelativeTime(timeStr);
      const bgGradient = getAvatarGradient(firstChar);

      const newItem = document.createElement("div");
      newItem.className = "feed-item";
      newItem.innerHTML = `
        <div class="feed-user-info">
          <div class="feed-avatar" style="background:${bgGradient};">${firstChar}</div>
          <div>
            <div class="feed-email">${maskedEmail}</div>
            <div class="feed-time">${displayTime} • Kode Order: ${code}</div>
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

    let watchedAdsCount = 0;
    let adModalState = 'initial'; // 'initial' (Buka Iklan) or 'opened' (Ya, Saya Sudah Kembali)

    function setWizardStep(stepNum) {
      document.getElementById("step1AView").style.display = (stepNum === 1 || stepNum === '1A') ? "block" : "none";
      document.getElementById("step1BView").style.display = stepNum === '1B' ? "block" : "none";
      document.getElementById("step2View").style.display = stepNum === 2 ? "block" : "none";
      document.getElementById("step3View").style.display = stepNum === 3 ? "block" : "none";

      const card1 = document.getElementById("stepCard1");
      const card2 = document.getElementById("stepCard2");
      const card3 = document.getElementById("stepCard3");

      const num1 = document.getElementById("stepNum1");
      const num2 = document.getElementById("stepNum2");
      const num3 = document.getElementById("stepNum3");

      card1.className = "step-card";
      card2.className = "step-card";
      card3.className = "step-card";

      if (stepNum === 1 || stepNum === '1A' || stepNum === '1B') {
        card1.classList.add("active");
        num1.textContent = "1";
        num2.textContent = "2";
        num3.textContent = "3";
      } else if (stepNum === 2) {
        card1.classList.add("completed");
        num1.textContent = "✓";
        card2.classList.add("active");
        num2.textContent = "2";
        num3.textContent = "3";
      } else if (stepNum === 3) {
        card1.classList.add("completed");
        num1.textContent = "✓";
        card2.classList.add("completed");
        num2.textContent = "✓";
        card3.classList.add("active");
        num3.textContent = "3";

        const emailInp = appState.email || document.getElementById("emailInput").value.trim();
        document.getElementById("step3PreEmail").textContent = emailInp || '-';
      }
    }

    function triggerAdModal() {
      if (watchedAdsCount >= 5) {
        showAdCompletedModal();
        return;
      }
      adModalState = 'initial';
      const currentAd = watchedAdsCount + 1;
      
      const icon = document.getElementById("adModalIcon");
      icon.className = "modal-warn-icon";
      icon.textContent = "!";
      
      document.getElementById("adModalTitle").textContent = `Iklan ${currentAd} / 5`;
      document.getElementById("adModalSubtitle").style.display = "none";
      document.getElementById("adModalInstructions").style.display = "block";
      document.getElementById("adModalSubtext").style.display = "block";

      const percent = Math.round((watchedAdsCount / 5) * 100);
      document.getElementById("adModalBarFill").style.width = `${percent}%`;
      document.getElementById("adModalProgress").textContent = `Progress: ${watchedAdsCount}/5`;

      const btn = document.getElementById("btnModalAction");
      btn.innerHTML = `<span>Buka Iklan</span>`;
      btn.className = "btn-blue";

      document.getElementById("adModal").classList.add("show");
    }

    function handleAdModalButtonClick() {
      const btn = document.getElementById("btnModalAction");
      const currentAd = watchedAdsCount + 1;

      if (adModalState === 'initial') {
        // 1. Open ad in new tab
        window.open(ADSTERRA_URL, '_blank');
        
        // 2. Switch modal state to "Sudah Kembali?" (Screenshot 5)
        adModalState = 'opened';
        
        const icon = document.getElementById("adModalIcon");
        icon.className = "modal-check-icon";
        icon.textContent = "✓";

        document.getElementById("adModalTitle").textContent = "Sudah Kembali?";
        document.getElementById("adModalSubtitle").innerHTML = `<div style="color:var(--text-sub); font-size:0.85rem; margin-bottom:4px;">Iklan ${currentAd} / 5</div><div style="font-size:0.825rem; color:var(--text-muted);">Klik tombol di bawah setelah kamu kembali dari halaman mitra.</div>`;
        document.getElementById("adModalSubtitle").style.display = "block";
        document.getElementById("adModalInstructions").style.display = "none";
        document.getElementById("adModalSubtext").style.display = "none";

        btn.innerHTML = `<span>Ya, Saya Sudah Kembali</span>`;
      } else if (adModalState === 'opened') {
        // 3. User clicked "Ya, Saya Sudah Kembali"
        watchedAdsCount++;
        updateSubAdBoxes();

        if (watchedAdsCount >= 5) {
          // Switch to Screenshot 6 "Semua Iklan Selesai! 🎉" State inside modal
          showAdCompletedModal();
        } else {
          document.getElementById("adModal").classList.remove("show");
        }
      } else if (adModalState === 'completed') {
        // 4. User clicked "Lanjutkan →" in Screenshot 6 State
        document.getElementById("adModal").classList.remove("show");
        processFinalActivation();
      }
    }

    function showAdCompletedModal() {
      adModalState = 'completed';

      const icon = document.getElementById("adModalIcon");
      icon.className = "modal-success-icon";
      icon.textContent = "✓";

      document.getElementById("adModalTitle").textContent = "Semua Iklan Selesai! 🎉";
      document.getElementById("adModalSubtitle").innerHTML = `<div style="font-size:0.875rem; color:var(--text-sub);">Terima kasih! Kamu sudah menyelesaikan 5 iklan.</div>`;
      document.getElementById("adModalSubtitle").style.display = "block";
      document.getElementById("adModalInstructions").style.display = "none";
      document.getElementById("adModalSubtext").style.display = "none";

      const btn = document.getElementById("btnModalAction");
      btn.innerHTML = `<span>Lanjutkan →</span>`;
      btn.className = "btn-blue";

      document.getElementById("adModalBarFill").style.width = "100%";
      document.getElementById("adModalProgress").textContent = "Progress: 5/5";

      document.getElementById("adModal").classList.add("show");
    }

    function updateSubAdBoxes() {
      for (let i = 1; i <= 5; i++) {
        const box = document.getElementById(`subAdBox${i}`);
        const lbl = document.getElementById(`subAdLbl${i}`);
        const num = document.getElementById(`subAdNum${i}`);

        box.className = "ad-sub-box";
        if (i <= watchedAdsCount) {
          box.classList.add("completed");
          num.textContent = "✓";
          lbl.textContent = "Done";
        } else if (i === watchedAdsCount + 1) {
          box.classList.add("active");
          num.textContent = i.toString();
          lbl.textContent = "Next";
        } else {
          num.textContent = i.toString();
          lbl.textContent = "Locked";
        }
      }

      const btnMainText = document.getElementById("btnMainAdText");
      if (watchedAdsCount < 5) {
        btnMainText.textContent = `Tonton Iklan #${watchedAdsCount + 1}`;
      } else {
        btnMainText.textContent = `🚀 Aktifkan Premium Sekarang!`;
        document.getElementById("btnMainAdTask").style.background = "linear-gradient(135deg, #10B981, #059669)";
      }
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
        const data = await callApi('send', { email });

        if (data.status === false || data.error) {
          throw new Error(data.message || data.error || "Gagal mengirim magic link.");
        }

        appState.email = email;
        showToast("Magic link terkirim ke email!", "success");
        setWizardStep('1B');
      } catch (err) {
        showToast(err.message || "Gagal terhubung ke server.");
      } finally {
        btn.disabled = false;
        btn.innerHTML = `<span>Kirim Magic Link</span>`;
      }
    }

    function handleVerifyLink(e) {
      e.preventDefault();
      const link = document.getElementById("linkInput").value.trim();
      if (!link) return showToast("Masukkan link verifikasi.");

      appState.link = link;
      showToast("Tautan berhasil! Silakan selesaikan 5 iklan.", "success");
      setWizardStep(2);
    }

    async function processFinalActivation() {
      const btn = document.getElementById("btnActivateFinal");
      const btnMain = document.getElementById("btnMainAdTask");
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<div class="spinner"></div><span>Memproses Aktivasi Premium...</span>`;
      }
      if (btnMain) btnMain.disabled = true;

      try {
        const data = await callApi('verif', { email: appState.email, url: appState.link || 'https://alightcreative.com' });

        if (data.status === false || data.error) {
          throw new Error(data.message || data.error || "Verifikasi tidak valid.");
        }

        const code = data.codeorder || data.code_order || data.code || `AM-${Math.floor(100000 + Math.random() * 900000)}`;
        appState.codeOrder = code;

        document.getElementById("resEmail").textContent = appState.email;
        document.getElementById("resCode").textContent = code;

        if (data.db_total) {
          appState.apiCount = data.db_total;
        } else {
          appState.apiCount++;
        }
        document.getElementById("apiTotalCount").textContent = appState.apiCount.toLocaleString();
        addActivationToFeed(appState.email, code);

        showToast("Akun berhasil diaktifkan!", "success");
        
        document.getElementById("step3PendingBox").style.display = "none";
        document.getElementById("step3SuccessBox").style.display = "block";
        setWizardStep(3);
      } catch (err) {
        showToast(err.message || "Verifikasi gagal.");
      } finally {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = `<span>🚀 Aktifkan Premium Sekarang</span>`;
        }
        if (btnMain) btnMain.disabled = false;
      }
    }

    function resetWizard() {
      appState.email = '';
      appState.codeOrder = '';
      appState.link = '';
      watchedAdsCount = 0;
      updateSubAdBoxes();
      document.getElementById("emailInput").value = '';
      document.getElementById("linkInput").value = '';
      document.getElementById("step3PendingBox").style.display = "block";
      document.getElementById("step3SuccessBox").style.display = "none";
      setWizardStep(1);
    }

    // Initialize API Key stats on page load & auto-poll every 10s
    document.addEventListener("DOMContentLoaded", () => {
      fetchApiKeyStats();
      setInterval(fetchApiKeyStats, 10000);
    });
  </script>
</body>
</html>
