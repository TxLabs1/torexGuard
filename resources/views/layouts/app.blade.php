<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Torex Guard</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js" defer></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        /* ─── CSS Variables ───────────────────────────────────────── */
        :root {
            --tg-bg-primary:     #060b14;
            --tg-bg-secondary:   #0a1020;
            --tg-bg-card:        #0d1526;
            --tg-bg-card-hover:  #111d33;
            --tg-border:         rgba(0, 255, 136, 0.08);
            --tg-border-hover:   rgba(0, 255, 136, 0.25);
            --tg-accent:         #00ff88;
            --tg-accent-dim:     rgba(0, 255, 136, 0.12);
            --tg-accent-glow:    0 0 20px rgba(0, 255, 136, 0.3);
            --tg-critical:       #ff3366;
            --tg-critical-dim:   rgba(255, 51, 102, 0.12);
            --tg-high:           #ff6b35;
            --tg-high-dim:       rgba(255, 107, 53, 0.12);
            --tg-medium:         #ffd700;
            --tg-medium-dim:     rgba(255, 215, 0, 0.12);
            --tg-low:            #00d4ff;
            --tg-low-dim:        rgba(0, 212, 255, 0.12);
            --tg-text-primary:   #e8f0fe;
            --tg-text-secondary: #8099c0;
            --tg-text-muted:     #4a6080;
            --tg-sidebar-width:  260px;
            --tg-font-display:   'Rajdhani', sans-serif;
            --tg-font-body:      'Inter', sans-serif;
            --tg-font-mono:      'JetBrains Mono', monospace;
        }

        /* ─── Base ────────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--tg-bg-primary);
            color: var(--tg-text-primary);
            font-family: var(--tg-font-body);
            font-size: 14px;
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Grid noise texture overlay */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                repeating-linear-gradient(0deg, transparent, transparent 39px, rgba(0,255,136,0.015) 40px),
                repeating-linear-gradient(90deg, transparent, transparent 39px, rgba(0,255,136,0.015) 40px);
            pointer-events: none;
            z-index: 0;
        }

        /* ─── Sidebar ─────────────────────────────────────────────── */
        .tg-sidebar {
            position: fixed;
            top: 0; left: 0; bottom: 0;
            width: var(--tg-sidebar-width);
            background: var(--tg-bg-secondary);
            border-right: 1px solid var(--tg-border);
            display: flex;
            flex-direction: column;
            z-index: 100;
            transition: transform 0.3s ease;
        }

        .tg-sidebar-logo {
            padding: 24px 20px 20px;
            border-bottom: 1px solid var(--tg-border);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .tg-logo-icon {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, var(--tg-accent), #00aaff);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #060b14;
            box-shadow: var(--tg-accent-glow);
            flex-shrink: 0;
        }

        .tg-logo-text {
            font-family: var(--tg-font-display);
            font-size: 20px;
            font-weight: 700;
            color: var(--tg-text-primary);
            letter-spacing: 0.5px;
        }

        .tg-logo-text span {
            color: var(--tg-accent);
        }

        .tg-logo-badge {
            font-family: var(--tg-font-mono);
            font-size: 9px;
            color: var(--tg-accent);
            background: var(--tg-accent-dim);
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: auto;
            flex-shrink: 0;
        }

        /* Nav */
        .tg-nav {
            flex: 1;
            padding: 12px 0;
            overflow-y: auto;
        }

        .tg-nav-section {
            padding: 8px 16px 4px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--tg-text-muted);
            margin-top: 8px;
        }

        .tg-nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            color: var(--tg-text-secondary);
            text-decoration: none;
            border-radius: 0;
            transition: all 0.2s ease;
            position: relative;
            font-size: 13.5px;
            font-weight: 500;
        }

        .tg-nav-item:hover {
            color: var(--tg-text-primary);
            background: rgba(0, 255, 136, 0.05);
        }

        .tg-nav-item.active {
            color: var(--tg-accent);
            background: var(--tg-accent-dim);
        }

        .tg-nav-item.active::before {
            content: '';
            position: absolute;
            left: 0; top: 4px; bottom: 4px;
            width: 3px;
            background: var(--tg-accent);
            border-radius: 0 3px 3px 0;
            box-shadow: 0 0 8px var(--tg-accent);
        }

        .tg-nav-item .nav-icon {
            width: 18px;
            text-align: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .tg-nav-badge {
            margin-left: auto;
            font-size: 10px;
            font-family: var(--tg-font-mono);
            background: var(--tg-critical-dim);
            color: var(--tg-critical);
            padding: 1px 7px;
            border-radius: 20px;
            border: 1px solid rgba(255, 51, 102, 0.2);
        }

        .tg-nav-badge.green {
            background: var(--tg-accent-dim);
            color: var(--tg-accent);
            border-color: rgba(0, 255, 136, 0.2);
        }

        /* Sidebar Bottom */
        .tg-sidebar-bottom {
            padding: 16px;
            border-top: 1px solid var(--tg-border);
        }

        .tg-user-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--tg-border);
            cursor: pointer;
            transition: all 0.2s;
        }

        .tg-user-card:hover { background: rgba(255,255,255,0.06); }

        .tg-user-avatar {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--tg-accent), #00aaff);
            display: flex; align-items: center; justify-content: center;
            font-family: var(--tg-font-display);
            font-weight: 700;
            font-size: 14px;
            color: #060b14;
            flex-shrink: 0;
        }

        .tg-user-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--tg-text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .tg-user-role {
            font-size: 11px;
            color: var(--tg-text-muted);
            font-family: var(--tg-font-mono);
        }

        /* ─── Main Content ────────────────────────────────────────── */
        .tg-main {
            margin-left: var(--tg-sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ─── Topbar ──────────────────────────────────────────────── */
        .tg-topbar {
            position: sticky;
            top: 0;
            z-index: 90;
            background: rgba(6, 11, 20, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--tg-border);
            padding: 0 28px;
            height: 62px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .tg-page-title {
            font-family: var(--tg-font-display);
            font-size: 20px;
            font-weight: 700;
            color: var(--tg-text-primary);
            letter-spacing: 0.3px;
            flex: 1;
        }

        .tg-topbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Alert bell */
        .tg-alert-btn {
            position: relative;
            width: 38px; height: 38px;
            border-radius: 10px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--tg-border);
            color: var(--tg-text-secondary);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .tg-alert-btn:hover {
            color: var(--tg-accent);
            border-color: var(--tg-border-hover);
        }

        .tg-alert-dot {
            position: absolute;
            top: 7px; right: 7px;
            width: 8px; height: 8px;
            border-radius: 50%;
            background: var(--tg-critical);
            border: 2px solid var(--tg-bg-primary);
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(0.9); }
        }

        /* Live indicator */
        .tg-live-indicator {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-family: var(--tg-font-mono);
            color: var(--tg-accent);
            background: var(--tg-accent-dim);
            padding: 5px 10px;
            border-radius: 20px;
            border: 1px solid rgba(0, 255, 136, 0.2);
        }

        .tg-live-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--tg-accent);
            animation: blink 1.4s infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.2; }
        }

        /* ─── Page Content ────────────────────────────────────────── */
        .tg-content {
            flex: 1;
            padding: 28px;
            position: relative;
            z-index: 1;
        }

        /* ─── Cards ───────────────────────────────────────────────── */
        .tg-card {
            background: var(--tg-bg-card);
            border: 1px solid var(--tg-border);
            border-radius: 14px;
            transition: border-color 0.2s, background 0.2s;
        }

        .tg-card:hover {
            border-color: var(--tg-border-hover);
        }

        .tg-card-header {
            padding: 18px 22px 14px;
            border-bottom: 1px solid var(--tg-border);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tg-card-title {
            font-family: var(--tg-font-display);
            font-size: 15px;
            font-weight: 600;
            color: var(--tg-text-primary);
            letter-spacing: 0.3px;
            flex: 1;
        }

        .tg-card-body {
            padding: 20px 22px;
        }

        /* ─── Stat Cards ──────────────────────────────────────────── */
        .tg-stat-card {
            background: var(--tg-bg-card);
            border: 1px solid var(--tg-border);
            border-radius: 14px;
            padding: 20px 22px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .tg-stat-card::after {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 80px; height: 80px;
            border-radius: 50%;
            transform: translate(30%, -30%);
            opacity: 0.07;
        }

        .tg-stat-card.critical::after { background: var(--tg-critical); }
        .tg-stat-card.high::after { background: var(--tg-high); }
        .tg-stat-card.medium::after { background: var(--tg-medium); }
        .tg-stat-card.accent::after { background: var(--tg-accent); }

        .tg-stat-card:hover { border-color: var(--tg-border-hover); transform: translateY(-2px); }

        .tg-stat-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--tg-text-muted);
            margin-bottom: 10px;
        }

        .tg-stat-value {
            font-family: var(--tg-font-display);
            font-size: 36px;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 8px;
        }

        .tg-stat-value.critical { color: var(--tg-critical); }
        .tg-stat-value.high { color: var(--tg-high); }
        .tg-stat-value.medium { color: var(--tg-medium); }
        .tg-stat-value.accent { color: var(--tg-accent); }

        .tg-stat-sub {
            font-size: 12px;
            color: var(--tg-text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .tg-stat-icon {
            position: absolute;
            bottom: 16px; right: 20px;
            font-size: 28px;
            opacity: 0.08;
        }

        /* ─── Badges ──────────────────────────────────────────────── */
        .badge-critical { background: var(--tg-critical-dim); color: var(--tg-critical); border: 1px solid rgba(255,51,102,0.25); }
        .badge-high { background: var(--tg-high-dim); color: var(--tg-high); border: 1px solid rgba(255,107,53,0.25); }
        .badge-medium { background: var(--tg-medium-dim); color: var(--tg-medium); border: 1px solid rgba(255,215,0,0.25); }
        .badge-low { background: var(--tg-low-dim); color: var(--tg-low); border: 1px solid rgba(0,212,255,0.25); }
        .badge-success { background: var(--tg-accent-dim); color: var(--tg-accent); border: 1px solid rgba(0,255,136,0.25); }

        .tg-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            font-family: var(--tg-font-mono);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ─── Tables ──────────────────────────────────────────────── */
        .tg-table { width: 100%; border-collapse: collapse; }

        .tg-table th {
            padding: 10px 16px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--tg-text-muted);
            border-bottom: 1px solid var(--tg-border);
            text-align: left;
            white-space: nowrap;
        }

        .tg-table td {
            padding: 13px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.03);
            color: var(--tg-text-secondary);
            font-size: 13px;
            vertical-align: middle;
        }

        .tg-table tbody tr {
            transition: background 0.15s;
        }

        .tg-table tbody tr:hover {
            background: rgba(0, 255, 136, 0.03);
        }

        .tg-table tbody tr:last-child td { border-bottom: none; }

        /* ─── Buttons ─────────────────────────────────────────────── */
        .btn-tg-primary {
            background: var(--tg-accent);
            color: #060b14;
            border: none;
            border-radius: 10px;
            padding: 9px 18px;
            font-family: var(--tg-font-display);
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .btn-tg-primary:hover {
            background: #00e07a;
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.4);
            transform: translateY(-1px);
        }

        .btn-tg-danger {
            background: var(--tg-critical-dim);
            color: var(--tg-critical);
            border: 1px solid rgba(255, 51, 102, 0.2);
            border-radius: 10px;
            padding: 8px 16px;
            font-family: var(--tg-font-display);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-tg-danger:hover {
            background: rgba(255, 51, 102, 0.2);
            border-color: rgba(255, 51, 102, 0.5);
        }

        .btn-tg-ghost {
            background: transparent;
            color: var(--tg-text-secondary);
            border: 1px solid var(--tg-border);
            border-radius: 10px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-tg-ghost:hover {
            border-color: var(--tg-border-hover);
            color: var(--tg-text-primary);
        }

        /* ─── Forms ───────────────────────────────────────────────── */
        .tg-form-control {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--tg-border);
            border-radius: 10px;
            padding: 10px 14px;
            color: var(--tg-text-primary);
            font-size: 13.5px;
            width: 100%;
            transition: all 0.2s;
            outline: none;
            font-family: var(--tg-font-body);
        }

        .tg-form-control:focus {
            border-color: var(--tg-accent);
            background: rgba(0, 255, 136, 0.04);
            box-shadow: 0 0 0 3px rgba(0, 255, 136, 0.1);
        }

        .tg-form-control::placeholder { color: var(--tg-text-muted); }

        .tg-form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: var(--tg-text-secondary);
            margin-bottom: 6px;
        }

        .tg-select {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--tg-border);
            border-radius: 10px;
            padding: 10px 14px;
            color: var(--tg-text-primary);
            font-size: 13.5px;
            width: 100%;
            outline: none;
            cursor: pointer;
            transition: all 0.2s;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%238099c0' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }

        .tg-select:focus {
            border-color: var(--tg-accent);
            box-shadow: 0 0 0 3px rgba(0, 255, 136, 0.1);
        }

        /* ─── Status Indicators ───────────────────────────────────── */
        .tg-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .tg-status-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .tg-status.online .tg-status-dot  { background: var(--tg-accent); box-shadow: 0 0 6px var(--tg-accent); animation: blink 2s infinite; }
        .tg-status.online { color: var(--tg-accent); }
        .tg-status.offline .tg-status-dot { background: var(--tg-text-muted); }
        .tg-status.offline { color: var(--tg-text-muted); }
        .tg-status.critical .tg-status-dot { background: var(--tg-critical); box-shadow: 0 0 6px var(--tg-critical); }
        .tg-status.critical { color: var(--tg-critical); }
        .tg-status.warning .tg-status-dot { background: var(--tg-medium); }
        .tg-status.warning { color: var(--tg-medium); }

        /* ─── Scrollbar ───────────────────────────────────────────── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(0, 255, 136, 0.15); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(0, 255, 136, 0.3); }

        /* ─── Threat Feed Item ────────────────────────────────────── */
        .tg-feed-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 13px 0;
            border-bottom: 1px solid rgba(255,255,255,0.03);
            animation: slideIn 0.4s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-12px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        .tg-feed-item:last-child { border-bottom: none; }

        .tg-feed-icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
        }

        .tg-feed-icon.critical { background: var(--tg-critical-dim); color: var(--tg-critical); }
        .tg-feed-icon.high     { background: var(--tg-high-dim); color: var(--tg-high); }
        .tg-feed-icon.medium   { background: var(--tg-medium-dim); color: var(--tg-medium); }
        .tg-feed-icon.low      { background: var(--tg-low-dim); color: var(--tg-low); }

        .tg-feed-body { flex: 1; min-width: 0; }

        .tg-feed-title {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--tg-text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .tg-feed-meta {
            font-size: 11.5px;
            color: var(--tg-text-muted);
            margin-top: 2px;
            font-family: var(--tg-font-mono);
        }

        /* ─── Progress Bars ───────────────────────────────────────── */
        .tg-progress {
            height: 5px;
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 6px;
        }

        .tg-progress-bar {
            height: 100%;
            border-radius: 10px;
            transition: width 0.8s ease;
        }

        .tg-progress-bar.accent  { background: var(--tg-accent); }
        .tg-progress-bar.critical { background: var(--tg-critical); }
        .tg-progress-bar.medium  { background: var(--tg-medium); }

        /* ─── Misc ────────────────────────────────────────────────── */
        .mono { font-family: var(--tg-font-mono); }
        .text-accent { color: var(--tg-accent); }
        .text-critical { color: var(--tg-critical); }
        .text-high { color: var(--tg-high); }
        .text-medium { color: var(--tg-medium); }
        .text-muted-tg { color: var(--tg-text-muted); }
        .text-secondary-tg { color: var(--tg-text-secondary); }

        /* Toast notifications */
        .tg-toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }

        .tg-toast {
            background: var(--tg-bg-card);
            border: 1px solid var(--tg-border);
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 300px;
            max-width: 380px;
            animation: toastIn 0.3s ease;
            pointer-events: auto;
        }

        @keyframes toastIn {
            from { opacity: 0; transform: translateX(20px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        .tg-toast.critical { border-left: 4px solid var(--tg-critical); }
        .tg-toast.high     { border-left: 4px solid var(--tg-high); }
        .tg-toast.medium   { border-left: 4px solid var(--tg-medium); }
        .tg-toast.success  { border-left: 4px solid var(--tg-accent); }

        /* Scan animation */
        @keyframes scan-line {
            0% { transform: translateY(-100%); }
            100% { transform: translateY(100vh); }
        }

        /* Responsive */
        @media (max-width: 992px) {
            .tg-sidebar {
                transform: translateX(-100%);
            }
            .tg-sidebar.open {
                transform: translateX(0);
            }
            .tg-main {
                margin-left: 0;
            }
        }
    </style>

    @stack('styles')
</head>
<body>

<!-- Sidebar -->
<aside class="tg-sidebar" id="tgSidebar">
    <div class="tg-sidebar-logo">
        <div class="tg-logo-icon">
            <i class="fas fa-shield-halved"></i>
        </div>
        <div>
            <div class="tg-logo-text">Torex <span>Guard</span></div>
        </div>
        <div class="tg-logo-badge">v1.0</div>
    </div>

    <nav class="tg-nav">
        <div class="tg-nav-section">Overview</div>
        <a href="{{ route('dashboard') }}" class="tg-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-gauge-high"></i></span>
            Dashboard
        </a>
        <a href="{{ route('projects.index') }}" class="tg-nav-item {{ request()->routeIs('projects.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-folder-open"></i></span>
            Projects
            <span class="tg-nav-badge green">{{ auth()->user()->projects()->count() }}</span>
        </a>
        <a href="{{ route('servers.index') }}" class="tg-nav-item {{ request()->routeIs('servers.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-server"></i></span>
            Servers
        </a>

        <div class="tg-nav-section">Security</div>
        <a href="{{ route('threats.index') }}" class="tg-nav-item {{ request()->routeIs('threats.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-bug"></i></span>
            Threats
            @if($activeThreats = auth()->user()->organizations()->first()?->activeThreatCount())
                <span class="tg-nav-badge">{{ $activeThreats }}</span>
            @endif
        </a>
        <a href="{{ route('incidents.index') }}" class="tg-nav-item {{ request()->routeIs('incidents.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-triangle-exclamation"></i></span>
            Incidents
        </a>
        <a href="{{ route('alerts.index') }}" class="tg-nav-item {{ request()->routeIs('alerts.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-bell"></i></span>
            Alerts
        </a>
        <a href="{{ route('quarantine.index') }}" class="tg-nav-item {{ request()->routeIs('quarantine.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-vault"></i></span>
            Quarantine
        </a>

        <div class="tg-nav-section">Analysis</div>
        <a href="{{ route('scans.index') }}" class="tg-nav-item {{ request()->routeIs('scans.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-magnifying-glass-chart"></i></span>
            Scans
        </a>

        <div class="tg-nav-section">System</div>
        <a href="{{ route('settings.index') }}" class="tg-nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-gear"></i></span>
            Settings
        </a>
    </nav>

    <div class="tg-sidebar-bottom">
        <div class="tg-user-card" x-data="{ open: false }" @click="open = !open">
            <div class="tg-user-avatar">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
            </div>
            <div style="flex:1;min-width:0">
                <div class="tg-user-name">{{ auth()->user()->name ?? 'User' }}</div>
                <div class="tg-user-role">{{ auth()->user()->role ?? 'developer' }}</div>
            </div>
            <i class="fas fa-ellipsis-vertical text-muted-tg" style="font-size:12px"></i>
        </div>
    </div>
</aside>

<!-- Main -->
<main class="tg-main">
    <!-- Topbar -->
    <header class="tg-topbar">
        <button class="btn-tg-ghost d-lg-none" onclick="document.getElementById('tgSidebar').classList.toggle('open')">
            <i class="fas fa-bars"></i>
        </button>
        <div class="tg-page-title">@yield('page-title', 'Dashboard')</div>
        <div class="tg-topbar-actions">
            <div class="tg-live-indicator" id="liveIndicator">
                <div class="tg-live-dot"></div>
                LIVE
            </div>
            <a href="{{ route('alerts.index') }}" class="tg-alert-btn" title="Alerts">
                <i class="fas fa-triangle-exclamation" style="font-size:15px"></i>
                <div class="tg-alert-dot" id="alertDot" style="display:none"></div>
            </a>
            <a href="{{ route('notifications.index') }}" class="tg-alert-btn" title="Notifications" style="margin-left:6px">
                <i class="fas fa-bell" style="font-size:15px"></i>
                <div class="tg-alert-dot" id="notifDot" style="display:none"></div>
            </a>
            <div class="dropdown">
                <button class="btn-tg-ghost" data-bs-toggle="dropdown">
                    <i class="fas fa-circle-user"></i>
                    {{ auth()->user()->name ?? 'Account' }}
                    <i class="fas fa-chevron-down" style="font-size:10px"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="background:var(--tg-bg-card);border:1px solid var(--tg-border);border-radius:12px;padding:6px;">
                    <li><a class="dropdown-item" href="{{ route('settings.profile') }}" style="color:var(--tg-text-secondary);border-radius:8px;padding:8px 14px;font-size:13px">
                        <i class="fas fa-user me-2"></i>Profile
                    </a></li>
                    <li><a class="dropdown-item" href="{{ route('settings.index') }}" style="color:var(--tg-text-secondary);border-radius:8px;padding:8px 14px;font-size:13px">
                        <i class="fas fa-gear me-2"></i>Settings
                    </a></li>
                    <li><hr class="dropdown-divider" style="border-color:var(--tg-border)"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item" style="color:var(--tg-critical);border-radius:8px;padding:8px 14px;font-size:13px;background:none;border:none;width:100%;text-align:left;cursor:pointer">
                                <i class="fas fa-arrow-right-from-bracket me-2"></i>Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Content -->
    <div class="tg-content">
        @if(session('success'))
            <div class="alert mb-4" style="background:var(--tg-accent-dim);border:1px solid rgba(0,255,136,0.25);border-radius:12px;color:var(--tg-accent);padding:12px 18px;font-size:13.5px">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert mb-4" style="background:var(--tg-critical-dim);border:1px solid rgba(255,51,102,0.25);border-radius:12px;color:var(--tg-critical);padding:12px 18px;font-size:13.5px">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            </div>
        @endif

        @yield('content')
    </div>
</main>

<!-- Toast Container -->
<div class="tg-toast-container" id="toastContainer"></div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ── CSRF Setup ────────────────────────────────────────────────
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// ── Toast System ──────────────────────────────────────────────
function showToast(title, message, type = 'success') {
    const icons = {
        critical: 'fa-skull-crossbones',
        high: 'fa-triangle-exclamation',
        medium: 'fa-circle-info',
        success: 'fa-check-circle',
    };
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `tg-toast ${type}`;
    toast.innerHTML = `
        <i class="fas ${icons[type] || icons.success}" style="color:var(--tg-${type === 'success' ? 'accent' : type})"></i>
        <div>
            <div style="font-weight:600;font-size:13.5px;color:var(--tg-text-primary)">${title}</div>
            <div style="font-size:12px;color:var(--tg-text-muted);margin-top:2px">${message}</div>
        </div>
    `;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'none';
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// ── Alert Bell ────────────────────────────────────────────────
async function checkAlerts() {
    try {
        const r = await fetch('/api/v1/dashboard/alerts/unread', {
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        });
        if (r.ok) {
            const d = await r.json();
            const dot = document.getElementById('alertDot');
            if (dot) dot.style.display = d.count > 0 ? 'block' : 'none';
        }
    } catch(e) {}
}
checkAlerts();
setInterval(checkAlerts, 30000);

async function checkNotifications() {
    try {
        const r = await fetch('/notifications/unread.json', {
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        });
        if (r.ok) {
            const d = await r.json();
            const dot = document.getElementById('notifDot');
            if (dot) dot.style.display = d.unread > 0 ? 'block' : 'none';
        }
    } catch(e) {}
}
checkNotifications();
setInterval(checkNotifications, 30000);

// ── WebSocket / Reverb (Laravel Echo + Pusher transport) ─────
window.Pusher = window.Pusher || {};
@auth
@php $orgId = optional(\Illuminate\Support\Facades\Auth::user()->organizations()->first())->id; @endphp
@if($orgId)
window.__TOREX = {
    orgId: {{ $orgId }},
    reverb: {
        key:    @json(config('broadcasting.connections.reverb.key', env('REVERB_APP_KEY'))),
        wsHost: @json(env('REVERB_HOST', request()->getHost())),
        wsPort: {{ (int) env('REVERB_PORT', 8080) }},
        scheme: @json(env('REVERB_SCHEME', 'http')),
    }
};
@endif
@endauth
</script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.0/dist/echo.iife.js"></script>
<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js"></script>
<script>
(function () {
    if (!window.__TOREX || !window.Pusher || !window.Echo) return;
    const r = window.__TOREX.reverb;
    if (!r.key) return;
    try {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: r.key,
            wsHost: r.wsHost,
            wsPort: r.wsPort,
            wssPort: r.wsPort,
            forceTLS: r.scheme === 'https',
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth',
        });
        const ch = window.Echo.private('org.' + window.__TOREX.orgId);
        ch.listen('.threat.detected', (e) => {
            window.showToast && showToast(e.title || 'Threat detected', (e.server || ''), e.severity === 'critical' ? 'danger' : 'warning');
        });
        ch.listen('.alert.created', (e) => {
            const dot = document.getElementById('alertDot'); if (dot) dot.style.display = 'block';
        });
        ch.listen('.server.status', (e) => {
            console.info('[torex] server status', e);
        });
        ch.listen('.scan.completed', (e) => {
            window.showToast && showToast('Scan completed', `${e.files_scanned} files, ${e.threats_found} threats`, 'info');
        });
    } catch (err) {
        console.warn('[torex] Echo init failed', err);
    }
})();
</script>

@stack('scripts')
</body>
</html>
